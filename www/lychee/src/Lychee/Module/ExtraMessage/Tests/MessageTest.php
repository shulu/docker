<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 2017/10/27
 * Time: 下午1:37
 */

namespace Lychee\Module\ExtraMessage\Tests;


use Facebook\Facebook;
use Lychee\Component\Foundation\StringUtility;
use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\ExtraMessage\Component\RKFacebookCurlHttpClient;

class MessageTest extends ModuleAwareTestCase {
	public function test() {

//		$total = $this->extraMessageService()->preGeneratePromotionCode(100000);
//		var_dump('total' . $total);

//		$code = $this->extraMessageService()->generatePromotionCode(0, 0, 0, 0);
//		var_dump($code);



		$appid = $this->container()->getParameter('facebook_appid');
		$secret = $this->container()->getParameter('facebook_secret');
		$proxy_host = $this->container()->getParameter('proxy_host');
		$proxy_port = $this->container()->getParameter('proxy_port');

		if ($proxy_port) {
			$proxy = ['host' => $proxy_host, 'port' => $proxy_port];
		}

		var_dump($proxy);exit;
		$facebook = new Facebook(['app_id' => $appid, 'app_secret' => $secret, 'default_graph_version' => 'v2.10',]);

		if (!empty($proxy)) {
			$client = new RKFacebookCurlHttpClient();
			$client->proxy = $proxy;
			$facebook->getClient()->setHttpClientHandler($client);
		}

		$token = 'EAAEoXTgTZAasBAIubkJ7OD64Gl7herEUID1aPHW2lQEZBmlXGdDwkhsvditkQMUFYOQMCFZAiZAfkIGhzkr3TrPDrLQ7AKGeVqqoVZBkhTZC7gKJHO8TTmlAGROZClrcXSdP0c4PgF8ZCA2mUd94OHfC5Kv2A1lZCzXBUchrT2Ma8yrP9eWRNjlZCkYZBUA0RR8NeFkl3d4j6Xr924XQjrttI1DWgHMcfmON1UH9ZC79RPcaKAZDZD';
		try {
			$response = $facebook->get('/me', $token);
		} catch (\Exception $e) {
			var_dump($e->getMessage());
		}

		var_dump($response);
	}
}