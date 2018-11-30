<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 29/11/2016
 * Time: 5:58 PM
 */

namespace Lychee\Bundle\ApiBundle\Tests\Controller;
use GuzzleHttp\Client;

/**
 * @coversDefaultClass \Lychee\Bundle\ApiBundle\Controller\TopicController
 */

class TopicTest extends Base {


    /**
     * 访问次元
     *
     * /topic/visit
     *
     * @group prod
     * @covers ::visitAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testVisit() {
        $uri = 'topic/visit';
        $user = $this->getUser();
        $params = [];
        $params['topic'] = 26082;
        $params['access_token'] = $user['access_token'];
        $this->handlePost($uri, $params, function ($content, $makeMsg){
            $this->assertTrue($content['result'], $makeMsg($content));
        });
    }


    public function checkListResp($content, $makeMsg) {
        $this->assertArrayHasKey('topics', $content, $makeMsg($content));
        foreach ($content['topics'] as $topic) {
            $this->assertArrayHasKey('id', $topic, $makeMsg($topic));
            $this->assertArrayHasKey('title', $topic, $makeMsg($topic));
        }
    }


    /**
     * 我的次元列表
     *
     * /topic/followees
     *
     * @group prod
     * @covers ::listUserFollowingAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testFollowees() {
        $uri = 'topic/followees';
        $user = $this->getUser();
        $params = [];
        $params['uid'] = $user['uid'];
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkListResp($content, $makeMsg);
        });
    }

}