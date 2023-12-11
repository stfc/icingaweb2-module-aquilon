<?php

namespace Icinga\Module\Aquilon\ProvidedHook\Director;

use Icinga\Exception\IcingaException;
// use Icinga\Module\Director\Objects\IcingaObject;
// use Exception;

class AquilonImport {

    // Construct
    // Arguments: 
    // $baseurl - API url for your instance, e.g. http://aquilon.gridpp.rl.ac.uk
    // $basedir - Directory for your Archetype/Personality file e.g. /usr/share/icingaweb2/modules/aquilon/library/Aquilon/ProvidedHook/Director/ArchetypePersonalities.txt

    function __construct($baseurl, $basedir)
    {
        $this->baseurl = $baseurl;
        $this->basedir = $basedir;
        $this->ch = curl_init();
    }

    // Main function
    public function parseJSONData() {
        $raw_data = self::getRawData($this->baseurl);
        $parsed_data = self::parseRawData($raw_data);
        $arch_pers_array = self::getArchPersList();
        $matching_servers = self::filterByArchetypeAndPersonality($parsed_data, $arch_pers_array);
        return $matching_servers;
    }

    public function getArchPersList(){
        $dir = $this->basedir;
        $lines = file($dir, FILE_IGNORE_NEW_LINES |  FILE_SKIP_EMPTY_LINES);
        return $lines;
    }

    // Retrieves JSON information.
    private function getRawData($url) {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($this->ch);

        return $result;
    }

    // Parses JSON data for email, archetype and personality.
    private function parseRawData($rawdata) {
        $json_data = json_decode($rawdata, true);
        $parsed_server = array();

        foreach ($json_data as $server => $info) {
            preg_match('/<(.+?)>/', $info['grn'], $matches);
            if (isset($matches[1])) {
                if (isset($info['raid'])) {
                    $has_raid = 'yes';
                    $raid_model = $info['raid']['_1']['model'];
                } else {
                    $has_raid = 'no';
                    $raid_model = 'N/A';
                };
                $parsed_server[] = (object) array(
                    'hostname' => $server,
                    'archetype' => self::is_set_or_empty_string('archetype', 'N/A', [$server=>$info]),
                    'personality' => self::is_set_or_empty_string('personality', 'N/A', [$server=>$info]),
                    'email' => trim($matches[1]),
                    'has_raid' => $has_raid,
                    'raid_model' => $raid_model
                );
            }
        }

        return $parsed_server;
    }

    // Returns default value if string is empty.
    private function is_set_or_empty_string($field_name, $default_value, $data) {
        return (isset($data[key($data)][$field_name]))? $data[key($data)][$field_name] : $default_value;
    }

    // Function to return a list of Archetype/Personality types currently in Aquilon
    public function getArchetypePersonality() {
        curl_setopt($this->ch, CURLOPT_URL, "http://aquilon.gridpp.rl.ac.uk:6901/find/personality");
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($this->ch);
        $archPers = array();
        $outputs = explode("\n", $result);
        foreach($outputs as $output) {
            $archPers[] = $output;
        }
        return array_unique($archPers);
    }

    public function filterByArchetypeAndPersonality($array, $archetype_personality) 
    {
        $matched_elements = [];
        foreach($archetype_personality as $key=>$value){
            $archetype = explode("/", $value)[0];
            $personality = explode("/", $value)[1];
            foreach ($array as $server)
            {
                if ($server->archetype === $archetype && $server->personality === $personality)
                {
                    $matched_elements[] = (object) array(
                        'hostname' => $server->hostname,
                        'archetype' => $server->archetype,
                        'personality' => $server->personality,
                        'email' => $server->email,
                        'has_raid' => $server->has_raid,
                        'raid_model' => $server->raid_model
                    );
                }
            }
        };
        return $matched_elements;
    }
}

?>