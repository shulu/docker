<?php
namespace Lychee\Module\Like;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Lychee\Module\Like\Entity\RobotLikePostTask;
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


    private function getRobotLikePostService()
    {
        return $this->serviceContainer->get('lychee.module.robot.like_post');
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

    private function getLikeService()
    {
        return $this->serviceContainer->get('lychee.module.like');
    }

    private function getLogger()
    {
        return $this->serviceContainer->get('logger');
    }

    /**
     * 触发点赞帖子事件指派机器人任务，含条件判断
     *
     * @param $postId
     * @param $likerId
     * @return bool|RobotLikePostTask
     */
    public function dispatchLikePostTaskWhenLikeEventHappen($postId, $likerId)
    {
        // 点赞用户是机器人即忽略
        if ($this->getRobotLikePostService()->isWorker($likerId)) {
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

        $step = 3;
        $counter = $this->getRedisCounter();
        $counterKey='robot_like_post_wait'.$postId;

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

        $task = new RobotLikePostTask();
        $task->postId = $postId;
        $task->total = $total;
        $this->getRobotLikePostService()->dispatchTask($task);
        return $task;
    }

    /**
     * 点赞帖子
     *
     * @param $robotId
     * @param $task
     * @return array
     * @throws \Exception
     */
    public function likePost($robotId, $task)
    {
        $postId = intval($task['post_id']);
        $robotId = intval($robotId);
        $likeRes = $this->getLikeService()->likePost($robotId, $postId);
        if ($likeRes['isLiked']) {
            throw new \Exception(sprintf('post is already liked, post id (%s), user id (%s)',
                $postId, $robotId));
        }

        $sql = 'INSERT INTO robot_like_post (liker_id, post_id, time) VALUE ('
            .$robotId.', '.$postId.', '.time()
            .') ON DUPLICATE KEY UPDATE time = VALUES(time)';
        $this->entityManager->getConnection()->executeUpdate($sql);

        return $likeRes;
    }

}