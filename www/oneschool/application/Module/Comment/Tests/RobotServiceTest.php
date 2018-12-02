<?php

namespace Lychee\Module\Comment\Tests;

use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Comment\Entity\RobotCommentTask;
use Lychee\Module\Like\Entity\RobotLikePostTask;
use Lychee\Module\Relation\Entity\RobotUserFollowTask;

/**
 * @group \Lychee\Module\Comment\RobotService
 */
class RobotServiceTest extends ModuleAwareTestCase {

    private function getRecommendationService()
    {
        return $this->container()->get('lychee.module.recommendation');
    }

    private function getService()
    {
        return $this->container()->get('lychee.module.comment.robot');
    }

    private function getPostService()
    {
        return $this->container()->get('lychee.module.post');
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
        $sql = 'select * from robot_comment_task where id='.$taskId;
        $r = $this->getDBConnection()->executeQuery($sql)->fetch(\PDO::FETCH_ASSOC);
        return $r;
    }

    /**
     *
     * @covers ::dispatchCommentTaskWhenCommentEventHappen
     */
    public function testDispatchCommentTaskWhenCommentEventHappen()
    {
        $currTime = time();
        $postId = $this->getRandPostId();
        $userId = 1;
        for ($i=0; $i<3;$i++) {
            $task = $this->getService()->dispatchCommentTaskWhenCommentEventHappen($postId, $userId, 0);
        }

        $this->assertNotFalse($task);

        $targetId = $postId;

        $this->assertEquals($targetId, $task->targetId);

        $r = $this->findTask($task->id);
        $this->assertEquals(1, $r['total']);

        $this->assertEquals($targetId, $r['target_id']);

        $this->assertGreaterThanOrEqual($currTime, $r['create_time']);

        $this->assertEquals(RobotCommentTask::WAITING_STATE, $r['state']);
    }

    /**
     *
     * @covers ::comment
     */
    public function testComment()
    {
        $postId = $this->getRandPostId();
        $userId = 1;
        $targetId = $postId;
        $task = [];
        $task['target_id'] = $targetId;
        $comment= $this->getService()->comment($userId, $task);
        $sql = 'select 1 from robot_comment where id ='.$comment->id.' and user_id='.$userId;
        $r = $this->getDBConnection()->executeQuery($sql)->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotEmpty($r);
    }

    /**
     *
     * @covers ::dispatchCommentTaskWhenLikePostEventHappen
     */
    public function testDispatchCommentTaskWhenLikePostEventHappen()
    {
        $currTime = time();
        $postId = $this->getRandPostId();
        $targetId = $postId;
        $userId = 1;
        $task = false;
        for ($i = 0; $i<10; $i++) {
            $r = $this->getService()->dispatchCommentTaskWhenLikePostEventHappen($postId, $userId);
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

        $this->assertEquals(RobotCommentTask::WAITING_STATE, $r['state'],
            'Failed asserting at line:'.__LINE__);
    }


    /**
     *
     * @covers ::dispatchCommentTaskWhenPostCoolDown
     */
    public function testDispatchCommentTaskWhenPostCoolDown()
    {
        $userId = 1;
        $parameter = new \Lychee\Module\Post\PostParameter();
        $topicIds = $this->getRecommendationService()->fetchRecommendableTopicIds();
        $topicId = $topicIds[0];
        $parameter->setTopicId($topicId);

        $content = md5(uniqid(microtime()));
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
        $post = $this->getPostService()->create($parameter);

        $startDateTime = date('Y-m-d H:i:s', strtotime('-1 days'));
        $endDateTime = date('Y-m-d H:i:s');
        $this->getService()->dispatchCommentTaskWhenPostCoolDown($startDateTime, $endDateTime);

        $sql = 'select 1 from robot_comment_task where target_id='.$post->id;
        $r = $this->getDBConnection()->executeQuery($sql)->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotEmpty($r);
    }


}
