<?php
namespace Lychee\Module\Payment\ThridParty\Alipay;

class AlipayRequester {

    private $serverUrl = 'https://openapi.alipay.com/gateway.do';
    private $appId;
    private $sellerId;
    private $privateKey;
    private $alipayPublicKey;

    public function __construct($appId, $sellerId, $privateKey, $alipayPublicKey) {
    	$this->appId = $appId;
    	$this->sellerId = $sellerId;
//        $this->privateKey = 'file://' . __DIR__ . '/private.pem';
//        $this->alipayPublicKey = 'file://' . __DIR__ . '/alipay_public.pem';
        $this->privateKey = 'file://' . __DIR__ . '/' . $privateKey;
        $this->alipayPublicKey = 'file://' . __DIR__ . '/' . $alipayPublicKey;
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
        return implode('&', $kvs);
    }

    private function genSign($params) {
        $privateKey = openssl_get_privatekey($this->privateKey, 'ciyo.cn');
        openssl_sign($this->genSignString($params), $sign, $privateKey, OPENSSL_ALGO_SHA1);
        openssl_free_key($privateKey);
        return base64_encode($sign);
    }

    private function verify($content, $sign, $signType = 'RSA') {
        $key = openssl_get_publickey($this->alipayPublicKey);
        $valid = openssl_verify($content, base64_decode($sign), $key,
            $signType == 'RSA2' ? OPENSSL_ALGO_SHA256 : OPENSSL_ALGO_SHA1);
        openssl_free_key($key);
        return $valid;
    }

    /**
     * @param string $productTitle
     * @param string $productDesc
     * @param int $transactionId
     * @param string $totalFee
     * @param \DateTimeInterface $startTime
     * @param \DateTimeInterface $endTime
     * @param string $notifyUrl
     * @return string
     */
    public function signPayRequest($productTitle, $productDesc, $transactionId, $totalFee, $startTime, $endTime, $notifyUrl) {
        $bizContent = array(
            'body' => $productDesc,
            'subject' => $productTitle,
            'out_trade_no' => strval($transactionId),
            'timeout_express' => $endTime->diff($startTime)->format('%im'),
            'total_amount' => strval($totalFee),
            'product_code' => 'QUICK_MSECURITY_PAY'
        );
        $params = array(
            'app_id' => $this->appId,
            'method' => 'alipay.trade.app.pay',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA',
            'timestamp' => $startTime->format('Y-m-d H:i:s'),
            'version' => '1.0',
            'notify_url' => $notifyUrl,
            'biz_content' => json_encode($bizContent)
        );
        $params['sign'] = $this->genSign($params);
        return http_build_query($params);
    }

    /**
     * @param array $params
     * @return bool
     */
    public function verifyNotify($params) {
        $sign = $params['sign'];
        $signType = $params['sign_type'];
        unset($params['sign']);
        unset($params['sign_type']);
        $signContent = $this->genSignString($params);
        if (!$this->verify($signContent, $sign, $signType)) {
            return false;
        }

        $appIdValid = isset($params['app_id']) && $params['app_id'] == $this->appId;
        $sellerIdValid = isset($params['seller_id']) ? $params['seller_id'] == $this->sellerId : true;
        if (!($appIdValid && $sellerIdValid)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $outTradeNo
     * @return null|array
     */
    public function queryOrderInfo($outTradeNo) {
        $bizContent = array(
            'out_trade_no' => strval($outTradeNo),
            //'trade_no' => '1111',
        );
        $res = $this->request('POST', 'alipay.trade.query', $bizContent);
        $data = json_encode($res['alipay_trade_query_response'], JSON_UNESCAPED_UNICODE);
        $valid = $this->verify($data, $res['sign']);
        if ($valid) {
            return $res['alipay_trade_query_response'];
        } else {
            return null;
        }
    }

    private function request($httpMethod, $apiMethod, $bizContent) {
        $params = array(
            'app_id' => $this->appId,
            'method' => $apiMethod,
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'biz_content' => json_encode($bizContent)
        );
        $params['sign'] = $this->genSign($params);

//var_dump($params);
        $rep = $this->httpRequest($httpMethod, $this->serverUrl, $params);
        return json_decode($rep, true);
    }

    private function httpRequest($method, $url, $params) {
        $ch = curl_init();

        if ($method == 'GET') {
            $url .= '?'.http_build_query($params);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if ($method == 'POST' && is_array($params) && !empty($params)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            $headers = array('content-type: application/x-www-form-urlencoded;charset=UTF-8');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $rep = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception($error, 0);
        }

        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (200 !== $httpStatusCode) {
            throw new \Exception($rep, $httpStatusCode);
        }

        return $rep;
    }

}