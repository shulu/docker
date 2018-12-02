<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 29/11/2016
 * Time: 5:58 PM
 */

namespace Lychee\Bundle\ApiBundle\Tests\Controller;

/**
 * @coversDefaultClass \Lychee\Bundle\ApiBundle\Controller\AccountController
 */

class AccountTest extends Base {

    public $cursor = 0;

    /**
     *
     * 看自己
     *
     * /account/get
     *
     * @group prod
     * @covers ::getAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
	public function testGetSelfInfo() {
        $uri = '/account/get';
        $user = $this->getUser();
        $params = [];
        $params['uid'] = $user['uid'];
        $params['access_token'] = $user['access_token'];
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkInfoResp($content, $makeMsg);
            $this->assertArrayHasKey('favourites_count', $content, $makeMsg($content));
        });
	}

	private function checkInfoResp($content, $makeMsg) {
        $this->assertArrayHasKey('id', $content, $makeMsg($content));
        $this->assertArrayHasKey('nickname', $content, $makeMsg($content));
    }

    /**
     *
     * 看别人
     *
     * /account/get
     *
     * @group prod
     * @covers ::getAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testGetOtherInfo() {

        $uri = '/account/get';
        $user = $this->getUser();
        $params = [];
        $params['uid'] = 89239;
        $params['access_token'] = $user['access_token'];
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkInfoResp($content, $makeMsg);
        });

    }
}