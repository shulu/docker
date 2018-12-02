<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 29/11/2016
 * Time: 5:58 PM
 */

namespace Lychee\Bundle\ApiBundle\Tests\Controller;


use FOS\ElasticaBundle\Tests\Functional\WebTestCase;

class LiveControllerTest extends WebTestCase {

//	public function testDeductCoin() {
//		$url = 'http://localhost:8300/live/deduct_ciyocoin';
//		$params = [
//			'tid' => uniqid('', true),
//			'pid' => 528043,
//			'gifts' => '{"giftId":"57906a7b9d0d102c6f6017a1","masterPid":10222,"giftName":"波斯菊","price":1,"count":1,"giftDesc":"我送上波斯菊，美美哒","sendUserPid":10223}',
//		];
//		$secret = 'JVe5T73X9PZt';
//		ksort($params);
//		echo http_build_query($params) . PHP_EOL;
//		$signature = sha1(http_build_query($params).$secret);
//		$params['signature'] = $signature;
//		$ch = curl_init($url);
//		curl_setopt($ch, CURLOPT_POST, 1);
//		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
//		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//		$result = curl_exec($ch);
//		curl_close($ch);
//
//		echo $result;
//	}

	public function testInvokeInke() {
		$url = 'http://localhost:8300/user/check_verify';
		$params = [
			'access_time' => '1482490057',
			'appid' => '1000200001',
			'nonce_str' => 'sHY4HHpSBX4fDrRc6FEGDjnSA6FacAM7',
			'sig' => 'BD053A8548DCC4D2ABFF28A4C3C65CF6',
			'source' => 'inke_account',
			'uuid' => '31803'
		];
		$ch = curl_init($url . '?' . http_build_query($params));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		curl_close($ch);

		echo $result;
	}
}