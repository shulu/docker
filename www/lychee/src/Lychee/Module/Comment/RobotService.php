<?php
namespace Lychee\Module\Comment;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Lychee\Bundle\CoreBundle\Entity\Comment;
use Lychee\Module\Comment\Entity\RobotCommentTask;
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


    private function getRobotCommentService()
    {
        return $this->serviceContainer->get('lychee.module.robot.comment');
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

    private function getCommentService()
    {
        return $this->serviceContainer->get('lychee.module.comment');
    }

    private function getLogger()
    {
        return $this->serviceContainer->get('logger');
    }

    private function debug($msg)
    {
        $this->getLogger()->debug($msg);
    }

    /**
     * 发表评论
     *
     * @param $robotId
     * @param $task
     * @return Comment
     * @throws \Exception
     */
    public function comment($robotId, $task)
    {
        $targetId = intval($task['target_id']);
        $robotId = intval($robotId);

        $sql = 'SELECT c.content FROM robot_comment rc 
                INNER JOIN comment c ON c.id=rc.id and c.post_id='.$targetId
                .' ORDER BY c.id DESC LIMIT 10';
        $r = $this->entityManager->getConnection()
            ->executeQuery($sql)
            ->fetchAll(\PDO::FETCH_ASSOC);
        $usedContents = [];
        foreach ($r as $item) {
            $usedContents[] = $item['content'];
        }
        $totalContents = CommentConfig::getRobotContentPool();
        $noUsedContents = [];
        if ($usedContents && count($usedContents)<count($totalContents)) {
            $noUsedContents = array_diff($totalContents, $usedContents);
        }

        if ($noUsedContents) {
            $totalContents = $noUsedContents;
        }

        shuffle($totalContents);
        $content = current($totalContents);

        $comment = $this->getCommentService()->create($targetId, $robotId, $content,
            null, '', '127.0.0.1', '', '');
        if (empty($comment)) {
            throw new \Exception(sprintf('评论失败'));
        }

        $sql = 'INSERT IGNORE INTO robot_comment (id) VALUE (' .$comment->id.')';
        $this->entityManager->getConnection()->executeUpdate($sql);

        return $comment;
    }

    /**
     * 触发点赞帖子事件指派机器人任务，含条件判断
     *
     * @param $postId
     * @param $likerId
     * @return bool|RobotCommentTask
     */
    public function dispatchCommentTaskWhenLikePostEventHappen($postId, $likerId)
    {
        // 点赞用户是机器人即忽略
        if ($this->getRobotCommentService()->isWorker($likerId)) {
            return false;
        }

        $postInfo = $this->getPostService()->fetchOne($postId);
        if (empty($postInfo)) {
            $this->debug("帖子不存在 ".__FILE__.__LINE__);
            return false;
        }

        if ($postInfo->deleted) {
            $this->debug("帖子已被删 ".__FILE__.__LINE__);
            return false;
        }

        // 只处理精选次元的帖子
        if (!$this->getRecommendationService()->filterRecommendableTopicIds([$postInfo->topicId])) {
            $this->debug("不是精选次元 ".__FILE__.__LINE__);
            return false;
        }
        $targetId = $postInfo->id;

        $step = 10;
        $counter = $this->getRedisCounter();
        $counterKey='robot_comment_wait_by_like_post'.$targetId;

        $r = $counter->incrAndDecrByStep($counterKey, 1, $step);
        if (empty($r['decrCount'])) {
            return false;
        }
        $total = $r['decrCount'];

        $task = new RobotCommentTask();
        $task->targetId = $targetId;
        $task->total = $total;
        $this->getRobotCommentService()->dispatchTask($task);
        return $task;
    }


    /**
     * 触发评论帖子事件指派机器人任务，含条件判断
     *
     * @param $postId           int 帖子id
     * @param $commentatorId    int 评论用户id
     * @param $commentId        int 评论id
     * @return bool|RobotCommentTask
     */
    public function dispatchCommentTaskWhenCommentEventHappen($postId, $commentatorId, $commentId)
    {
        // 点赞用户是机器人即忽略
        if ($this->getRobotCommentService()->isWorker($commentatorId)) {
            return false;
        }

        $postInfo = $this->getPostService()->fetchOne($postId);
        if (empty($postInfo)) {
            $this->debug("帖子不存在 ".__FILE__.__LINE__);
            return false;
        }

        if ($postInfo->deleted) {
            $this->debug("帖子已被删 ".__FILE__.__LINE__);
            return false;
        }

        // 只处理精选次元的帖子
        if (!$this->getRecommendationService()->filterRecommendableTopicIds([$postInfo->topicId])) {
            $this->debug("不是精选次元 ".__FILE__.__LINE__);
            return false;
        }
        $targetId = $postInfo->id;

        $step = 3;
        $counter = $this->getRedisCounter();
        $counterKey='robot_comment_wait_by_comment'.$targetId;

        $r = $counter->incrAndDecrByStep($counterKey, 1, $step);
        if (empty($r['decrCount'])) {
            return false;
        }
        $total = $r['decrCount'];

        $task = new RobotCommentTask();
        $task->targetId = $targetId;
        $task->total = $total;
        $this->getRobotCommentService()->dispatchTask($task);
        return $task;
    }

    /**
     * 在指定时间内发帖且至今没评论的帖子，指派评论任务
     *
     * @param $startDateTime
     * @param $endDateTime
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function dispatchCommentTaskWhenPostCoolDown($startDateTime, $endDateTime)
    {
        $sql = 'SELECT min(id) min_id, max(id) max_id FROM post WHERE create_time BETWEEN ? AND ?';
        $r = $this->entityManager->getConnection()
            ->executeQuery($sql, [$startDateTime, $endDateTime])
            ->fetch(\PDO::FETCH_ASSOC);
        if (empty($r)) {
            return false;
        }

        $minId = $r['min_id']-1;
        $maxId = $r['max_id'];
        $cursorId = $minId;

        $sql = 'SELECT p.id FROM post p 
        INNER JOIN recommendable_topic rt ON rt.topic_id=p.topic_id
        LEFT JOIN post_comment pc ON pc.post_id=p.id
        WHERE p.id > ? AND p.id <= ? group by p.id 
        HAVING count(pc.comment_id)=0 
        ORDER BY p.id ASC LIMIT 1000';

        while (true) {
            $list = $this->entityManager->getConnection()
                ->executeQuery($sql, [$cursorId, $maxId])
                ->fetchAll(\PDO::FETCH_ASSOC);
            if (empty($list)) {
                return true;
            }
            foreach ($list as $item) {
                $cursorId = $item['id'];
                $task = new RobotCommentTask();
                $task->targetId = $item['id'];
                $task->total = 1;
                $this->getRobotCommentService()->dispatchTask($task);
            }

            usleep(200000);
        }

        return true;
    }


}