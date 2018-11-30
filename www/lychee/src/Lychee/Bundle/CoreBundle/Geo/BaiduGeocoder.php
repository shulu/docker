<?php
namespace Lychee\Bundle\CoreBundle\Geo;

class BaiduGeocoder {

    private $apiKey;

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function getAddressWithIp($ip) {
        $format = 'http://api.map.baidu.com/location/ip?coor=bd09ll&ak=%s&ip=%s';
        $url = sprintf($format, $this->apiKey, $ip);
        $result = file_get_contents($url);
        $content = json_decode($result, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            return null;
        }

        if ($content['status'] != 0) {
            return null;
        }

        return $content['content']['address'];
    }

    public function getAddressWithCoordinate($latitude, $longitude) {
        $format = 'http://api.map.baidu.com/geocoder/v2/?output=json&pois=0&ak=%s&location=%f,%f';
        $url = sprintf($format, $this->apiKey, $latitude, $longitude);
        $result = file_get_contents($url);
        $content = json_decode($result, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            return null;
        }

        if ($content['status'] != 0) {
            return null;
        }

        return $content['result']['formatted_address'];
    }
}