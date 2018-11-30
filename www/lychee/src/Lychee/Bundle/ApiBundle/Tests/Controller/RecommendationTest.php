<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 29/11/2016
 * Time: 5:58 PM
 */

namespace Lychee\Bundle\ApiBundle\Tests\Controller;

/**
 * @coversDefaultClass \Lychee\Bundle\ApiBundle\Controller\RecommendationController
 */

class RecommendationTest extends Base {


    /**
     * 安卓
     *
     * /recommendation/tabs/posts
     *
     * @group prod
     * @covers ::getTabPosts
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
	public function testGetTabPostsForAndroid() {
        $uri = '/recommendation/tabs/posts';
        $params = [];
        $params['tab'] = '精选';
        $params['client'] = 'android';
        $this->handleRequest($uri, $params, function ($content, $host){
            $this->assertArrayHasKey('posts', $content);
            $this->assertTrue(count($content['posts'])>0);
        });

	}

    /**
     * ios
     *
     * /recommendation/tabs/posts
     *
     * @group prod
     * @covers ::getTabPosts
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testGetTabPostsForIOS() {
        $uri = '/recommendation/tabs/posts';
        $params = [];
        $params['tab'] = '精选';
        $params['client'] = 'ios';
        $this->handleRequest($uri, $params, function ($content, $host){
            $this->assertArrayHasKey('posts', $content);
            $this->assertTrue(count($content['posts'])>0);
        });

    }

    /**
     * 安卓
     *
     * /recommendation/posts/jingxuan
     *
     * @group prod
     * @covers ::getJingxuanPosts
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testGetJingxuanPostsForAndroid() {
        $uri = '/recommendation/posts/jingxuan';
        $params = [];
        $params['client'] = 'android';
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->assertArrayHasKey('posts', $content, $makeMsg('结构不符'));
            $this->assertTrue(count($content['posts'])>0, $makeMsg('结果不符'));
        });
    }

    /**
     * ios
     *
     * /recommendation/posts/jingxuan
     *
     * @group prod
     * @covers ::getJingxuanPosts
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testGetJingxuanPostsForIOS() {
        $uri = '/recommendation/posts/jingxuan';
        $params = [];
        $params['client'] = 'ios';
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->assertArrayHasKey('posts', $content, $makeMsg('结构不符'));
            $this->assertTrue(count($content['posts'])>0, $makeMsg('结果不符'));
        });
    }
}