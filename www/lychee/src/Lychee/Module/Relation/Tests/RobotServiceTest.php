<?php

namespace Lychee\Module\Relation\Tests;

use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Like\Entity\RobotLikePostTask;
use Lychee\Module\Relation\Entity\RobotUserFollowTask;

/**
 * @group \Lychee\Module\Relation\RobotService
 */
class RobotServiceTest extends ModuleAwareTestCase {

    private function getService()
    {
        return $this->container()->get('lychee.module.relation.robot');
    }

    private function getLikeService()
    {
        return $this->container()->get('lychee.module.like');
    }

    private function getPostService()
    {
        return $this->container()->get('lychee.module.post');
    }

    private function getRelationService()
    {
        return $this->container()->get('lychee.module.relation');
    }

    private function getDBConnection()
    {
        return $this->container()->get('doctrine')->getManager()->getConnection();
    }

    private function getRandPostId()
    {
        $sql = 'select p.id from post p 
        inner join recommendable_topic rt on p.topic_id = rt.topic_id and p.deleted=0
        order by RAND() limit 10';

        $stm = $this->getDBConnection()->executeQuery($sql);
        $r = $stm->fetchAll(\PDO::FETCH_ASSOC);
        $r = ArrayUtility::columns($r, 'id');
        return $r[0];
    }

    private function getRandPostIdsByUserId($userId, $count)
    {
        $sql = 'select p.id from post p 
            inner join recommendable_topic rt on p.topic_id = rt.topic_id 
            where p.deleted=0 and p.author_id=' .intval($userId)
            .' order by RAND() limit '.$count;

        $stm = $this->getDBConnection()->executeQuery($sql);
        $r = $stm->fetchAll(\PDO::FETCH_ASSOC);
        $r = ArrayUtility::columns($r, 'id');
        return $r;
    }


    private function findTask($taskId)
    {
        $sql = 'select * from robot_user_follow_task where id='.$taskId;
        $r = $this->getDBConnection()->executeQuery($sql)->fetch(\PDO::FETCH_ASSOC);
        return $r;
    }

    /**
     *
     * @covers ::dispatchFollowUserTaskWhenPostEventHappen
     */
    public function testDispatchFollowUserTaskWhenPostEventHappen()
    {
        $currTime = time();
        $postId = $this->getRandPostId();
        $task = $this->getService()->dispatchFollowUserTaskWhenPostEventHappen($postId);

        $post = $this->getPostService()->fetchOne($postId);
        $targetId = $post->authorId;

        $this->assertNotFalse($task);
        $this->assertEquals($targetId, $task->targetId);

        $r = $this->findTask($task->id);
        $this->assertEquals(1, $r['total']);
        $this->assertEquals($targetId, $r['target_id']);
        $this->assertGreaterThanOrEqual($currTime, $r['create_time']);
        $this->assertEquals(RobotUserFollowTask::WAITING_STATE, $r['state']);
    }

    /**
     *
     * @covers ::followUser
     */
    public function testFollowUser()
    {
        $postId = $this->getRandPostId();
        $userId = 1;

        $post = $this->getPostService()->fetchOne($postId);
        $targetId = $post->authorId;
        try {
            $this->getRelationService()->makeUserUnfollowAnother($userId, $targetId);
            $task = [];
            $task['target_id'] = $targetId;
            $this->getService()->followUser($userId, $task);
        } catch (\Exception $e) {}

        $sql = 'select 1 from robot_user_following where follower_id ='.$userId.' and followee_id='.$targetId;
        $r = $this->getDBConnection()->executeQuery($sql)->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotEmpty($r);
    }

    /**
     *
     * @covers ::dispatchFollowUserTaskWhenLikeEventHappen
     */
    public function testDispatchFollowUserTaskWhenLikeEventHappenWithSamePost()
    {
        $currTime = time();
        $postId = $this->getRandPostId();
        $post = $this->getPostService()->fetchOne($postId);
        $targetId = $post->authorId;
        $userId = 1;
        $task = false;
        for ($i = 0; $i<10; $i++) {
            $r = $this->getService()->dispatchFollowUserTaskWhenLikeEventHappen($postId, $userId);
            if ($r) {
                $task = $r;
            }
        }
        $this->assertNotFalse($task);

        $this->assertEquals($targetId, $task->targetId,
            'Failed asserting at line:'.__LINE__);

        $r = $this->findTask($task->id);
        $this->assertEquals(1, $r['total'],
            'Failed asserting at line:'.__LINE__);

        $this->assertEquals($targetId, $r['target_id'],
            'Failed asserting at line:'.__LINE__);

        $this->assertGreaterThanOrEqual($currTime, $r['create_time'],
            'Failed asserting at line:'.__LINE__);

        $this->assertEquals(RobotUserFollowTask::WAITING_STATE, $r['state'],
            'Failed asserting at line:'.__LINE__);
    }

    /**
     *
     * @covers ::dispatchFollowUserTaskWhenLikeEventHappen
     */
    public function testDispatchFollowUserTaskWhenLikeEventHappenWithDifferencePost()
    {
        $currTime = time();
        $userId = 1;
        $postIds = $this->getRandPostIdsByUserId($userId, 10);
        $task = false;
        foreach ($postIds as $postId) {
            $r = $this->getService()->dispatchFollowUserTaskWhenLikeEventHappen($postId, $userId);
            if ($r) {
                $task = $r;
            }
        }

        $targetId = $userId;
        $this->assertNotFalse($task);
        $this->assertEquals($targetId, $task->targetId,
            'Failed asserting at line:'.__LINE__);

        $r = $this->findTask($task->id);
        $this->assertEquals(1, $r['total'],
            'Failed asserting at line:'.__LINE__);

        $this->assertEquals($targetId, $r['target_id'],
            'Failed asserting at line:'.__LINE__);

        $this->assertGreaterThanOrEqual($currTime, $r['create_time'],
            'Failed asserting at line:'.__LINE__);

        $this->assertEquals(RobotUserFollowTask::WAITING_STATE, $r['state'],
            'Failed asserting at line:'.__LINE__);
    }

    /**
     *
     * @covers ::dispatchFollowUserTaskWhenFollowUserEventHappen
     */
    public function testDispatchFollowUserTaskWhenFollowUserEventHappen()
    {
        $currTime = time();
        $targetId = 3;
        $userId = 1;
        $task = false;
        for ($i = 0; $i<3; $i++) {
            $r = $this->getService()->dispatchFollowUserTaskWhenFollowUserEventHappen($userId, $targetId);
            if ($r) {
                $task = $r;
            }
        }
        $this->assertNotFalse($task);

        $this->assertEquals($targetId, $task->targetId,
            'Failed asserting at line:'.__LINE__);

        $r = $this->findTask($task->id);
        $this->assertEquals(1, $r['total'],
            'Failed asserting at line:'.__LINE__);

        $this->assertEquals($targetId, $r['target_id'],
            'Failed asserting at line:'.__LINE__);

        $this->assertGreaterThanOrEqual($currTime, $r['create_time'],
            'Failed asserting at line:'.__LINE__);

        $this->assertEquals(RobotUserFollowTask::WAITING_STATE, $r['state'],
            'Failed asserting at line:'.__LINE__);
    }

}
