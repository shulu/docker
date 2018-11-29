<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\Route;
Route::get('/',function(){
	return 'Hello,world!';
});

Route::group('v1',function (){
	Route::any('auth/signin/mobile/:email/:nickname','index/User/createWithEmail');
	Route::any('auth/send_sms_code','index/User/sendCode');
	Route::any('auth/signin/captcha','index/User/sendCode');
	Route::any('account/password/reset','index/User/sendCode');
	Route::resource('user','index/User');
});

Route::any('accessToken','index/auth/accessToken');//Oauth

return [
    '__pattern__' => [
    ],
];
