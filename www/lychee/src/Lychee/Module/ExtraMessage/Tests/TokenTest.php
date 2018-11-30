<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 2017/11/30
 * Time: 上午11:52
 */

namespace Lychee\Module\ExtraMessage\Tests;


use Lychee\Component\Foundation\HttpUtility;
use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Authentication\AuthenticationService;
use Lychee\Module\ExtraMessage\EMAuthenticationService;
use Lychee\Module\ExtraMessage\Entity\EMUser;

class TokenTest extends ModuleAwareTestCase {
	public function test() {
		$result = $this->emAuthenticationService()->createTokenForUser(6, EMAuthenticationService::GRANT_TYPE_WECHAT);
		var_dump($result);
//		$this->authentication()->createTokenForUser(1, AuthenticationService::GRANT_TYPE_EMAIL);
//		$this->emAuthenticationService()->createTokenForUser(1, EMAuthenticationService::GRANT_TYPE_BILIBILI);

//		$params = [
//			'merc_id' => '2000431',
//			'order_id' => '939861',
//			'time' => time(),
//		];
//		$sign = $this->requestDmzjSignature($params, '30eed6b689f8e397e032cc4a69065c75');
//		$params['sign'] = $sign;
//
//		$url = 'http://fee.uebilling.com:23000/sdkfee/order/query';
//		$json = HttpUtility::postJson($url, $params);
//		var_dump($json);

	}

	protected function requestDmzjSignature($params, $secret, $algo = 'md5') {

		$params = array_filter($params, function($val){
			return (!is_null($val)) && ($val !== '');
		});

		$params['merc_key'] = $secret;
		ksort($params);

		$params_str = '';
		foreach ($params as $key => $value) {
			$params_str .= $key . '=' . $value . '&';
		}

		if (strlen($params_str)) {
			$length = strlen($params_str) - 1;
			$params_str = substr($params_str, 0, $length);
		}

		$sign = strtolower(hash($algo, $params_str));

		return $sign;
	}
}