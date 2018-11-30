<?php

namespace Lychee\Module\Like\Tests;

use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Like\Entity\RobotLikePostTask;

/**
 * @group \Lychee\Module\Like\RobotService
 */
class RobotServiceTest extends ModuleAwareTestCase {

    private function getService()
    {
        return $this->container()->get('lychee.module.like.robot');
    }

    private function getLikeService()
    {
        return $this->container()->get('lychee.module.like');
    }

    private function getDBConnection()
    {
        return $this->container()->get('doctrine')->getManager()->getConnection();
    }

    private function getRandPostId()
    {
        $sql = 'select p.id from post p 
        inner join recommendable_topic rt on p.topic_id = rt.topic_id 
        order by RAND() limit 10';

        $stm = $this->getDBConnection()->executeQuery($sql);
        $r = $stm->fetchAll(\PDO::FETCH_ASSOC);
        $r = ArrayUtility::columns($r, 'id');
        return $r[0];
    }

    /**
     *
     * @covers ::dispatchLikePostTaskWhenLikeEventHappen
     */
    public function testDispatchLikePostTaskWhenLikeEventHappen()
    {
        $currTime = time();
        $postId = $this->getRandPostId();
        $userId = 1;
        $task = false;
        for ($i = 0; $i<3; $i++) {
            $r = $this->getService()->dispatchLikePostTaskWhenLikeEventHappen($postId, $userId);
            if ($r) {
                $task = $r;
            }
        }

        $this->assertNotFalse($task);
        $this->assertEquals($postId, $task->postId);

        $sql = 'select * from robot_like_post_task where id='.$task->id;
        $r = $this->getDBConnection()->executeQuery($sql)->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals(1, $r['total']);
        $this->assertEquals($postId, $r['post_id']);
        $this->assertGreaterThanOrEqual($currTime, $r['create_time']);
        $this->assertEquals(RobotLikePostTask::WAITING_STATE, $r['state']);
    }

    /**
     *
     * @covers ::likePost
     */
    public function testLikePost()
    {
        $postId = $this->getRandPostId();
        $userId = 1;
        try {
            $this->getLikeService()->cancelLikePost($userId, $postId);
            $task = [];
            $task['postId'] = $postId;
            $this->getService()->likePost($userId, $task);
        } catch (\Exception $e) {}

        $sql = 'select 1 from robot_like_post where liker_id ='.$userId.' and post_id='.$postId;
        $r = $this->getDBConnection()->executeQuery($sql)->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotEmpty($r);
    }
}
