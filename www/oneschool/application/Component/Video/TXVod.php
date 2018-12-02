<?php
namespace Lychee\Component\Video;


/**
 * Class TXVod
 */
class TXVod {

    private $secretId;
    private $secretKey;
    private $host;
    private $requestTimeOut;
    private $isDebug;

    /**
     * @param $secretId
     * @param $secretKey
     */
    public function __construct($secretId, $secretKey, $host) {
        $this->secretId = $secretId;
        $this->secretKey = $secretKey;
        $this->host = $host;
        $this->requestTimeOut = 30;
        $this->isDebug = false;
    }

    /**
     * 开启调试模式
     */
    public function enableDebug() {
        $this->isDebug = true;
    }

    /**
     * 创建http客户端对象
     *
     * @return mixed
     */
    private function createHttpClient() {
        static $client = [];
        if (isset($client[$this->host])) {
            return $client[$this->host];
        }

        $client[$this->host] = new \GuzzleHttp\Client([
            'base_uri' => 'https://'.$this->host.'/',
            'timeout' => $this->requestTimeOut,
        ]);
        return $client[$this->host];
    }

    /**
     * 发起请求
     *
     * @param string     $uri
     * @param string     $action
     * @param array      $params
     * @return mixed
     */
    private function getRequest($uri, $action, $params=[]) {
        $params['Action'] = $action;
        $params['Timestamp'] = time();
        $params['Nonce'] = mt_rand(10000, 999999999);
        $params['Region'] = 'gz';
        $params['SecretId'] = $this->secretId;
        ksort($params);
        $srcStr = 'GET'.$this->host.'/'.$uri.'?'.http_build_query($params);
        $signStr = base64_encode(hash_hmac('sha1', $srcStr, $this->secretKey, true));
        $params['Signature'] = $signStr;

        $httpClient = $this->createHttpClient();
        $r =  $httpClient->get($uri, ['query' => $params])
            ->getBody()
            ->getContents();
        $r = json_decode($r, true);
        return $r;
    }

    /**
     * 确认事件
     *
     * @param string $msgHandles
     * @return bool
     */
    public function confirmEvent($msgHandles) {
        $this->debug('开始确认事件...');
        if (empty($msgHandles)) {
            $this->debug('...没事件需要确认');
            return false;
        }
        $params = [];
        foreach ($msgHandles as $key => $msgHandle) {
            $params['msgHandle.'.$key] = $msgHandle;
        }
        $r = $this->getRequest('v2/index.php', 'confirmEvent', $params);
        $this->debug('事件确认结果：');
        $this->debug($r);

        $this->debug('...确认事件完毕');
        return true;
    }

    /**
     * 处理接口返回结果，如果是返回错误，即抛异常
     *
     * @param array $apiResp
     * @throws \Exception
     */
    public function apiRespOrException($apiResp) {
        if (0==$apiResp['code']) {
            return true;
        }

        if (strpos($apiResp['message'], '没有事件通知')) {
            return true;
        }

        $msg = vsprintf("%s( %s - %s )", [$apiResp['message'], $apiResp['code'], $apiResp['codeDesc']]);
        throw new VideoException($msg);
    }

    /**
     * 拉取视频处理事件，回调函数抛异常即不确认事件，否则执行完回调函数后，即确认事件
     *
     * @param  string  $eventType
     * @param  callable $handle
     * @return bool
     * @throws \Exception
     */
    public function pullEvent($eventType, $handle) {
        $r = $this->getRequest('v2/index.php', 'pullEvent');
        $this->apiRespOrException($r);

        if (empty($r['eventList'])) {
            $this->debug('...没有拉取到事件');
            return false;
        }
        $eventType = (array)$eventType;
        $msgHandles = [];
        $this->debug('开始遍历事件...');
        foreach ($r['eventList'] as $event) {

            $this->debug('事件内容：');
            $this->debug($event);

            if (empty($event)
                || empty($event['eventContent'])) {
                $this->debug('...事件内容为空');
                continue;
            }

            if (!in_array($event['eventContent']['eventType'], $eventType)) {
                $this->debug('...不在该次处理的范围内,指定处理的事件类型：'.$eventType);
                continue;
            }

            try {
                $handle($event['eventContent']);
                $msgHandles[] = $event['msgHandle'];
            } catch (\Exception $e) {
                $this->output($e->__toString());
                continue;
            }
        }

        $this->debug('...遍历事件完毕');

        $this->confirmEvent($msgHandles);
        return true;
    }

    /**
     * 调试信息
     * @param string $msg
     * @return bool
     */
    private function debug($msg) {
        if (!$this->isDebug) {
            return false;
        }
        $this->output($msg);
    }

    /**
     * 输出信息
     * @param string|array $msg
     */
    private function output($msg) {
        ob_start();
        if (is_array($msg)) {
            $msg = var_export($msg, true);
        }
        echo $msg;
        echo "\r\n";
        ob_flush();
    }

    /**
     * 获取视频播放次数统计日志下载地址
     *
     * @param string $startDate     Y-m-d格式
     * @param string $endDate       Y-m-d格式
     * @return mixed
     * @throws \Exception
     */
    public function getPlayStatLogList($startDate, $endDate)
    {
        $params = [];
        $params['from'] = $startDate;
        $params['to'] = $endDate;
        $r = $this->getRequest('v2/index.php', 'GetPlayStatLogList', $params);
        $this->apiRespOrException($r);
        return $r;
    }


}