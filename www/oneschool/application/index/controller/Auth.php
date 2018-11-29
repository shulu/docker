<?php
/**
 * -------------------------
 *
 * -------------------------
 * User: shulu
 * Date: 2018/11/29
 * Time: 14:19
 */

namespace app\index\controller;
use app\index\auth\BasicAuth;
use app\index\auth\OauthAuth;
use think\Request;
class Auth
{
	public function accessToken()
	{
		$request = Request::instance();
		$OauthAuth = new OauthAuth();
		return $OauthAuth->accessToken($request);
	}
}