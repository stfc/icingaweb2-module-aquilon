<?php

namespace Icinga\Module\Aquilon\ProvidedHook\Director;

// use Icinga\Module\Director\Objects\IcingaObject;
// use Exception;

class AquilonImport {
    // Main function
    public function parseJSONData($url) {
        $raw_data = self::getRawData($url);
        $parsed_data = self::parseRawData($raw_data);
        return $parsed_data;
    }

    // Retrieves JSON information.
    private function getRawData($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    // Parses JSON data for email, archetype and personality.
    private function parseRawData($rawdata) {
        $json_data = json_decode($rawdata, true);
        $parsed_server = array();

        foreach ($json_data as $server => $info) {
            preg_match('/<(.+?)>/', $info['grn'], $matches);
            if (isset($matches[1])) {
                $parsed_server[] = (object) array(
                    'hostname' => $server,
                    'archetype' => self::is_set_or_empty_string('archetype', 'N/A', [$server=>$info]),
                    'personality' => self::is_set_or_empty_string('personality', 'N/A', [$server=>$info]),
                    'email' => trim($matches[1])
                );
            }
        }

        return $parsed_server;
    }

    // Returns default value if string is empty.
    private function is_set_or_empty_string($field_name, $default_value, $data) {
        return (isset($data[key($data)][$field_name]))? $data[key($data)][$field_name] : $default_value;
    }
}

?>