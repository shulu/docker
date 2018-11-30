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
define('SM_ACCESSKEY', 'xxxxxxxxxx'); // 客户的accessKey
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