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
 * @coversDefaultClass \Lychee\Bundle\ApiBundle\Controller\UGSVController
 */

class UGSVTest extends Base {


    /**
     *
     * /ugsv/isopen
     *
     * @group prod
     * @covers ::isOpenAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
	public function testIsOpen() {
        $uri = 'ugsv/isopen';
        $user = $this->getUser();
        $params = [];
        $params['uid'] = $user['uid'];
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->assertArrayHasKey('result', $content, $makeMsg($content));
            $this->assertArrayHasKey('apply_url', $content, $makeMsg($content));
        });
	}

    /**
     *
     * /ugsv/video
     *
     * @group prod
     * @covers ::getVideoAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testGetVideo() {
        $uri = 'ugsv/video';
        $user = $this->getUser();
        $params = [];
        $params['id'] = '126976415560705';
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->assertArrayHasKey('id', $content, $makeMsg($content));
        });

    }


    /**
     * 看他人
     *
     * /ugsv/video/timeline/user
     *
     * @group prod
     * @covers ::listVideosByUserAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testListVideosByUserOther() {
        $uri = 'ugsv/video/timeline/user';
        $params = [];
        $params['uid'] = '2438554';
        $params['count'] = 20;
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkListResp($content, $makeMsg);
        });
    }

    /**
     * 看自己
     *
     * /ugsv/video/timeline/user
     *
     * @group prod
     * @covers ::listVideosByUserAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testListVideosByUserMySelf() {
        $uri = 'ugsv/video/timeline/user';
        $user = $this->getUser();
        $params = [];
        $params['uid'] = $user['uid'];
        $params['access_token'] = $user['access_token'];
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkListResp($content, $makeMsg);
        });
    }


    public function checkListResp($content, $makeMsg) {
        $this->assertArrayHasKey('posts', $content, $makeMsg($content));
        foreach ($content['posts'] as $post) {
            $this->assertEquals('short_video', $post['type'], $makeMsg($post['type']));
            $this->assertArrayHasKey('id', $post, $makeMsg($post));
        }
    }


    /**
     * 没登录查看
     *
     * /ugsv/video/recs
     *
     * @group prod
     * @covers ::listVideosByRecAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testListVideosByRecNoLogin() {
        $uri = 'ugsv/video/recs';
        $params = [];
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkListResp($content, $makeMsg);
        });
    }

    /**
     * 已登录查看
     *
     * /ugsv/video/recs
     *
     * @group prod
     * @covers ::listVideosByRecAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testListVideosByRecWithLogin() {
        $uri = 'ugsv/video/recs';
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
     * /ugsv/bgm/hots
     *
     * @group prod
     * @covers ::hotBGMListAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testHotBGMListAction() {
        $uri = 'ugsv/bgm/hots';
        $params = [];
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->assertArrayHasKey('list', $content, $makeMsg($content));
        });

    }

    /**
     *
     * /ugsv/video/signature
     *
     * @group prod
     * @covers ::signatureAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testSignature() {
        $uri = 'ugsv/video/signature';
        $user = $this->getUser();
        $params = [];
        $params['access_token'] = $user['access_token'];
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->assertArrayHasKey('result', $content, $makeMsg($content));
        });
    }


    /**
     * 没登录查看
     *
     * /ugsv/video/newly
     *
     * @group prod
     * @covers ::listVideosByNewlyAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testNewlyVideosNoLogin() {
        $uri = 'ugsv/video/newly';
        $params = [];
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkListResp($content, $makeMsg);
        });
    }

    /**
     * 已登录查看
     *
     * /ugsv/video/newly
     *
     * @group prod
     * @covers ::listVideosByNewlyAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testNewlyVideosWithLogin() {
        $uri = 'ugsv/video/newly';
        $user = $this->getUser();
        $params = [];
        $params['uid'] = $user['uid'];
        $params['access_token'] = $user['access_token'];
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkListResp($content, $makeMsg);
        });
    }

    /**
     * 已登录查看
     *
     * /ugsv/video/hots
     *
     * @group prod
     * @covers ::listVideosByHotsAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testHotsVideosWithLogin() {
        $uri = 'ugsv/video/hots';
        $user = $this->getUser();
        $params = [];
        $params['uid'] = $user['uid'];
        $params['access_token'] = $user['access_token'];
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkListResp($content, $makeMsg);
        });
    }

    /**
     * 没登录查看
     *
     * /ugsv/video/hots
     *
     * @group prod
     * @covers ::listVideosByHotsAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testHotsVideosNoLogin() {
        $uri = 'ugsv/video/hots';
        $params = [];
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkListResp($content, $makeMsg);
        });
    }

    /**
     * 没登录查看
     *
     * /ugsv/video/recs/part2
     *
     * @group prod
     * @covers ::listVideosByRecPart2Action
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testRecVideosPart2NoLogin() {
        $uri = 'ugsv/video/recs/part2';
        $params = [];
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkListResp($content, $makeMsg);
        });
    }

    /**
     * 已登录查看
     *
     * /ugsv/video/recs/part2
     *
     * @group prod
     * @covers ::listVideosByRecPart2Action
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testRecVideosPart2WithLogin() {
        $uri = 'ugsv/video/recs/part2';
        $user = $this->getUser();
        $params = [];
        $params['uid'] = $user['uid'];
        $params['access_token'] = $user['access_token'];
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkListResp($content, $makeMsg);
        });
    }

    /**
     * 已登录查看
     *
     * /ugsv/video/recs/part1
     *
     * @group prod
     * @covers ::listVideosByRecPart1Action
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testRecVideosPart1WithLogin() {
        $uri = 'ugsv/video/recs/part1';
        $user = $this->getUser();
        $params = [];
        $params['uid'] = $user['uid'];
        $params['access_token'] = $user['access_token'];
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkListResp($content, $makeMsg);
        });
    }

    /**
     * 没登录查看
     *
     * /ugsv/video/recs/part1
     *
     * @group prod
     * @covers ::listVideosByRecPart1Action
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testRecVideosPart1NoLogin() {
        $uri = 'ugsv/video/recs/part1';
        $params = [];
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->checkListResp($content, $makeMsg);
        });
    }



    /**
     * 获取配置
     *
     * /ugsv/config
     *
     * @group prod
     * @covers ::getConfig
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testConfig() {
        $uri = 'ugsv/config';
        $params = [];
        $this->handleRequest($uri, $params, function ($content, $makeMsg){
            $this->assertArrayHasKey('rec_video_part1_count', $content, $makeMsg($content));
            $this->assertArrayHasKey('rec_video_part2_count', $content, $makeMsg($content));
        });
    }

    /**
     * 统计短视频数量
     *
     */
    public function testRecVideosPart1Count() {
        $count = 0;
        $cursor = 0;
        $uri = 'ugsv/video/recs/part1';
        $params = [];
        $params['count'] = 20;

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

        $this->assertEquals(450, $count);
    }


    /**
     * 统计ios短视频数量
     *
     */
    public function testRecVideosPart1CountForIOS() {
        $count = 0;
        $cursor = 0;
        $uri = 'ugsv/video/recs/part1';
        $params = [];
        $params['count'] = 20;
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

        $this->assertEquals(450, $count);
    }
}