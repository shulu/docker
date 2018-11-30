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
 * @coversDefaultClass \Lychee\Bundle\ApiBundle\Controller\PostController
 */

class PostTest extends Base {

    public $cursor = 0;

    /**
     *
     * 看自己
     *
     * /post/timeline/user
     *
     * @group prod
     * @covers ::listPostsByUserAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
	public function testMySelfPostList() {
        $uri = '/post/timeline/user';
        $user = $this->getUser();
        $params = [];
        $params['uid'] = $user['uid'];
        $params['access_token'] = $user['access_token'];
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkListResp($content, $makeMsg);
        });
	}

    /**
     *
     * 看别人，安卓
     *
     * /post/timeline/user
     *
     * @group prod
     * @covers ::listPostsByUserAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testOtherPostListForAndroid() {
        $uri = '/post/timeline/user';
        $user = $this->getUser();
        $params = [];
        $params['uid'] = 2481634;
        $params['access_token'] = $user['access_token'];
        $params['client'] = 'android';
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkListResp($content, $makeMsg);
        });
    }

    /**
     * 看自己
     *
     * /post/plain/timeline/user
     *
     * @group prod
     * @covers ::listPlainsByUserAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testMySelfPlainPostList() {
        $uri = '/post/plain/timeline/user';
        $user = $this->getUser();
        $params = [];
        $params['uid'] = $user['uid'];
        $params['access_token'] = $user['access_token'];
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->assertArrayHasKey('posts', $content, $makeMsg($content));
            $post = reset($content['posts']);
            $this->assertArrayHasKey('id', $post, $makeMsg($post));
            foreach ($content['posts'] as $post) {
                $this->assertNotEquals('short_video', $post['type'], $makeMsg($post['type']));
            }
        });

    }


    /**
     *
     * 看别人，ios
     *
     * /post/timeline/user
     *
     * @group prod
     * @covers ::listPostsByUserAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testOtherPostListForIOS() {
        $uri = '/post/timeline/user';
        $user = $this->getUser();
        $params = [];
        $params['uid'] = 2481634;
        $params['access_token'] = $user['access_token'];
        $params['client'] = 'ios';
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkListResp($content, $makeMsg);
        });
    }



    /**
     *
     * 看别人
     *
     * /post/plain/timeline/user
     *
     * @group prod
     * @covers ::listPlainsByUserAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testOtherPlainPostList() {
        $uri = '/post/plain/timeline/user';
        $user = $this->getUser();
        $params = [];
        $params['uid'] = 89239;
        $params['access_token'] = $user['access_token'];
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->assertArrayHasKey('posts', $content, $makeMsg($content));
            $post = reset($content['posts']);
            $this->assertArrayHasKey('id', $post, $makeMsg($post));
            foreach ($content['posts'] as $post) {
                $this->assertNotEquals('short_video', $post['type'], $makeMsg($post['type']));
            }
        });

    }


    /**
     *
     * 不登录
     *
     * /post/plain/timeline/user
     *
     * @group prod
     * @covers ::listPlainsByUserAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testPlainPostListNoLogin() {
        $uri = '/post/plain/timeline/user';
        $user = $this->getUser();
        $params = [];
        $params['uid'] = 89239;
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->assertArrayHasKey('posts', $content, $makeMsg($content));
            $post = reset($content['posts']);
            $this->assertArrayHasKey('id', $post, $makeMsg($post));
            foreach ($content['posts'] as $post) {
                $this->assertNotEquals('short_video', $post['type'], $makeMsg($post['type']));
            }
        });

    }

    public function checkListResp($content, $makeMsg) {
        $this->assertArrayHasKey('posts', $content, $makeMsg($content));
        foreach ($content['posts'] as $post) {
            $this->assertArrayHasKey('id', $post, $makeMsg($post));
        }
    }


    /**
     *
     * 未登录
     *
     * /post/hots/topic
     *
     * @group prod
     * @covers ::getHotPostsByTopicAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testTopicHotPostListNoLogin() {
        $uri = '/post/hots/topic';
        $params = [];
        $params['tid'] = 25076;
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkListResp($content, $makeMsg);
        });
    }



    /**
     * 统计次元热门帖子数量（android）
     *
     */
    public function testTopicHotPostCountForAndroid() {
        $count = 0;
        $cursor = 0;
        $uri = '/post/hots/topic';
        $params = [];
        $params['count'] = 20;
        $params['tid'] = 54723;

        $httpClient = new Client([
            'base_uri' => 'http://api.ciyo.cn/',
        ]);

        $postIds = [];
        do {
            $olderCursor = $cursor;
            $params['cursor'] = $cursor;
            $response =  $httpClient->get($uri, ['query' => $params]);
            $content = $response->getBody()->getContents();
            $content = json_decode($content, true);
            $ids =  \Lychee\Component\Foundation\ArrayUtility::columns($content['posts'], 'id');
            $repeatIds = array_intersect($postIds, $ids);
            $this->assertEmpty($repeatIds, '旧游标：'.$olderCursor.' 与 新游标：'.$cursor.' 出现重复，重复id：'.implode(',', $repeatIds));
            $postIds = array_merge($postIds, $ids);
            usleep(100000);
            $count += count($content['posts']);
            $this->assertLessThanOrEqual(1000, $count);
            $cursor = $content['next_cursor'];
        } while ($cursor);

        $this->pre($count);
    }

    /**
     * 统计次元热门帖子数量（ios）
     *
     */
    public function testTopicHotPostCountForIOS() {
        $count = 0;
        $cursor = 0;
        $uri = '/post/hots/topic';
        $params = [];
        $params['count'] = 20;
        $params['tid'] = 54723;
        $params['client'] = 'ios';

        $httpClient = new Client([
            'base_uri' => 'http://api.ciyo.cn/',
        ]);

        $postIds = [];
        do {
            $olderCursor = $cursor;
            $params['cursor'] = $cursor;
            $response =  $httpClient->get($uri, ['query' => $params]);
            $content = $response->getBody()->getContents();
            $content = json_decode($content, true);
            $ids =  \Lychee\Component\Foundation\ArrayUtility::columns($content['posts'], 'id');
            $repeatIds = array_intersect($postIds, $ids);
            $this->assertEmpty($repeatIds, '旧游标：'.$olderCursor.' 与 新游标：'.$cursor.' 出现重复，重复id：'.implode(',', $repeatIds));
            $postIds = array_merge($postIds, $ids);
            usleep(100000);
            $count += count($content['posts']);
            $this->assertLessThanOrEqual(1000, $count);
            $cursor = $content['next_cursor'];
        } while ($cursor);

        $this->pre($count);
    }

    /**
     *
     * 翻页
     *
     * /post/hots/topic
     *
     * @group prod
     * @covers ::getHotPostsByTopicAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testTopicHotPostListPaging() {
        $uri = '/post/hots/topic';
        $params = [];
        $params['tid'] = 54723;
        $params['count'] = 5;
        for ($i=0; $i<5; $i++) {
            $params['cursor'] = $this->cursor;
            $resp = $this->handleRequest($uri, $params, function ($content, $makeMsg){
                $this->checkListResp($content, $makeMsg);
                $this->assertNotEquals($this->cursor, $content['next_cursor'], $makeMsg($content));
            });
            $resp = $resp->getBody()->getContents();
            $resp = json_decode($resp, true);
            $this->cursor = $resp['next_cursor'];
        }
    }
    /**
     *
     * 已登录
     *
     * /post/hots/topic
     *
     * @group prod
     * @covers ::getHotPostsByTopicAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testTopicHotPostListWithLogin() {
        $uri = '/post/hots/topic';
        $user = $this->getUser();
        $params = [];
        $params['access_token'] = $user['access_token'];
        $params['tid'] = 25076;
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkListResp($content, $makeMsg);
        });
    }

    /**
     *
     * 未登录
     *
     * /post/newly/topic
     *
     * @group prod
     * @covers ::getNewLyPostsByTopicAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testTopicNewlyPostListNoLogin() {
        $uri = '/post/newly/topic';
        $params = [];
        $params['tid'] = 25076;
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkListResp($content, $makeMsg);
        });
    }


    /**
     *
     * 翻页
     *
     * /post/newly/topic
     *
     * @group prod
     * @covers ::getNewLyPostsByTopicAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testTopicNewlyPostListPaging() {
        $uri = '/post/newly/topic';
        $this->cursor = 0;
        $params = [];
        $params['tid'] = 25076;
        $params['count'] = 5;
        for ($i=0; $i<5; $i++) {
            $this->resp = [];
            $params['cursor'] = $this->cursor;
            $resp =   $this->handleRequest($uri, $params, function ($content, $makeMsg){
                $this->checkListResp($content, $makeMsg);
                $this->assertNotEquals($this->cursor, $content['next_cursor'], $makeMsg($content));
            });
            $resp = $resp->getBody()->getContents();
            $resp = json_decode($resp, true);
            $this->cursor = $resp['next_cursor'];
        }
    }

    /**
     *
     * 已登录
     *
     * /post/newly/topic
     *
     * @group prod
     * @covers ::getNewLyPostsByTopicAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testTopicNewlyPostListWithLogin() {
        $uri = '/post/newly/topic';
        $user = $this->getUser();
        $params = [];
        $params['access_token'] = $user['access_token'];
        $params['tid'] = 25076;
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkListResp($content, $makeMsg);
        });
    }

    /**
     *
     * 关注的用户帖子列表
     *
     * /post/timeline/following
     *
     * @group prod
     * @covers ::timelineAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testFollowingTimeline() {
        $uri = '/post/timeline/following';
        $user = $this->getUser();
        $params = [];
        $params['access_token'] = $user['access_token'];
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkListResp($content, $makeMsg);
        });
    }

}