<?php
namespace Lychee\Module\Payment\ThridParty\Wechat;

use GuzzleHttp\Client;
use Monolog\Logger;

class WechatRequester {

    private $baseUrl = 'https://api.mch.weixin.qq.com';

    private $key;

    private $mchId;

    private $appId;

	/**
	 * @var Logger
	 */
	private $logger;

    public function __construct($logger) {
	    $this->logger = $logger;
    }

	/**
	 * @param $mchId
	 *
	 * @return $this
	 */
    public function setMchId($mchId) {
    	$this->mchId = $mchId;

	    return $this;
    }

	/**
	 * @param $appId
	 *
	 * @return $this
	 */
    public function setAppId($appId) {
    	$this->appId = $appId;

	    return $this;
    }

	/**
	 * @param $key
	 *
	 * @return $this
	 */
    public function setKey($key) {
    	$this->key = $key;

	    return $this;
    }

    /**
     * @param string $productTitle
     * @param string $productDesc
     * @param int $transactionId
     * @param string $totalFee
     * @param \DateTime $startTime
     * @param \DateTime $endTime
     * @param string $notifyUrl
     * @param string $clientIp
     *
     * @return string|null
     */
    public function unifiedorder($productTitle, $productDesc, $transactionId, $totalFee, $startTime, $endTime, $notifyUrl, $clientIp) {
        $params = array(
            'appid' => $this->appId,
            'mch_id' => $this->mchId,
            'nonce_str' => $this->genNonceStr(),
            'body' => $productTitle,
            'detail' => $productDesc,
            'out_trade_no' => $transactionId,
            'total_fee' => bcmul($totalFee, 100),
            'spbill_create_ip' => $clientIp,
            'time_start' => $startTime->format('YmdHis'),
            'time_expire' => $endTime->format('YmdHis'),
            'notify_url' => $notifyUrl,
            'trade_type' => 'APP'
        );

        $params['sign'] = $this->genSign($params);
        $xml = $this->toXml($params);
        $client = new Client(['base_uri' => $this->baseUrl, 'timeout' => 10.0]);
        $res = $client->post('/pay/unifiedorder', ['body' => $xml]);
        $content = $res->getBody()->getContents();
        $r = $this->toArray($content);
        if (!isset($r['sign']) || !$this->verify($r, $r['sign'])) {
        	$this->logger->err(sprintf("Sign Error: %s\n", $content));
            return null;
        }
        $returnSuccess = isset($r['return_code']) && $r['return_code'] == 'SUCCESS';
        $resultSuccess = isset($r['result_code']) && $r['result_code'] == 'SUCCESS';
        if (!($returnSuccess && $resultSuccess)) {
	        $this->logger->err(sprintf("Return Error: %s\n", $content));
            return null;
        } else {
            return $r['prepay_id'];
        }
    }

    /**
     * @param string $productTitle
     * @param string $productDesc
     * @param int $transactionId
     * @param string $totalFee
     * @param \DateTime $startTime
     * @param \DateTime $endTime
     * @param string $notifyUrl
     * @param string $clientIp
     *
     * @return array|null
     */
    public function signPayRequest($productTitle, $productDesc, $transactionId, $totalFee, $startTime, $endTime, $notifyUrl, $clientIp) {
        $prepayId = $this->unifiedorder($productTitle, $productDesc, $transactionId, $totalFee, $startTime, $endTime, $notifyUrl, $clientIp);
        if ($prepayId == null) {
            return null;
        }

        $params = [
            'appid' => $this->appId,
            'partnerid' => $this->mchId,
            'prepayid' => $prepayId,
            'package' => 'Sign=WXPay',
            'noncestr' => $this->genNonceStr(),
            'timestamp' => time()
        ];
        $params['sign'] = $this->genSign($params);
        return $params;
    }

    /**
     * @param int $outTradeNo
     * @return array|null
     */
    public function orderquery($outTradeNo) {
        $params = array(
            'appid' => $this->appId,
            'mch_id' => $this->mchId,
            'nonce_str' => $this->genNonceStr(),
            'out_trade_no' => $outTradeNo,
        );
        $params['sign'] = $this->genSign($params);
        $xml = $this->toXml($params);

        $client = new Client(['base_uri' => $this->baseUrl, 'timeout' => 10.0]);
        $res = $client->post('/pay/orderquery', ['body' => $xml]);
        $r = $this->toArray($res->getBody()->getContents());
        $returnSuccess = isset($r['return_code']) && $r['return_code'] == 'SUCCESS';
        $resultSuccess = isset($r['result_code']) && $r['result_code'] == 'SUCCESS';
        if (!($returnSuccess && $resultSuccess)) {
            return null;
        } else {
            return $r;
        }
    }

	/**
	 * @param $params
	 *
	 * @return bool|array
	 */
    public function verifyNotify($params) {
//        $params = $this->toArray($notifyBody);
        if (!$this->verify($params, $params['sign'])) {
            return false;
        }

        return $params;
    }

    private function genSignString($params) {
        ksort($params);

        $kvs = array();
        foreach ($params as $k => $v) {
            if (empty($v)) {
                continue;
            }
            $kvs[] = "$k=$v";
        }
        $kvs[] = "key={$this->key}";
        return implode('&', $kvs);
    }

    private function genSign($params) {
        return strtoupper(md5($this->genSignString($params)));
    }

    private function verify($params, $sign) {
        unset($params['sign']);
        return $this->genSign($params) == $sign;
    }

    private function genNonceStr() {
        return bin2hex(openssl_random_pseudo_bytes(16));
    }

    private function toXml($params) {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startElement('xml');
        foreach ($params as $k => $v) {
            $xml->writeElement($k, $v);
        }
        $xml->endElement();
        return $xml->flush();
    }

    public function toArray($xml) {
        $iter = (new \SimpleXMLElement($xml))->children();
        $arr = [];
        foreach ($iter as $e) {
            /** @var \SimpleXMLElement $e */
            $arr[$e->getName()] = $e->__toString();
        }
        return $arr;
    }

	/**
	 * @param $key
	 * @param $mchId
	 * @param $appId
	 *
	 * @return $this
	 */
	public function setAccount($key, $mchId, $appId) {
    	$this->key = $key;
    	$this->mchId = $mchId;
    	$this->appId = $appId;

    	return $this;
    }

}