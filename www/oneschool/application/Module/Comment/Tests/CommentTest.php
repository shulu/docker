<?php
namespace Lychee\Module\Comment\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;

/**
 * Class CommentTest
 * @package Lychee\Module\Comment\Tests
 *
 * @group \Lychee\Module\Comment\CommentService
 */
class CommentTest extends ModuleAwareTestCase {


    public function getConnection() {
        $conn  = $this->container()->get('doctrine')->getManager()->getConnection();
        return $conn;
    }

    /**
     * 按点赞数降序排序取top n为热评
     *
     * @covers ::fetchHotIdsByPostId
     */
    public function testFetchHotIdsByPostIdWithTop()
    {
        $userId = 1;
        $r = $this->post()->getTopNewLyShortVideoIdsRand(1);
        $postId = reset($r);
        $commentIds = [];
        for ($i=0; $i<5; $i++) {
            $r =  $this->comment()->create($postId, $userId, '测试评论'.date('YmdHis'),
                0, '', '', '', '');
            $commentIds[] = $r->id;
        }

        $commentId = reset($commentIds);
        $sql = "update comment set liked_count=".time()." where id=".$commentId;
        $this->getConnection()->executeUpdate($sql);

        $r = $this->comment()->fetchHotIdsByPostId($postId, 3, 0, 1);
        $this->assertEquals($commentId, reset($r));
    }

    /**
     * 热评点赞数有下限
     *
     * @covers ::fetchHotIdsByPostId
     */
    public function testFetchHotIdsByPostIdMinLikedCount()
    {
        $userId = 1;
        $r = $this->post()->getTopNewLyShortVideoIdsRand(1);
        $postId = reset($r);
        $commentIds = [];
        for ($i=0; $i<5; $i++) {
            $r =  $this->comment()->create($postId, $userId, '测试评论'.date('YmdHis'),
                0, '', '', '', '');
            $commentIds[] = $r->id;
        }

        $hotId = array_shift($commentIds);
        $sql = "update comment set liked_count=2 where id in (".implode(',', $commentIds).')';
        $this->getConnection()->executeUpdate($sql);

        $sql = "update comment set liked_count=3 where id = ".$hotId;
        $this->getConnection()->executeUpdate($sql);


        $r = $this->comment()->fetchHotIdsByPostId($postId, 3, 0, 10);
        $this->assertTrue(in_array($hotId, $r));
        $this->assertEmpty(array_intersect($r, $commentIds));


        $sql = "select 1  from  comment where liked_count >= 3 and id in (".implode(',', $commentIds).')';
        $r = $this->getConnection()->executeQuery($sql);
        $r = $r->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertEmpty($r);

    }


}