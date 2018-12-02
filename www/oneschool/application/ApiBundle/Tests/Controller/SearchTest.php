<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 29/11/2016
 * Time: 5:58 PM
 */

namespace Lychee\Bundle\ApiBundle\Tests\Controller;

/**
 * @coversDefaultClass \Lychee\Bundle\ApiBundle\Controller\SearchController
 */

class SearchTest extends Base {


    /**
     * 安卓
     *
     * /search
     *
     * @group prod
     * @covers ::searchAll
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
	public function testSearchAllForAndroid() {
        $uri = '/search';
        $user = $this->getUser();
        $params = [];
        $params['access_token'] = $user['access_token'];
        $params['query'] = '游戏';
        $params['client'] = 'android';
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkSearchAllFResp($content, $makeMsg);
        });
	}


    public function checkSearchAllFResp($content, $makeMsg) {
        $this->assertArrayHasKey('posts', $content);
        $this->assertTrue(count($content['posts'])>0, $makeMsg('帖子搜索结果不符'));
        $this->assertArrayHasKey('topics', $content);
        $this->assertTrue(count($content['topics'])>0, $makeMsg('次元搜索结果不符'));
        $this->assertArrayHasKey('users', $content);
        $this->assertTrue(count($content['users'])>0, $makeMsg('用户搜索结果不符'));
    }

    /**
     * 安卓
     *
     * /search/post
     *
     * @group prod
     * @covers ::search
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testSearchPostForAndroid() {
        $uri = '/search/post';
        $user = $this->getUser();
        $params = [];
        $params['access_token'] = $user['access_token'];
        $params['query'] = '游戏';
        $params['client'] = 'android';
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->assertArrayHasKey('posts', $content);
            $this->assertTrue(count($content['posts'])>0, $makeMsg('搜索结果不符'));
        });
    }


    /**
     * 安卓
     *
     * /search/topic
     *
     * @group prod
     * @covers ::search
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testSearchTopicForAndroid() {
        $uri = '/search/topic';
        $user = $this->getUser();
        $params = [];
        $params['access_token'] = $user['access_token'];
        $params['query'] = '游戏';
        $params['client'] = 'android';
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->assertArrayHasKey('topics', $content);
            $this->assertTrue(count($content['topics'])>0, $makeMsg('搜索结果不符'));
        });
    }


    /**
     * 安卓
     *
     * /search/user
     *
     * @group prod
     * @covers ::search
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testSearchUserForAndroid() {
        $uri = '/search/user';
        $user = $this->getUser();
        $params = [];
        $params['access_token'] = $user['access_token'];
        $params['query'] = '方便面';
        $params['client'] = 'android';
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->assertArrayHasKey('users', $content);
            $this->assertTrue(count($content['users'])>0, $makeMsg('搜索结果不符'));
        });
    }

    /**
     * ios
     *
     * /search
     *
     * @group prod
     * @covers ::searchAll
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testSearchAllForIOS() {
        $uri = '/search';
        $user = $this->getUser();
        $params = [];
        $params['access_token'] = $user['access_token'];
        $params['query'] = 'cosplay';
        $params['client'] = 'ios';
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkSearchAllFResp($content, $makeMsg);
        });

    }

    /**
     * 小米渠道
     *
     * /search
     *
     * @group prod
     * @covers ::searchAll
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testSearchAllForXiaoMi() {
        $uri = '/search';
        $user = $this->getUser();
        $params = [];
        $params['access_token'] = $user['access_token'];
        $params['query'] = 'cosplay';
        $params['channel'] = 'xiaomi';
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkSearchAllFResp($content, $makeMsg);
        });

    }


    /**
     * ios
     *
     * /search/post
     *
     * @group prod
     * @covers ::search
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testSearchPostForIOS() {
        $uri = '/search/post';
        $user = $this->getUser();
        $params = [];
        $params['access_token'] = $user['access_token'];
        $params['query'] = 'cosplay';
        $params['client'] = 'ios';
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->assertArrayHasKey('posts', $content);
            $this->assertTrue(count($content['posts'])>0, $makeMsg('搜索结果不符'));
        });
    }


    /**
     * ios
     *
     * /search/topic
     *
     * @group prod
     * @covers ::search
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testSearchTopicForIOS() {
        $uri = '/search/topic';
        $user = $this->getUser();
        $params = [];
        $params['access_token'] = $user['access_token'];
        $params['query'] = 'cosplay';
        $params['client'] = 'ios';
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->assertArrayHasKey('topics', $content);
            $this->assertTrue(count($content['topics'])>0, $makeMsg('搜索结果不符'));
        });
    }


    /**
     * ios
     *
     * /search/user
     *
     * @group prod
     * @covers ::search
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testSearchUserForIOS() {
        $uri = '/search/user';
        $user = $this->getUser();
        $params = [];
        $params['access_token'] = $user['access_token'];
        $params['query'] = '方便面';
        $params['client'] = 'ios';
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->assertArrayHasKey('users', $content);
            $this->assertTrue(count($content['users'])>0, $makeMsg('搜索结果不符'));
        });
    }

    /**
     * 小米渠道
     *
     * /search/post
     *
     * @group prod
     * @covers ::search
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testSearchPostForXiaoMi() {
        $uri = '/search/post';
        $user = $this->getUser();
        $params = [];
        $params['access_token'] = $user['access_token'];
        $params['query'] = 'cosplay';
        $params['channel'] = 'xiaomi';
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->assertArrayHasKey('posts', $content);
            $this->assertTrue(count($content['posts'])>0, $makeMsg('搜索结果不符'));
        });
    }

    /**
     * 小米渠道
     *
     * /search/topic
     *
     * @group prod
     * @covers ::search
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testSearchTopicForXiaoMi() {
        $uri = '/search/topic';
        $user = $this->getUser();
        $params = [];
        $params['access_token'] = $user['access_token'];
        $params['query'] = 'cosplay';
        $params['channel'] = 'xiaomi';
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->assertArrayHasKey('topics', $content);
            $this->assertTrue(count($content['topics'])>0, $makeMsg('搜索结果不符'));
        });
    }

    /**
     * 小米渠道
     *
     * /search/user
     *
     * @group prod
     * @covers ::search
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testSearchUserForXiaoMi() {
        $uri = '/search/user';
        $user = $this->getUser();
        $params = [];
        $params['access_token'] = $user['access_token'];
        $params['query'] = '方便面';
        $params['channel'] = 'xiaomi';
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->assertArrayHasKey('users', $content);
            $this->assertTrue(count($content['users'])>0, $makeMsg('搜索结果不符'));
        });
    }

}