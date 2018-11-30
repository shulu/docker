<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 29/11/2016
 * Time: 5:58 PM
 */

namespace Lychee\Bundle\ApiBundle\Tests\Controller;



use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaymentControllerTest extends WebTestCase {

	public function testCreateTransaction() {
		$client = new Client();
		$res = $client->request('POST', 'http://localhost:8300/payment/inke_transaction', [
			'access_token' => '2fcd63133a03e2de7ad85bf45f47b994580054dd',
			'order_id' => '123'
		]);
		var_dump($res->getBody());
	}
}