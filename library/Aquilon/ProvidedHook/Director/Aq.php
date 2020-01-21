<?php


namespace Icinga\Module\Aquilon\ProvidedHook\Director;

use Icinga\Module\Director\Objects\IcingaObject;
use Exception;

class Aq
{
    function __construct($baseurl, $profiledir, $personalities)
    {
        $this->baseurl = $baseurl;
        $this->profiledir = $profiledir;
        $this->ch = curl_init(); // curl handle
        $this->personalities = $personalities;

        // Configure curl
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true); // curl_exec returns response as a string
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirect requests
        curl_setopt($this->ch, CURLOPT_MAXREDIRS, 5); // limit number of redirects to follow
    }

    private function getModifyTimes() {
        // set url
        curl_setopt($this->ch, CURLOPT_URL, $this->baseurl . "/profiles/profiles-info.xml");

        $output = curl_exec($this->ch);

        $curl_error = curl_error($this->ch);
        $status = curl_getinfo($this->ch, CURLINFO_RESPONSE_CODE);

        if ($curl_error === '' && $status === 200) {

            $profiles = simplexml_load_string($output);

            $modtimes = array();
            
            // Get the profile name and last modified time out of the XML
            foreach($profiles as $profile) {
                $modtimes[$profile[0]->__toString()] = $profile['mtime']->__toString();
            }
    
            return $modtimes;
        } else {
            throw new \Exception("Failed to get profiles from server! status=$status; error=$curl_error");
        }
    }

    private function downloadProfiles() {

        // Create cache directories if they do not already exist
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

            if(file_exists($filepath)) {
                if(filemtime($filepath) < $modtime) {
                    // download profile again
                    $this->downloadProfile($profile, $filepath);
                }
            } else {
                //download profile
                $this->downloadProfile($profile, $filepath);
            }
            // Add profile to array
            $profiles[] = $profile;
        }

        return $profiles;
    }

    private function downloadProfile($profileName, $filepath) {
        // Set URL
        curl_setopt($this->ch, CURLOPT_URL, $this->baseurl . "/profiles/" . $profileName);

        $output = curl_exec($this->ch);

        $curl_error = curl_error($this->ch);
        $status = curl_getinfo($this->ch, CURLINFO_RESPONSE_CODE);

        if ($curl_error === '' && $status === 200) {
            file_put_contents($filepath, $output); // Write the file to disk
        } else {
            // Fail silently, some files don't have a 200 status code (by design), so we'll just ignore these
        }

        unset($output); // Remove profile from memory, as a lot of them will take up a large amount of memory
    }

    function getHosts() {
        $profiles = $this->downloadProfiles();
        $hosts = array();

        foreach($profiles as $profile) {
            // Try and read from disk
            if(file_exists($this->profiledir . "/" . $profile)) {
                $f = file_get_contents($this->profiledir . "/" . $profile);
                
                $pro = json_decode($f, true);

                // Strip .json from the filename to get the hostname
                $hostname = str_replace(".json", "", $profile);
                
                // Get the personality key, if it exists. If not, just give it the default one!
                if (array_key_exists("personality", $pro['system'])) {
                    $personality = $pro['system']['personality']['name'];
                } else {
                    $personality = "all-hosts-t1";
                }
                
                $parray = $this->personalities->toArray();

                // Check if this personality is in the allowed personalities
                if (in_array($personality, $parray)) {

                    // Ignore hosts that have internal hostnames or have no network
                    if (strpos($hostname, "testing.internal") === false && array_key_exists("network", $pro['system'])) {
                        $address = $pro['system']['network']['primary_ip'];
                        // The shortname is used by the loggers, it's the bit before the first .
                        $shortname = explode(".", $hostname)[0];
                        // What we actually send to Icinga. If you want to send more data, just add another field here.
                        $hosts[] = (object) array(
                            "hostname" => $hostname,
                            "shortname" => $shortname,
                            "address" => $address,
                            "personality" => $personality
                        );
                    }
                }

                unset($pro); // remove profile from memory, as these can get quite large
            }
        }
        
        // Return hostname, shortname, personality, ip address
        return $hosts;
    }

    public function getPersonalities() {
        // set url
        curl_setopt($this->ch, CURLOPT_URL, $this->baseurl . ":6901/find/personality");

        // $output contains the output string
        $output = curl_exec($this->ch);

        $curl_error = curl_error($this->ch);
        $status = curl_getinfo($this->ch, CURLINFO_RESPONSE_CODE);

        if ($curl_error === '' && $status === 200) {
            $personalities = array();
            $pers = explode("\n", $output);
            foreach($pers as $per) {
                // Get the personality only, not the archetype
                $personalities[] = explode("/", $per)[1];
            }

            return array_unique($personalities);
        } else {
            return array();
        }

        unset($output);
    }
}