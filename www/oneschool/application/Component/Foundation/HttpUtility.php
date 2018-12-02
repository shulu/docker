<?php
namespace Lychee\Component\Foundation;

class HttpUtility {
    static public function getJson($url, $params, $timeout = 10, &$response = null) {
        $query = http_build_query($params);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_POSTFIELDS => $query,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_RETURNTRANSFER => true,
        ));
        $result = curl_exec($curl);

        if ($result === false) {
            $response = curl_error($curl);
            curl_close($curl);
            return null;
        }
        $response = $result;

        $resultJson = json_decode($result, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            return null;
        } else {
            return $resultJson;
        }
    }

    static public function requestJson($method, $url, $params, $timeout = 10) {
        $query = http_build_query($params);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_POSTFIELDS => $query,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_RETURNTRANSFER => true,
        ));
        if (strcmp($method, 'post') == 0) {
            curl_setopt($curl, CURLOPT_POST, true);
        } else if (strcmp($method, 'put') == 0) {
            curl_setopt($curl, CURLOPT_PUT, true);
        } else {
            curl_setopt($curl, CURLOPT_HTTPGET, true);
        }
        $result = curl_exec($curl);
        curl_close($curl);

        if ($result === false) {
            return null;
        }

        $resultJson = json_decode($result, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            return null;
        } else {
            return $resultJson;
        }
    }

    static public function postJson($url, $params, $timeout = 10) {
        $query = http_build_query($params);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_POSTFIELDS => $query,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true
        ));
        $result = curl_exec($curl);
        curl_close($curl);

        if ($result === false) {
            return null;
        }

        $resultJson = json_decode($result, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            return null;
        } else {
            return $resultJson;
        }
    }

	static public function postBilibiliJson($url, $params, $timeout = 10) {
		$query = http_build_query($params);
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_POSTFIELDS => $query,
			CURLOPT_CONNECTTIMEOUT => $timeout,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_USERAGENT => 'Mozilla/5.0 GameServer'
		));
		$result = curl_exec($curl);
		curl_close($curl);

		if ($result === false) {
			return null;
		}

		$resultJson = json_decode($result, true);
		if (json_last_error() != JSON_ERROR_NONE) {
			return null;
		} else {
			return $resultJson;
		}
	}
}