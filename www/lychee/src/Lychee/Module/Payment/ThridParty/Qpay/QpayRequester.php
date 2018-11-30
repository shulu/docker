<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 21/02/2017
 * Time: 3:25 PM
 */

namespace Lychee\Module\Payment\ThridParty\Qpay;


use GuzzleHttp\Client;
use Monolog\Logger;

class QpayRequester {

	private $baseUrl = 'https://qpay.qq.com/cgi-bin/pay/';

	private $key;

	private $mchId;

	private $appId;

	private $appKey;

	/**
	 * @var Logger
	 */
	private $logger;

	public function __construct($logger) {
		$this->logger = $logger;
	}

	/**
	 * 统一下单
	 * @param $productTitle
	 * @param $transactionId
	 * @param $totalFee
	 * @param $startTime
	 * @param $endTime
	 * @param $notifyUrl
	 * @param $clientIp
	 *
	 * @return mixed|null
	 */
	public function unifiedorder(
		$productTitle,
		$transactionId,
		$totalFee,
		$startTime,
		$endTime,
		$notifyUrl,
		$clientIp
	) {
		$params = array(
			'appid' => $this->appId,
			'mch_id' => $this->mchId,
			'nonce_str' => $this->genNonceStr(),
			'body' => $productTitle,
			'out_trade_no' => $transactionId,
			'fee_type' => 'CNY',
			'total_fee' => bcmul($totalFee, 100),
			'spbill_create_ip' => $clientIp,
			'time_start' => $startTime->format('YmdHis'),
			'time_expire' => $endTime->format('YmdHis'),
			'trade_type' => 'APP',
			'notify_url' => $notifyUrl
		);

		$params['sign'] = $this->genSign($params);
		$xml = $this->toXml($params);
		$client = new Client(['base_uri' => $this->baseUrl, 'timeout' => 10.0]);
		$res = $client->post('qpay_unified_order.cgi', ['body' => $xml]);
		$content = $res->getBody()->getContents();
//		echo $content . "\n";
		$r = $this->toArray($content);
//		print_r($r);
//		unset($r['sign']);
//		echo $this->genSignString($r) . "\n";
//		echo $this->genSign($r) . "\n";
//		exit;
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
	 * @param $productTitle
	 * @param $transactionId
	 * @param $totalFee
	 * @param $startTime
	 * @param $endTime
	 * @param $notifyUrl
	 * @param $clientIp
	 *
	 * @return array|null
	 */
	public function signPayRequest(
		$productTitle,
		$transactionId,
		$totalFee,
		$startTime,
		$endTime,
		$notifyUrl,
		$clientIp
	) {
		$prepayId = $this->unifiedorder(
			$productTitle,
			$transactionId,
			$totalFee,
			$startTime,
			$endTime,
			$notifyUrl,
			$clientIp
		);
		if ($prepayId == null) {
			return null;
		}

		$params = [
			'appId' => $this->appId,
			'bargainorId' => $this->mchId,
			'tokenId' => $prepayId,
			'nonce' => $this->genNonceStr(),
			'pubAcc' => ''
		];
		$signString = $this->genClientSignString($params);
//		echo $signString . "\n";
//		echo $this->key . "&\n";
//		$params['sig'] = base64_encode(hash_hmac('sha1', $signString, $this->key . '&'));
//		echo hash_hmac('sha1', $signString, 'i09gAYza97AJPWQO&') . "\n";
		$params['sig'] = base64_encode(hash_hmac('sha1', $signString, $this->appKey . '&', true));
		$params['timeStamp'] = time();
		return $params;
	}

	/**
	 * 订单查询
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
		$res = $client->post('qpay_order_query.cgi', ['body' => $xml]);
		$r = $this->toArray($res->getBody()->getContents());
		$returnSuccess = isset($r['return_code']) && $r['return_code'] == 'SUCCESS';
		$resultSuccess = isset($r['result_code']) && $r['result_code'] == 'SUCCESS';
		if (!($returnSuccess && $resultSuccess)) {
			return null;
		} else {
			return $r;
		}
	}

	private function genSignString($params, $signNullValue = false) {
		ksort($params);

		$kvs = array();
		foreach ($params as $k => $v) {
			if (false === $signNullValue && empty($v) && $v !== '0') {
				continue;
			}
			$kvs[] = "$k=$v";
		}
		$kvs[] = "key={$this->key}";
		return implode('&', $kvs);
	}

	private function genClientSignString($params) {
		ksort($params);

		$kvs = [];
		foreach ($params as $k => $v) {
			$kvs[] = "$k=$v";
		}

		return implode('&', $kvs);
	}

	private function genSign($params, $hashMethod = 'md5', $signNullValue = false) {
		return strtoupper(hash($hashMethod, $this->genSignString($params, $signNullValue)));
//		return strtoupper(md5($this->genSignString($params)));
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

	private function verify($params, $sign) {
		unset($params['sign']);
		return $this->genSign($params) == $sign;
	}

	public function setAccount($key, $mchId, $appId, $appKey) {
		$this->key = $key;
		$this->mchId = $mchId;
		$this->appId = $appId;
		$this->appKey = $appKey;

		return $this;
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

}