<?php
namespace Lychee\Component\ShuMei;
/**
 * api请求的url
 */
define('SM_API_HOST', 'http://api.fengkongcloud.com');
/**
 * fp请求的url
 */
define('SM_FP_HOST', 'http://fp.fengkongcloud.com');
/**
 * finance api 请求的url
 */
define('SM_FINANCE_HOST', 'https://finance-api.fengkongcloud.com');
/**
 * captcha api 请求的url
 */
define('SM_CAPTCHA_HOST', 'http://captcha.fengkongcloud.com');
/**
 * accessKey 配置
 */
define('SM_ACCESSKEY', 'tEvjAITgImrDvypf55Wd'); // 客户的accessKey
/**
 *  请求具体接口的uri
 */
define('TEXT_URI', '/v2/saas/anti_fraud/text');
define('IMG_URI', '/v2/saas/anti_fraud/img');
define('FINANCE_LABEL_URI', '/v2/finance/labels');
define('FINANCE_MALAGENT_URI', '/v2/finance/malagent');
define('REGISTER_URI', '/v2/saas/register');
define('FINANCE_LENDING_URI', '/v3/finance/lending');
define('SMS_URI', '/v2/saas/anti_fraud/sms');
define('SVERIFY_URI', '/ca/v1/sverify');

class ShuMei {
    public function Text($text,$userId=0,$type='SOCIAL'){
        /**
         * 按照接口参数组装请求数据
         * 需要注意 data 参数，本身是个数组形式
         */
        $postData = [];
        $postData['accessKey'] = 'tEvjAITgImrDvypf55Wd';
        $postData['type'] = $type;
        $dataParams = [];
        $dataParams['tokenId'] = "$userId"; // 设置tokenId, 由客户提供
        $dataParams['text'] = $text; // 设置文本内容
        $postData['data'] = $dataParams;
        $mycurl = new namespace\SMCurl();
        $mycurl->url = SM_API_HOST . TEXT_URI; // 设置请求url地址
        $response = $mycurl->Post($postData); // 发起接口请求
        $resJson = json_decode($response, true);
        /**
         * 接口会返回code， code=1100 时说明请求成功，根据不同的 riskLevel 风险级别进行业务处理
         * 当 code!=1100 时，如果是 1902 错误，需要检查参数配置
         * 其余情况需要根据错误码进行重试或者其它异常处理
         */
        if (isset($resJson["code"]) && $resJson["code"] == 1100) {
            if ($resJson["riskLevel"] == "PASS") {
                // 放行
            } else if ($resJson["riskLevel"] == "REVIEW") {
                // 人工审核，如果没有审核，就放行
            } else if ($resJson["riskLevel"] == "REJECT") {
                // 拒绝
            } else {
                // 异常
            }
        } else {
            // 接口请求失败，需要参照返回码进行不同的处理
        }

        return $resJson;
    }
}

class SMCurl {
    public $url = "";
    public $connectTime = 2;
    public $timeout = 5;
    public function Get() {
        //初始化
        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        //返回结果
        return $output;
    }
    public function Post($postData) {
        $data_string = json_encode($postData);
        $ch = curl_init();
        // 设置超时时间
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTime);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8', 'Content-Length: ' . strlen($data_string)));
        $output = curl_exec($ch);
        curl_close($ch);
        //返回结果
        return $output;
    }
}