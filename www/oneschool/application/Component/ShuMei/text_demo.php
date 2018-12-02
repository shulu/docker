<?php
define('ROOT_PATH', dirname(dirname(__FILE__)));
require ROOT_PATH . "/httpclient.php";
require ROOT_PATH . "/config.php";
use Lychee\Component\ShuMei\SMCurl;
/**
 * 按照接口参数组装请求数据
 * 需要注意 data 参数，本身是个数组形式
 */
$postData['accessKey'] = 'tEvjAITgImrDvypf55Wd';
$postData['type'] = 'SOCIAL';
$dataParams = array();
$dataParams['tokenId'] = 'tokenId_test'; // 设置tokenId, 由客户提供
$dataParams['text'] = 'iphone 7'; // 设置文本内容
$postData['data'] = $dataParams;
$mycurl = new SMCurl();
$mycurl->url = SM_API_HOST . TEXT_URI; // 设置请求url地址
$response = $mycurl->Post($postData); // 发起接口请求
//print_r($response);
$resJson = json_decode($response, true);
/**
 * 接口会返回code， code=1100 时说明请求成功，根据不同的 riskLevel 风险级别进行业务处理
 * 当 code!=1100 时，如果是 1902 错误，需要检查参数配置
 * 其余情况需要根据错误码进行重试或者其它异常处理
 */
if ($resJson["code"] == 1100) {
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

echo $resJson;