<?php
/**
 * -------------------------
 *
 * -------------------------
 * User: shulu
 * Date: 2018/11/29
 * Time: 14:12
 */

namespace app\index\controller;


use DawnApi\facade\ApiController;
class Base extends ApiController
{
	
	//是否开启授权认证
	public    $apiAuth = true;
	
}