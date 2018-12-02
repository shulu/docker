<?php
namespace Lychee\Bundle\WebsiteBundle\Wechat;

class JsSDK {
    private $appId;
    private $appSecret;
    private $cacheDir;

    /**
     * @param string $appId
     * @param string $appSecret
     * @param string $cacheDir
     */
    public function __construct($appId, $appSecret, $cacheDir) {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->cacheDir = $cacheDir;
    }

    public function getSignPackage() {
        $jsapiTicket = $this->getJsApiTicket();
        $url = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);

        $signPackage = array(
            "appId"     => $this->appId,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }

    private function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    private function getCacheFile($fileName) {
        return $this->cacheDir . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * @param string $filePath
     *
     * @return array|mixed
     */
    private function readCacheFile($filePath) {
        try {
            $data = file_get_contents($filePath);
            return json_decode($data, true);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getJsApiTicket() {
        $ticketCache = $this->getCacheFile('wechat_ticket.json');
        $data = $this->readCacheFile($ticketCache);
        if (!$data || $data['expire_time'] < time()) {
            $accessToken = $this->getAccessToken();
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $res = json_decode($this->httpGet($url), true);
            $ticket = $res['ticket'];
            if ($ticket) {
                $data['expire_time'] = time() + 7000;
                $data['jsapi_ticket'] = $ticket;
                $fp = fopen($ticketCache, 'w');
                fwrite($fp, json_encode($data));
                fclose($fp);
            }
        } else {
            $ticket = $data['jsapi_ticket'];
        }

        return $ticket;
    }

    private function getAccessToken() {
        $accessTokenCache = $this->getCacheFile('wechat_token.json');
        $data = $this->readCacheFile($accessTokenCache);
        if (!$data || $data['expire_time'] < time()) {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
            $res = json_decode($this->httpGet($url), true);
            $access_token = $res['access_token'];
            if ($access_token) {
                $data['expire_time'] = time() + 7000;
                $data['access_token'] = $access_token;
                $fp = fopen($accessTokenCache, 'w');
                fwrite($fp, json_encode($data));
                fclose($fp);
            }
        } else {
            $access_token = $data['access_token'];
        }
        return $access_token;
    }

    private function httpGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_URL, $url);

        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }
}