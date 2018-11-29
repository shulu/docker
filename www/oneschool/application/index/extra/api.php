<?php
/**
 * -------------------------
 *
 * -------------------------
 * User: shulu
 * Date: 2018/11/29
 * Time: 14:24
 */

return [
	'api_auth' => true,  //是否开启授权认证
	'auth_class' => \app\index\auth\OauthAuth::class, //授权认证类
	'api_debug'=>false,//是否开启调试
];