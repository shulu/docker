<?php
namespace Lychee\Module\IM;

class IMService {

    private $imHost;
    private $imPort;
    private $apiToken;

    /**
     * @param string $imHost
     * @param int $imPort
     * @param string $apiToken
     */
    public function __construct($imHost, $imPort, $apiToken) {
        $this->imHost = $imHost;
        $this->imPort = $imPort;
        $this->apiToken = $apiToken;
    }

    /**
     * @param int $from
     * @param int $to
     * @param int $time
     * @return bool
     */
    public function dispatchFollowing($from, $to, $time) {
        return $this->post("http://{$this->imHost}:{$this->imPort}/api/send_follow", array(
            'from' => $from, 'to' => $to, 'time' => $time
        ));
    }

    /**
     * @param int $from
     * @param int[] $toIds
     * @param int $type
     * @param string $message
     * @param int $time
     *
     * @return bool
     */
    public function dispatchMass($from, $toIds, $type, $message, $time) {
        return $this->post("http://{$this->imHost}:{$this->imPort}/api/send_mass", array(
            'from' => $from, 'to' => implode(',', $toIds), 'time' => $time,
            'type' => $type, 'body' => $message
        ));
    }

    /**
     * @param Message[] $messages
     */
    public function dispatch($messages) {
        $data = array_map(function(/** Message $m */$m){
            $datum = array(
                'from' => $m->from,
                'to' => $m->to,
                'time' => $m->time,
                'type' => $m->type
            );
            if ($m->body) {
                $datum['body'] = $m->body;
            }
            return $datum;
        }, $messages);
        $this->postJson('http://'.$this->imHost.':'.$this->imPort.'/api/send', $data);
    }

    private function postJson($url, $data, $timeout = 10) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => array(
                'X-API-TOKEN: '.$this->apiToken,
                'Content-Type: application/json'
            ),
        ));
        $result = curl_exec($curl);
        if ($result === false) {
            curl_close($curl);
            return false;
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return $httpCode == 200;
    }

    private function post($url, $param, $timeout = 10) {
        $query = http_build_query($param);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_POSTFIELDS => $query,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => array('X-API-TOKEN: '.$this->apiToken,),
        ));
        $result = curl_exec($curl);
        if ($result === false) {
            curl_close($curl);
            return false;
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return $httpCode == 200;
    }
}