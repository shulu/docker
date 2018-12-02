<?php
namespace Lychee\Module\Post\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;

/**
 * @group \Lychee\Module\Post\PostService
 */
class PostTest extends ModuleAwareTestCase {



    /**
     * 验证分页
     *
     * @covers ::fetchIdsByTopicIdsOrderByHot
     */
    public function testFetchIdsByTopicIdsOrderByHotPaging() {

        $limit = 50;
        $topicId = 25076;

        $expList = $this->post()->fetchIdsByTopicIdsOrderByHot([$topicId], 0, $limit, $nextCursor);

        $this->assertNotEmpty($expList);
        $cursor = 0;
        $pageList = [];
        while ($cursor<$limit) {
            $r = $this->post()->fetchIdsByTopicIdsOrderByHot([$topicId], $cursor, 10, $cursor);
            if (empty($r)) {
                break;
            }
            $pageList = array_merge($pageList, $r);
        }
        $this->assertEquals($expList, $pageList);
    }


    public function topLikePostPeriodCount($postIds) {
        $sql = "TRUNCATE TABLE `like_post_period_count`;";
        $sql .= "INSERT INTO like_post_period_count (post_id, count) VALUES ";
        $count = time();
        $values = [];
        foreach ($postIds as $key => $postId) {
            $values[] = "(".$postId.", ".($count-$key).")";
        }

        $sql .= implode(',', $values);
        $sql .= ' ON DUPLICATE KEY UPDATE count=VALUES(count)';
        $conn  = $this->getConnection();
        $conn->executeUpdate($sql);
    }
    public function upLikePostCount($postIds) {
        $sql = [];
        $count = time();
        foreach ($postIds as $key => $postId) {
            $sql[] = "update post_counting set liked_count=".($count-$key)."+100 where post_id=".$postId;
        }
        $sql = implode(';', $sql);
        $conn  = $this->getConnection();
        $conn->executeUpdate($sql);
    }

    public function getConnection() {
        $conn  = $this->container()->get('doctrine')->getManager()->getConnection();
        return $conn;
    }


    /**
     * 验证顺序
     *
     * @covers ::fetchIdsByTopicIdsOrderByHot
     */
    public function testFetchIdsByTopicIdsOrderByHotSequence() {
        $limit = 50;
        $topicId = 25076;
        $srcList = $this->post()->fetchIdsByTopicId($topicId, 0, $limit);
        shuffle($srcList);

        $postIds = array_splice($srcList, 0, 3);
        $this->topLikePostPeriodCount($postIds);
        $r = array_splice($srcList, 0, 3);
        $this->upLikePostCount($r);

        $postIds = array_merge($postIds, $r);

        $r = $this->post()->fetchIdsByTopicIdsOrderByHot([$topicId], 0, $limit, $nextCursor);
        $r = array_splice($r, 0, 6);
        $this->assertEquals($postIds, $r);
    }


    /**
     * 验证分页
     *
     * @covers ::fetchIdsByTopicIdsOrderByHot
     */
    public function testFetchPassIdsByTopicIdsOrderByHotPaging() {

        $limit = 50;
        $topicId = 53057;

        $expList = $this->post()->fetchPassIdsByTopicIdsOrderByHot([$topicId], 0, $limit, $nextCursor);

        $this->assertNotEmpty($expList);
        $cursor = 0;
        $pageList = [];
        while ($cursor<$limit) {
            $r = $this->post()->fetchPassIdsByTopicIdsOrderByHot([$topicId], $cursor, $limit, $cursor);
            if (empty($r)) {
                break;
            }
            $pageList = array_merge($pageList, $r);
        }
        $this->assertEquals($expList, $pageList);
    }

    /**
     * 验证顺序
     *
     * @covers ::fetchIdsByTopicIdsOrderByHot
     */
    public function testFetchPassIdsByTopicIdsOrderByHotSequence() {
        $limit = 50;
        $topicId = 53057;
        $srcList = $this->post()->fetchPassIdsByTopicId($topicId, 0, $limit);

        shuffle($srcList);

        $postIds = array_splice($srcList, 0, 3);
        $this->topLikePostPeriodCount($postIds);
        $r = array_splice($srcList, 0, 3);
        $this->upLikePostCount($r);

        $postIds = array_merge($postIds, $r);

        $r = $this->post()->fetchPassIdsByTopicIdsOrderByHot([$topicId], 0, $limit, $nextCursor);
        $r = array_splice($r, 0, 6);
        $this->assertEquals($postIds, $r);
    }


    /**
     * 验证分页
     *
     * @covers ::fetchPassIdsByTopicIdOrderByHot
     */
    public function testFetchPassIdsByTopicIdOrderByHotPaging() {

        $limit = 50;
        $topicId = 54703;

        $expList = $this->post()->fetchPassIdsByTopicIdOrderByHot($topicId, 0, $limit);

        $this->assertNotEmpty($expList);
        $cursor = 0;
        $pageList = [];
        while ($cursor<$limit) {
            $r = $this->post()->fetchPassIdsByTopicIdOrderByHot($topicId, $cursor, $limit, $cursor);
            if (empty($r)) {
                break;
            }
            $pageList = array_merge($pageList, $r);
        }
        $this->assertEquals($expList, $pageList);
    }

    /**
     * 验证顺序
     *
     * @covers ::fetchPassIdsByTopicIdOrderByHot
     */
    public function testFetchPassIdsByTopicIdOrderByHotSequence() {
        $limit = 50;
        $topicId = 54703;
        $srcList = $this->post()->fetchPassIdsByTopicId($topicId, 0, $limit);

        shuffle($srcList);

        $postIds = array_splice($srcList, 0, 3);
        $this->topLikePostPeriodCount($postIds);
        $r = array_splice($srcList, 0, 3);
        $this->upLikePostCount($r);

        $postIds = array_merge($postIds, $r);

        $r = $this->post()->fetchPassIdsByTopicIdOrderByHot($topicId, 0, $limit, $nextCursor);
        $r = array_splice($r, 0, 6);
        $this->assertEquals($postIds, $r);
    }


    /**
     * 验证分页
     *
     * @covers ::fetchIdsByTopicIdOrderByHot
     */
    public function testFetchIdsByTopicIdOrderByHotPaging() {

        $limit = 50;
        $topicId = 53157;

        $expList = $this->post()->fetchIdsByTopicIdOrderByHot($topicId, 0, $limit);

        $this->assertNotEmpty($expList);
        $cursor = 0;
        $pageList = [];
        while ($cursor<$limit) {
            $r = $this->post()->fetchIdsByTopicIdOrderByHot($topicId, $cursor, $limit, $cursor);
            if (empty($r)) {
                break;
            }
            $pageList = array_merge($pageList, $r);
        }
        $this->assertEquals($expList, $pageList);
    }

    /**
     * 验证顺序
     *
     * @covers ::fetchIdsByTopicIdOrderByHot
     */
    public function testFetchIdsByTopicIdOrderByHotSequence() {
        $limit = 50;
        $topicId = 53157;
        $srcList = $this->post()->fetchIdsByTopicId($topicId, 0, $limit);

        shuffle($srcList);

        $postIds = array_splice($srcList, 0, 3);
        $this->topLikePostPeriodCount($postIds);
        $r = array_splice($srcList, 0, 3);
        $this->upLikePostCount($r);

        $postIds = array_merge($postIds, $r);

        $r = $this->post()->fetchIdsByTopicIdOrderByHot($topicId, 0, $limit, $nextCursor);
        $r = array_splice($r, 0, 6);
        $this->assertEquals($postIds, $r);
    }


    public function followTopic($userId, $topicId) {
        $followedBefore = 0;
        $this->container()->get('lychee.module.topic.following')->follow($userId, $topicId, $followedBefore);
    }

    public function createShortVideo($userId, $topicId) {
        $this->followTopic($userId, $topicId);
        $parameter = new \Lychee\Module\Post\PostParameter();
        $content = date('Y-m-d H:i:s').' 发。';
        $parameter->setTopicId($topicId);
        $parameter->setContent($content);
        $videoUrl = $audioUrl = $siteUrl = '';
        $imageUrl = 'http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/e85209d07447398155567673211/7447398155567673213.jpg';
        $videoUrl = 'http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/e85209d07447398155567673211/n0wAX6iwoZ0A.mp4';
        $annotation = array();
        $annotation['video_cover_width'] = 360;
        $annotation['video_cover_height'] = 636;
        $annotation['video_cover'] = $imageUrl;
        $annotation = json_encode($annotation);
        $parameter->setAuthorId($userId);
        $parameter->setAuthorLevel(1);
        $parameter->setResource($imageUrl, $videoUrl, $audioUrl, $siteUrl);
        $parameter->setAnnotation($annotation);
        $parameter->setSvId('7447398155565827538');
        $parameter->setBgmId(1);
        $type = 7;
        $parameter->setType($type);
        $post =  $this->post()->create($parameter);
        return $post;
    }


    public function create($userId, $topicId) {

        $this->followTopic($userId, $topicId);
        $parameter = new \Lychee\Module\Post\PostParameter();

        $content = date('Y-m-d H:i:s').' 发。';
        $parameter->setTopicId($topicId);
        $parameter->setContent($content);

        $videoUrl = $audioUrl = $siteUrl = '';
        $imageUrl = 'http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/e85209d07447398155567673211/7447398155567673213.jpg';

        $annotation = array();
        $annotation = json_encode($annotation);
        $parameter->setAuthorId($userId);
        $parameter->setAuthorLevel(1);
        $parameter->setResource($imageUrl, $videoUrl, $audioUrl, $siteUrl);
        $parameter->setAnnotation($annotation);

        $type = \Lychee\Bundle\CoreBundle\Entity\Post::TYPE_NORMAL;
        $parameter->setType($type);
        $post = $this->post()->create($parameter);
        return $post;
    }


    /**
     * 验证个人图文帖列表,不展示视频
     *
     * @covers ::fetchPlainIdsByAuthorId
     */
    public function testFetchPlainIdsByAuthorIdNoVideo() {
        $userId = 2;
        $post = $this->createShortVideo($userId, 53157);
        $r = $this->post()->fetchPlainIdsByAuthorId($userId, 0, 10);
        $this->assertFalse(in_array($post->id, $r));
    }

    /**
     * 验证个人图文帖列表,展示图文帖
     *
     * @covers ::fetchPlainIdsByAuthorId
     */
    public function testFetchPlainIdsByAuthorIdHasPlainPost() {
        $userId = 2;
        $post = $this->create($userId, 53157);
        $r = $this->post()->fetchPlainIdsByAuthorId($userId, 0, 10);
        $this->assertTrue(in_array($post->id, $r));
    }

    /**
     * 验证个人图文帖列表，不展示私有次元的帖子
     *
     * @covers ::fetchPlainIdsByAuthorIdInPublicTopic
     */
    public function testFetchPlainIdsByAuthorIdInPublicTopicNoPrivatePost() {
        $topicId = 56582;

        $topic = $this->topic()->fetchOne($topicId);
        $topic->private = 1;
        $this->topic()->update($topic);

        $userId = 2;
        $post = $this->create($userId, $topicId);
        $r = $this->post()->fetchPlainIdsByAuthorIdInPublicTopic($userId, 0, 10);
        $this->assertFalse(in_array($post->id, $r));
    }

    /**
     * 验证个人图文帖列表，正常展示
     *
     * @covers ::fetchPlainIdsByAuthorIdInPublicTopic
     */
    public function testFetchPlainIdsByAuthorIdInPublicTopicHasPlainPost() {
        $topicId = 53157;
        $userId = 2;
        $post = $this->create($userId, $topicId);
        $r = $this->post()->fetchPlainIdsByAuthorIdInPublicTopic($userId, 0, 10);
        $this->assertTrue(in_array($post->id, $r));
    }

    /**
     * 验证个人图文帖列表，不展示视频
     *
     * @covers ::fetchPlainIdsByAuthorIdInPublicTopic
     */
    public function testFetchPlainIdsByAuthorIdInPublicTopicNoVideo() {
        $topicId = 53157;
        $userId = 2;
        $post = $this->createShortVideo($userId, $topicId);
        $r = $this->post()->fetchPlainIdsByAuthorIdInPublicTopic($userId, 0, 10);
        $this->assertFalse(in_array($post->id, $r));
    }

    /**
     * 验证个人图文帖列表，不展示未审核
     *
     * @covers ::fetchPassPlainIdsByAuthorIdInPublicTopic
     */
    public function testFetchPassPlainIdsByAuthorIdInPublicTopicWhenUntreatedAudit() {
        $userId = 2;
        $topicId = $this->getRandRecTopicId();
        $post = $this->create($userId, $topicId);
        $this->post()->initAuditStatus($post->id);
        $r = $this->post()->fetchPassPlainIdsByAuthorIdInPublicTopic($userId, 0, 10);
        $this->assertFalse(in_array($post->id, $r));
    }

    /**
     * 验证个人图文帖列表，不展示审核不通过
     *
     * @covers ::fetchPassPlainIdsByAuthorIdInPublicTopic
     */
    public function testFetchPassPlainIdsByAuthorIdInPublicTopicWhenPassAudit() {
        $userId = 2;
        $postIds = $this->post()->fetchIdsByAuthorId($userId, 0, 10);
        $postId = reset($postIds);
        $r = $this->post()->fetchPassPlainIdsByAuthorIdInPublicTopic($userId, 0, 10);
        $this->assertFalse(in_array($postId, $r));
    }

    /**
     * 验证个人图文帖列表，翻页
     *
     * @covers ::fetchPlainIdsByAuthorIdInPublicTopic
     */
    public function testFetchPlainIdsByAuthorIdInPublicTopicPageing() {
        $userId = 2;
        $cursor = 0;
        for ($i=0; $i<3; $i++) {
            $this->post()->fetchPlainIdsByAuthorIdInPublicTopic($userId, $cursor, 2, $nextCursor);
            $this->assertNotEquals($cursor, $nextCursor);
            $cursor = $nextCursor;
        }
    }

    /**
     * 验证个人帖子列表
     *
     * @covers ::fetchIdsByAuthorId
     */
    public function testFetchIdsByAuthorId() {
        $userId = 2;
        $post = $this->createShortVideo($userId, 53157);
        $r = $this->post()->fetchIdsByAuthorId($userId, 0, 10);
        $this->assertTrue(in_array($post->id, $r));
    }

    /**
     * 验证个人帖子列表,翻页
     *
     * @covers ::fetchIdsByAuthorId
     */
    public function testFetchIdsByAuthorIdPaging() {
        $userId = 2;
        $cursor = 0;
        for ($i=0; $i<3; $i++) {
            $this->post()->fetchIdsByAuthorId($userId, $cursor, 2, $nextCursor);
            $this->assertNotEquals($cursor, $nextCursor);
            $cursor = $nextCursor;
        }
    }

    /**
     * 短视频封面图迁移
     *
     * @covers ::moveShortVideoCoverStoreById
     */
    public function testMoveShortVideoCoverStoreById() {
        $userId = 2;
        $post = $this->createShortVideo($userId, 53157);

        $cover1 = $post->imageUrl;
        $cover1MD5  = md5(file_get_contents($cover1));

        $r = $this->post()->moveShortVideoCoverStoreById($post->id);
        $this->assertTrue($r);

        $post = $this->post()->fetchOne($post->id);

        $cover2 = $post->imageUrl;
        $cover2MD5  = md5(file_get_contents($cover1));

        $this->assertNotEquals($cover1, $cover2);
        $this->assertEquals($cover1MD5, $cover2MD5);

        $annotation = json_decode($post->annotation, true);
        $this->assertEquals($annotation['video_cover'], $cover2, var_export($post->annotation, true));
    }

    /**
     * 短视频封面图迁移
     *
     * @covers ::moveShortVideoCoverStoreById
     */
    public function testMoveShortVideoCoverStoreByIdUnique() {
        $r = $this->post()->moveShortVideoCoverStoreById(131795026395137);
        $this->assertFalse($r);
    }

    /**
     * 随机热门视频
     *
     * @covers ::getTopHotShortVideoIdsRand
     */
    public function testGetTopHotShortVideoIdsRand() {
        $count = 2;
        $preRs = [];
        for ($i=0; $i<3; $i++) {
            $r = $this->post()->getTopHotShortVideoIdsRand($count);
            $this->assertNotEquals($preRs, $r);
            foreach ($r as $id) {
                $counting =  $this->post()->fetchOneCounting($id);
                $this->assertGreaterThan(2, $counting->likedCount);
            }
        }
    }

    /**
     * 验证个人短视频列表
     *
     * @covers ::fetchShortVideoIdsByAuthorId
     */
    public function testFetchShortVideoIdsByAuthorId() {
        $topicId = 53157;
        $userId = 2;
        $post = $this->createShortVideo($userId, $topicId);
        $r = $this->post()->fetchShortVideoIdsByAuthorId($userId, 0, 10);
        $this->assertTrue(in_array($post->id, $r));
    }


    private function getRandRecTopicId() {
        $r = $this->recommendation()->fetchRecommendableTopicIds();
        return reset($r);
    }


    /**
     * 验证个人短视频列表，未审核不可见
     *
     * @covers ::fetchPassShortVideoIdsByAuthorId
     */
    public function testFetchPassShortVideoIdsByAuthorIdWhenUntreatedAudit() {
        $topicId = $this->getRandRecTopicId();
        $userId = 2;
        $post = $this->createShortVideo($userId, $topicId);
        $this->post()->initAuditStatus($post->id);
        $r = $this->post()->fetchPassShortVideoIdsByAuthorId($userId, 0, 10);
        $this->assertFalse(in_array($post->id, $r));
    }


    /**
     * 验证个人短视频列表，审核不过不可见
     *
     * @covers ::fetchPassShortVideoIdsByAuthorId
     */
    public function testFetchPassShortVideoIdsByAuthorIdWhenRejectAudit() {
        $userId = 2;
        $postIds = $this->post()->fetchShortVideoIdsByAuthorId($userId, 0, 10);
        $postId = reset($postIds);
        $this->post()->rejectAudit([$postId]);
        $r = $this->post()->fetchPassShortVideoIdsByAuthorId($userId, 0, 10);
        $this->assertFalse(in_array($postId, $r));
    }

    /**
     * 验证个人短视频列表，审核通过可见
     *
     * @covers ::fetchPassShortVideoIdsByAuthorId
     */
    public function testFetchPassShortVideoIdsByAuthorIdWhenPassAudit() {
        $userId = 2;
        $postIds = $this->post()->fetchShortVideoIdsByAuthorId($userId, 0, 10);
        $postId = reset($postIds);
        $this->post()->passAudit([$postId]);
        $r = $this->post()->fetchPassShortVideoIdsByAuthorId($userId, 0, 10);
        $this->assertTrue(in_array($postId, $r));
    }

    /**
     * 验证个人短视频列表,翻页
     *
     * @covers ::fetchShortVideoIdsByAuthorId
     */
    public function testFetchShortVideoIdsByAuthorIdPaging() {
        $userId = 2;
        $cursor = 0;
        for ($i=0; $i<3; $i++) {
            $this->post()->fetchShortVideoIdsByAuthorId($userId, $cursor, 2, $nextCursor);
            $this->assertNotEquals($cursor, $nextCursor);
            $cursor = $nextCursor;
        }
    }


    /**
     * 验证个人审核通过短视频列表,翻页
     *
     * @covers ::fetchPassShortVideoIdsByAuthorId
     */
    public function testFetchPassShortVideoIdsByAuthorIdPaging() {
        $userId = 2;
        $cursor = 0;
        for ($i=0; $i<3; $i++) {
            $this->post()->fetchPassShortVideoIdsByAuthorId($userId, $cursor, 2, $nextCursor);
            $this->assertNotEquals($cursor, $nextCursor);
            $cursor = $nextCursor;
        }
    }
}