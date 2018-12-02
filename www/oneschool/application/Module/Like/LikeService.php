<?php
namespace Lychee\Module\Like;

use Doctrine\ORM\Query\ResultSetMapping;
use Lychee\Component\Foundation\CursorableIterator\DatetimeQueryCursorableIterator;
use Lychee\Module\Like\Entity\PostLike;
use Lychee\Component\Foundation\ArrayUtility;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Lychee\Module\Post\PostService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Lychee\Module\Comment\CommentService;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Lychee\Component\KVStorage\MemcacheStorage;
use Lychee\Module\Like\Entity\CommentLike;

class LikeService {

    /**
     * @var CommentService
     */
    private $commentModule;

    private $memcacheService;


    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var PostService
     */
    private $postModule;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    private $dataSrcConfig;

    /**
     * @var ContainerInterface
     */
    private $serviceContainer;


    /**
     * @param ManagerRegistry $registry
     * @param PostService $postModule
     * @param EventDispatcherInterface $eventDispatcher
     * @param CommentService $commentModule
     */
    public function __construct($registry, $postModule, $eventDispatcher, $commentModule, $memcache, $serviceContainer=null) {
        $this->entityManager = $registry->getManager($registry->getDefaultManagerName());
        $this->postModule = $postModule;
        $this->eventDispatcher = $eventDispatcher;
        $this->commentModule = $commentModule;
        $this->memcacheService = $memcache;

        $this->dataSrcConfig = [];
        $this->dataSrcConfig[LikeType::POST] = [
            'table'=>'like_post',
            'targetField'=>'post_id',
            'index'=>'post_state_id_udx',
        ];
        $this->dataSrcConfig[LikeType::COMMENT] = [
            'table'=>'like_comment',
            'targetField'=>'comment_id',
            'index'=>'comment_state_id_udx',
        ];

        $this->serviceContainer = $serviceContainer;
    }


    private function getDataSrcConfig($type) {
        if (empty($this->dataSrcConfig[$type])) {
            $type = LikeType::POST;
        }
        return $this->dataSrcConfig[$type];
    }

    private function dispatchEvent($eventName, $event)
    {
        $this->serviceContainer->get('lychee.dynamic_dispatcher_async')->dispatch($eventName, $event);
    }

    /**
     * @param $userId
     * @param $postId
     * @param null $likedBefore
     * @param bool $callEvent
     * @return array  $ret
     *                $rer['isLiked']   是否已经关注了
     * @throws \Exception
     */
    public function likePost($userId, $postId, &$likedBefore = null, $callEvent = true) {
        $isLiked = false;
        try {
            $this->entityManager->getConnection()
                ->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            $this->entityManager->beginTransaction();

            $state = $this->getLikeStateAtomically($userId, $postId);
            if ($state === null) {
                $likedBefore = false;
                $this->insertLike($userId, $postId);
                $this->postModule->increaseLikedCounter($postId, 1);
            } else if ($state === LikeState::REMOVED) {
                $likedBefore = true;
                $this->updateLikeState($userId, $postId, LikeState::NORMAL);
                $this->postModule->increaseLikedCounter($postId, 1);
            } else {
                $isLiked = true;
                $likedBefore = true;
                //already liked, do nothing.
            }

            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        if ($callEvent) {
	        $this->eventDispatcher->dispatch(
		        LikeEvent::LIKE,
		        new LikeEvent(LikeType::POST, $userId, $postId, $likedBefore)
	        );

        }

        if ($callEvent
            &&empty($isLiked)) {
            $event = [];
            $event['postId'] = $postId;
            $event['likerId'] = $userId;
            $this->dispatchEvent('post.like', $event);
        }

        return [
            'isLiked' => $isLiked
        ];
    }

    public function cancelLikePost($userId, $postId) {
        try {
            $this->entityManager->getConnection()
                ->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            $this->entityManager->beginTransaction();

            $state = $this->getLikeStateAtomically($userId, $postId);
            if ($state === null || $state === LikeState::REMOVED) {
                //already unliked, do nothing.
            } else {
                $this->updateLikeState($userId, $postId, LikeState::REMOVED);
                $this->postModule->increaseLikedCounter($postId, -1);
            }

            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        $this->eventDispatcher->dispatch(
            LikeEvent::UNLIKE,
            new LikeEvent(LikeType::POST, $userId, $postId, false)
        );
    }

    public function fetchPostLikerIds($postId, $cursor, $count, &$nextCursor = null) {
        return $this->fetchOneLikerIds($postId, $cursor, $count, $nextCursor);
    }

    public function fetchPostsLatestLikerIds($postIds, $count) {
        return $this->fetchLikerIds($postIds, $count);
    }

    public function likeComment($userId, $commentId, &$likedBefore = null) {
        try {
            $this->entityManager->getConnection()
                ->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            $this->entityManager->beginTransaction();

            $state = $this->getLikeStateAtomically($userId, $commentId, LikeType::COMMENT);
            if ($state === null) {
                $likedBefore = false;
                $this->insertLike($userId, $commentId, LikeType::COMMENT);
                $this->commentModule->increaseLikedCounter($commentId, 1);
            } else if ($state === LikeState::REMOVED) {
                $likedBefore = true;
                $this->updateLikeState($userId, $commentId, LikeState::NORMAL, LikeType::COMMENT);
                $this->commentModule->increaseLikedCounter($commentId, 1);
            } else {
                $likedBefore = true;
                //already liked, do nothing.
            }

            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        $this->eventDispatcher->dispatch(
            LikeEvent::LIKE,
            new LikeEvent(LikeType::COMMENT, $userId, $commentId, $likedBefore)
        );
    }

    public function cancelLikeComment($userId, $commentId) {
        try {
            $this->entityManager->getConnection()
                ->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            $this->entityManager->beginTransaction();

            $state = $this->getLikeStateAtomically($userId, $commentId, LikeType::COMMENT);
            if ($state === null || $state === LikeState::REMOVED) {
                //already unliked, do nothing.
            } else {
                $this->updateLikeState($userId, $commentId, LikeState::REMOVED, LikeType::COMMENT);
                $this->commentModule->increaseLikedCounter($commentId, -1);
            }

            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        $this->eventDispatcher->dispatch(
            LikeEvent::UNLIKE,
            new LikeEvent(LikeType::COMMENT, $userId, $commentId, false)
        );
    }

    public function fetchCommentLikerIds($commentId, $curosr, $count, &$nextCursor = null) {
        return $this->fetchOneLikerIds($commentId, $curosr, $count, $nextCursor, LikeType::COMMENT);
    }

    public function fetchCommnetsLatestLikerIds($commentIds, $count) {
        return $this->fetchLikerIds($commentIds, $count, LikeType::COMMENT);
    }


    private function getLikeStateAtomically($userId, $targetId, $type = LikeType::POST) {
        $dataSrcConfig = $this->getDataSrcConfig($type);
        $table = $dataSrcConfig['table'];
        $targetField = $dataSrcConfig['targetField'];

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('state', 'state', 'integer');

        $query = $this->entityManager->createNativeQuery('
            SELECT state FROM '. $table  .'
            WHERE liker_id = :likerId
            AND '. $targetField .' = :targetId
            LIMIT 1
            FOR UPDATE
        ', $rsm);
        $result = $query->execute(array('likerId' => $userId, 'targetId' => $targetId));

        if (count($result) === 0) {
            return null;
        } else {
            return $result[0]['state'];
        }
    }

    private function insertLike($userId, $targetId, $type = LikeType::POST) {
        $dataSrcConfig = $this->getDataSrcConfig($type);
        $table = $dataSrcConfig['table'];
        $targetField = $dataSrcConfig['targetField'];
        $state = LikeState::NORMAL;

        $statement = $this->entityManager->getConnection()->prepare('
            INSERT INTO '. $table .'(liker_id, '. $targetField.',state, update_time)
            VALUES(:likerId, :targetId, :state, :updateTime)
            ON DUPLICATE KEY UPDATE state=:state
        ');
        $datetime = $this->entityManager->getConnection()->convertToDatabaseValue(new \DateTime(), 'datetime');
        $statement->bindValue('likerId', $userId, \PDO::PARAM_INT);
        $statement->bindValue('targetId', $targetId, \PDO::PARAM_INT);
        $statement->bindValue('state', $state, \PDO::PARAM_INT);
        $statement->bindValue('updateTime', $datetime, \PDO::PARAM_STR);
        $statement->execute();

    }

    private function updateLikeState($userId, $targetId, $state, $type = LikeType::POST) {
        $dataSrcConfig = $this->getDataSrcConfig($type);
        $table = $dataSrcConfig['table'];
        $targetField = $dataSrcConfig['targetField'];

        $this->entityManager->getConnection()->executeUpdate('
            UPDATE '. $table .'
            SET state = :state
            WHERE liker_id = :likerId
            AND '. $targetField .' = :targetId
        ', array(
            'likerId' => $userId, 'targetId' => $targetId,
            'state' => $state,
        ), array(
            'likerId' => \PDO::PARAM_INT, 'targetId' => \PDO::PARAM_INT,
            'state' => \PDO::PARAM_INT
        ));
    }

    private function fetchOneLikerIds($targetId, $cursor, $count, &$nextCursor, $type = LikeType::POST) {
        $dataSrcConfig = $this->getDataSrcConfig($type);
        $table = $dataSrcConfig['table'];
        $targetField = $dataSrcConfig['targetField'];
        $state = LikeState::NORMAL;

        if ($cursor == 0) {
            $cursor = PHP_INT_MAX;
        }

        $statement = $this->entityManager->getConnection()->executeQuery('
            SELECT id, liker_id
            FROM '. $table .'
            WHERE '. $targetField .' = :targetId
            AND state = :state
            AND id < :cursor
            ORDER BY id DESC
            LIMIT 0, :count
        ', array(
            'targetId' => $targetId, 'state' => $state,
            'cursor' => $cursor, 'count' => $count
        ), array(
            'targetId' => \PDO::PARAM_INT, 'state' => \PDO::PARAM_INT,
            'cursor' => \PDO::PARAM_INT, 'count' => \PDO::PARAM_INT
        ));
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

        if (count($result) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $result[count($result) - 1]['id'];
        }

        return ArrayUtility::columns($result, 'liker_id');
    }

    private function fetchLikerIds($targetIds, $count, $type = LikeType::POST) {
        if (count($targetIds) === 0) {
            return array();
        }
        $dataSrcConfig = $this->getDataSrcConfig($type);
        $table = $dataSrcConfig['table'];
        $targetField = $dataSrcConfig['targetField'];
        $index = $dataSrcConfig['index'];
        $state = LikeState::NORMAL;

        $sql = '';
        foreach ($targetIds as $targetId) {
            if (strlen($sql) > 0) {
                $sql .= 'UNION';
            }
            $sql .= '(
                SELECT '. $targetField .' as likee_id, liker_id FROM '. $table .' USE INDEX ('. $index .')
                WHERE '. $targetField .' = '. intval($targetId) .' AND state = '. $state .'
                ORDER BY id DESC LIMIT 0, '. intval($count) .'
            )';
        }
        $statement = $this->entityManager->getConnection()->executeQuery($sql);
        $queryResult = $statement->fetchAll(\PDO::FETCH_COLUMN | \PDO::FETCH_GROUP);
        $result = array();
        foreach ($targetIds as $targetId) {
            if (isset($queryResult[$targetId])) {
                $result[$targetId] = $queryResult[$targetId];
            } else {
                $result[$targetId] = array();
            }
        }
        return $result;
    }

    /**
     * @param int $accountId
     * @param array $postIds
     *
     * @return ParticalLikeResolver
     */
    public function buildPostLikeResolver($accountId, $postIds) {
        if ($accountId == 0) {
            return new ParticalLikeResolver(array());
        }

        $query = $this->entityManager->createQuery('
            SELECT pl.postId
            FROM '.PostLike::class.' pl
            WHERE pl.likerId = :likerId
            AND pl.postId IN (:postIds)
            AND pl.state = :state
        ');
        $result = $query->execute(array('likerId' => $accountId, 'postIds' => $postIds, 'state' => LikeState::NORMAL));
        $ids = ArrayUtility::columns($result, 'postId');

        $map = array_fill_keys($ids, true);
        return new ParticalLikeResolver($map);
    }

    /**
     * @param $entity
     * @param \DateTime $initDatetime
     * @param string $fieldName
     * @return DatetimeQueryCursorableIterator
     */
    public function getIterator($entity, \DateTime $initDatetime = null, $fieldName = 'updateTime')
    {
        $repo = $this->entityManager->getRepository($entity);
        $query = $repo->createQueryBuilder('l')
            ->where("l.$fieldName > :cursor")
            ->andWhere('l.state = :state')
            ->setParameter('state', LikeState::NORMAL)
            ->orderBy('l.' . $fieldName)
            ->getQuery();

        return new DatetimeQueryCursorableIterator($query, $fieldName, null, $initDatetime);
    }

    /**
     * @param int $accountId
     * @param array $commentIds
     *
     * @return ParticalLikeResolver
     */
    public function buildCommentLikeResolver($accountId, $commentIds) {
        if ($accountId == 0) {
            return new ParticalLikeResolver(array());
        }

        $query = $this->entityManager->createQuery('
            SELECT cl.commentId
            FROM '.CommentLike::class.' cl
            WHERE cl.likerId = :likerId
            AND cl.commentId IN (:commentIds)
            AND cl.state = :state
        ');
        $result = $query->execute(array('likerId' => $accountId, 'commentIds' => $commentIds, 'state' => LikeState::NORMAL));
        $ids = ArrayUtility::columns($result, 'commentId');

        $map = array_fill_keys($ids, true);
        return new ParticalLikeResolver($map);
    }

    /**
     * @param $commentIds
     * @return array
     */
    public function fetchAmountOfCommentsLiker($commentIds) {
        $memcacheStorage = new MemcacheStorage($this->memcacheService, 'like:comment:amount:', 6 * 3600);
        $ret = $memcacheStorage->getMulti($commentIds);
        $noCacheCommentIds = array_diff($commentIds, array_keys($ret));

        if (false === empty($noCacheCommentIds)) {
            $repo = $this->entityManager->getRepository(CommentLike::class);
            $qb = $repo->createQueryBuilder('lc');
            $qb->select(array('lc.commentId', 'COUNT(lc.likerId)'));
            $qb->where('lc.commentId IN (:commentIds)');
            $qb->groupBy('lc.commentId');
            $qb->setParameter('commentIds', $noCacheCommentIds);
            $result = $qb->getQuery()->getResult();
            $cache = [];
            foreach ($result as $row) {
                $key = $row['commentId'];
                $value = $row[1];
                $ret[$key] = $value;
                $cache[$key] = $value;
            }
            foreach ($noCacheCommentIds as $noCacheCommentId) {
                if (false === isset($ret[$noCacheCommentId])) {
                    $ret[$noCacheCommentId] = 0;
                    $cache[$noCacheCommentId] = 0;
                }
            }
            $memcacheStorage->setMulti($cache);
        }

        return $ret;
    }

    /**
     * 帖子点赞后事件，消费消息队列异步执行
     *
     * @param $eventBody
     */
    public function asyncAfterLikePost($eventBody) {

        $triggers = [];
        $triggers[] = [$this->serviceContainer->get('lychee.module.like.robot'), 'dispatchLikePostTaskWhenLikeEventHappen'];
        $triggers[] = [$this->serviceContainer->get('lychee.module.relation.robot'), 'dispatchFollowUserTaskWhenLikeEventHappen'];
        $triggers[] = [$this->serviceContainer->get('lychee.module.comment.robot'), 'dispatchCommentTaskWhenLikePostEventHappen'];

        foreach ($triggers as $trigger) {
            try {

                list($class, $method) = $trigger;
                $class->$method($eventBody['postId'], $eventBody['likerId']);

            } catch (\Exception $e) {
                $this->serviceContainer->get('logger')->error($e->__toString());
            }
        }
        return true;
    }
}