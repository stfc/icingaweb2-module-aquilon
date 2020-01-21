<?php


namespace Icinga\Module\Aquilon\ProvidedHook\Director;

use Icinga\Module\Director\Objects\IcingaObject;
use Exception;

class Aq
{
    function __construct($baseurl, $profiledir)
    {
        $this->baseurl = $baseurl;
        $this->profiledir = $profiledir;
        $this->ch = curl_init(); // curl handle

        // Configure curl
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true); // curl_exec returns response as a string
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirect requests
        curl_setopt($this->ch, CURLOPT_MAXREDIRS, 5); // limit number of redirects to follow
    }

    private function getModifyTimes() {
        // set url
        curl_setopt($this->ch, CURLOPT_URL, $this->baseurl . "/profiles/profiles-info.xml");

        // $output contains the output string
        $output = curl_exec($this->ch);

        // close curl resource to free up system resources

        $curl_error = curl_error($this->ch);
        $status = curl_getinfo($this->ch, CURLINFO_RESPONSE_CODE);

        if ($curl_error === '' && $status === 200) {

            $profiles = simplexml_load_string($output);

            $modtimes = array();
    
            foreach($profiles as $profile) {
                $modtimes[$profile[0]->__toString()] = $profile['mtime']->__toString();
            }
    
            return $modtimes;
        } else {
            throw new \Exception("Failed to get profiles from server! status=$status; error=$curl_error");
        }
    }

    private function downloadProfiles() {

        if (!file_exists($this->profiledir)) {
            $res = mkdir($this->profiledir);
        }

        if (!file_exists($this->profiledir . "/" . "clusters")) {
            mkdir($this->profiledir . "/" . "clusters");
        }

        $modtimes = $this->getModifyTimes();

        $profiles = array();
        foreach($modtimes as $profile => $modtime) {
            $filepath = $this->profiledir . "/" . $profile;
            //print_r($filepath);
            if(file_exists($filepath)) {
                if(filemtime($filepath) < $modtime) {
                    // download profile again
                    $this->downloadProfile($profile, $filepath);
                }
            } else {
                //download profile
                $this->downloadProfile($profile, $filepath);
            }
            $profiles[] = $profile;
        }

        return $profiles;
    }

    private function downloadProfile($profileName, $filepath) {
        // set url
        curl_setopt($this->ch, CURLOPT_URL, $this->baseurl . "/profiles/" . $profileName);

        // $output contains the output string
        $output = curl_exec($this->ch);

        $curl_error = curl_error($this->ch);
        $status = curl_getinfo($this->ch, CURLINFO_RESPONSE_CODE);

        if ($curl_error === '' && $status === 200) {
            file_put_contents($filepath, $output);
        } else {
            // Fail silently, some files don't have a 200 status code, so we'll just ignore these
        }

        unset($output);
    }

    function getHosts() {
        // Return hostname, shortname, personality, ip address
        $profiles = $this->downloadProfiles();
        $hosts = array();

        foreach($profiles as $profile) {
            // Try and read from disk
            if(file_exists($this->profiledir . "/" . $profile)) {
                $f = file_get_contents($this->profiledir . "/" . $profile);
                
                $pro = json_decode($f, true);

                $hostname = str_replace(".json", "", $profile);

                if (array_key_exists("personality", $pro['system'])) {
                    $personality = $pro['system']['personality']['name'];
                } else {
                    $personality = "all-hosts-t1";
                }
                
                if (strpos($hostname, "testing.internal") === false && array_key_exists("network", $pro['system'])) {
                    $address = $pro['system']['network']['primary_ip'];
                    $shortname = explode(".", $hostname)[0];
                    $hosts[] = (object) array(
                        "hostname" => $hostname,
                        "shortname" => $shortname,
                        "address" => $address,
                        "personality" => $personality
                    );
                }

                unset($pro);
            }
        }

        //print_r($hosts);
    
        return $hosts;
    }
}