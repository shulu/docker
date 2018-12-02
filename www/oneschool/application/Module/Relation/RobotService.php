<?php
namespace Lychee\Module\Relation;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Lychee\Module\Relation\Entity\RobotUserFollowTask;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RobotService {

    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var ContainerInterface
     */
    private $serviceContainer;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct($registry, $serviceContainer) {
        $this->entityManager = $registry->getManager();
        $this->serviceContainer = $serviceContainer;
    }


    private function getRobotFollowUserService()
    {
        return $this->serviceContainer->get('lychee.module.robot.follow_user');
    }

    private function getPostService()
    {
        return $this->serviceContainer->get('lychee.module.post');
    }

    private function getRecommendationService()
    {
        return $this->serviceContainer->get('lychee.module.recommendation');
    }

    private function getRedisCounter()
    {
        return $this->serviceContainer->get('lychee.component.counter.robot');
    }

    private function getRelationService()
    {
        return $this->serviceContainer->get('lychee.module.relation');
    }

    private function getLogger()
    {
        return $this->serviceContainer->get('logger');
    }

    /**
     * 关注用户
     *
     * @param $robotId
     * @param $task
     * @return bool
     * @throws \Exception
     */
    public function followUser($robotId, $task)
    {
        $targetId = intval($task['target_id']);
        $robotId = intval($robotId);

        $r = $this->getRelationService()->makeUserFollowAnother($robotId, $targetId);
        if ($r['isFollowed']) {
            throw new \Exception(sprintf('(%s) 已关注了 (%s).',
                $robotId, $targetId));
        }

        $sql = 'INSERT INTO robot_user_following (follower_id, followee_id, time) VALUE ('
            .$robotId.', '.$targetId.', '.time()
            .') ON DUPLICATE KEY UPDATE time = VALUES(time)';
        $this->entityManager->getConnection()->executeUpdate($sql);

        return true;
    }

    /**
     * 触发发布帖子事件指派机器人任务，含条件判断
     *
     * @param $postId
     * @return bool|RobotUserFollowTask
     */
    public function dispatchFollowUserTaskWhenPostEventHappen($postId)
    {
        $postInfo = $this->getPostService()->fetchOne($postId);
        if (empty($postInfo)) {
//            $this->getLogger()->debug("帖子不存在 ");
            return false;
        }
        if ($postInfo->deleted) {
//            $this->getLogger()->debug("帖子已被删 ");
            return false;
        }

        // 只处理精选次元的帖子
        if (!$this->getRecommendationService()->filterRecommendableTopicIds([$postInfo->topicId])) {
//            $this->getLogger()->debug("不是精选次元 ");
            return false;
        }

        $targetId = $postInfo->authorId;

        $task = new RobotUserFollowTask();
        $task->targetId = $targetId;
        $task->total = 1;
        $this->getRobotFollowUserService()->dispatchTask($task);
        return $task;
    }

    /**
     * 触发关注事件指派机器人任务，含条件判断
     *
     * @param $postId
     * @param $likerId
     * @return bool|RobotUserFollowTask
     */
    public function dispatchFollowUserTaskWhenFollowUserEventHappen($userId, $targetId)
    {
        // 点赞用户是机器人即忽略
        if ($this->getRobotFollowUserService()->isWorker($userId)) {
            return false;
        }

        $step = 3;
        $counter = $this->getRedisCounter();
        $counterKey='robot_follow_user_wait_by_follow_user'.$targetId;

        // 累计点赞次数，是否达到步长
        $count = $counter->incr($counterKey);
        if ($count<$step) {
//            $this->getLogger()->debug("未达到步长 ".$count.'('.__FILE__.':'.__LINE__.')');
            return false;
        }

        $total = intval($count/$step);
        $decrNum = $total*$step;

        if (false===$counter->decrMustEnough($counterKey, $decrNum)) {
//            $this->getLogger()->debug("超扣 ".$decrNum.'('.__FILE__.':'.__LINE__.')');
            return false;
        }

        $task = new RobotUserFollowTask();
        $task->targetId = $targetId;
        $task->total = $total;
        $this->getRobotFollowUserService()->dispatchTask($task);
        return $task;
    }

    /**
     * 触发点赞帖子事件指派机器人任务，含条件判断
     *
     * @param $postId
     * @param $likerId
     * @return bool|RobotUserFollowTask
     */
    public function dispatchFollowUserTaskWhenLikeEventHappen($postId, $likerId)
    {
        // 点赞用户是机器人即忽略
        if ($this->getRobotFollowUserService()->isWorker($likerId)) {
            return false;
        }

        $postInfo = $this->getPostService()->fetchOne($postId);
        if (empty($postInfo)) {
//            $this->getLogger()->debug("帖子不存在 ");
            return false;
        }

        if ($postInfo->deleted) {
//            $this->getLogger()->debug("帖子已被删 ");
            return false;
        }

        // 只处理精选次元的帖子
        if (!$this->getRecommendationService()->filterRecommendableTopicIds([$postInfo->topicId])) {
//            $this->getLogger()->debug("不是精选次元 ");
            return false;
        }
        $targetId = $postInfo->authorId;

        $step = 10;
        $counter = $this->getRedisCounter();
        $counterKey='robot_follow_user_wait_by_like_post'.$targetId;

        // 累计点赞次数，是否达到步长
        $count = $counter->incr($counterKey);
        if ($count<$step) {
//            $this->getLogger()->debug("未达到步长 ".$count);
            return false;
        }

        $total = intval($count/$step);
        $decrNum = $total*$step;

        if (false===$counter->decrMustEnough($counterKey, $decrNum)) {
//            $this->getLogger()->debug("超扣 ".$decrNum);
            return false;
        }

        $task = new RobotUserFollowTask();
        $task->targetId = $targetId;
        $task->total = $total;
        $this->getRobotFollowUserService()->dispatchTask($task);
        return $task;
    }

}