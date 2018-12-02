<?php
namespace Lychee\Module\Relation;

use Doctrine\ORM\EntityManager;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Foundation\CursorableIterator\DatetimeQueryCursorableIterator;
use Lychee\Component\GraphStorage\Doctrine\AbstractFollowing;
use Lychee\Module\Relation\BlackList\ParticalBlackListResolver;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Lychee\Component\GraphStorage\Redis\RedisFollowingStorage;
use Lychee\Component\GraphStorage\FollowingStorage;
use Lychee\Component\GraphStorage\FollowingCounter;
use Lychee\Component\GraphStorage\FollowingResolver;
use Lychee\Component\GraphStorage\Exception\FollowingException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Lychee\Component\GraphStorage\Doctrine\DoctrineFollowingStorage;
use Lychee\Module\Relation\Entity\UserFollowing;
use Lychee\Module\Relation\Entity\UserFollowingCounting;
use Lychee\Module\Relation\BlackList\BlackListResolver;

class RelationService {

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var FollowingStorage
     */
    private $doctrineStorage;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ContainerInterface
     */
    private $serviceContainer;

    /**
     * @param ManagerRegistry $doctrine
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct($doctrine, $eventDispatcher, $serviceContainer=null) {
        $this->entityManager = $doctrine->getManager();
        $this->doctrineStorage = new DoctrineFollowingStorage(
            $this->entityManager, UserFollowing::class, UserFollowingCounting::class
        );
        $this->eventDispatcher = $eventDispatcher;
        $this->serviceContainer = $serviceContainer;
    }

    /**
     * @param int $userId
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     * @return array
     */
    public function fetchFollowerIdsByUserId($userId, $cursor, $count, &$nextCursor = null) {
        return $this->doctrineStorage->fetchFollowers($userId, $cursor, $count, $nextCursor);
    }

    /**
     * @param int $userId
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     * @return array
     */
    public function fetchFolloweeIdsByUserId($userId, $cursor, $count, &$nextCursor = null) {
        return $this->doctrineStorage->fetchFollowees($userId, $cursor, $count, $nextCursor);
    }

    /**
     * @param int $userId
     *
     * @return int
     */
    public function countUserFollowers($userId) {
        return $this->doctrineStorage->countFollowers($userId);
    }

    /**
     * @param int $userId
     *
     * @return int
     */
    public function countUserFollowees($userId) {
        return $this->doctrineStorage->countFollowees($userId);
    }

    /**
     * @param int $userId
     * @param int $anotherId
     *
     * @return bool
     */
    public function isUserFollowingAnother($userId, $anotherId) {
        return $this->doctrineStorage->isFollowing($userId, $anotherId);
    }

    /**
     * @param int $userId
     * @param int $anotherId
     * @throws Exception\CreateRelationException
     */
    public function makeUserFollowAnother($userId, $anotherId) {
        $ret = [];
        try {
            $ret = $this->doctrineStorage->follow($userId, $anotherId);
        } catch (FollowingException $e) {
            throw new Exception\CreateRelationException();
        }

        $this->eventDispatcher->dispatch(
            RelationEvent::FOLLOW,
            new RelationEvent($userId, array($anotherId))
        );

        if (empty($ret['isFollowed'])) {
            $event = [];
            $event['userId'] = $userId;
            $event['targetIds'] = array($anotherId);
            $this->dispatchEvent('user.follow', $event);
        }

        return $ret;
    }

    /**
     * @param int $userId
     * @param array $otherIds
     * @throws Exception\CreateRelationException
     */
    public function makeUserFollowOthers($userId, $otherIds) {
        try {
            $this->doctrineStorage->multiFollow($userId, $otherIds);
        } catch (FollowingException $e) {
            throw new Exception\CreateRelationException();
        }

        $this->eventDispatcher->dispatch(
            RelationEvent::FOLLOW,
            new RelationEvent($userId, $otherIds)
        );

        $event = [];
        $event['userId'] = $userId;
        $event['targetIds'] = $otherIds;
        $this->dispatchEvent('user.follow', $event);
    }

    /**
     * @param int $userId
     * @param int $anotherId
     */
    public function makeUserUnfollowAnother($userId, $anotherId) {
        $this->doctrineStorage->unfollow($userId, $anotherId);

        $this->eventDispatcher->dispatch(
            RelationEvent::UNFOLLOW,
            new RelationEvent($userId, array($anotherId))
        );
    }

    /**
     * @param int $userId
     * @param array $otherIds
     */
    public function makeUserUnfollowOthers($userId, $otherIds) {
        $this->doctrineStorage->multiUnfollow($userId, $otherIds);

        $this->eventDispatcher->dispatch(
            RelationEvent::UNFOLLOW,
            new RelationEvent($userId, $otherIds)
        );
    }

    /**
     * @param int $userId
     * @param array $otherIds
     * @param int $hint
     * @return FollowingResolver
     */
    public function buildRelationResolver($userId, $otherIds, $hint = FollowingResolver::HINT_NONE) {
        return $this->doctrineStorage->buildResolver($userId, $otherIds, $hint);
    }

    /**
     * @param $userIds
     *
     * @return FollowingCounter
     */
    public function buildRelationCounter($userIds) {
        return $this->doctrineStorage->buildCounter($userIds);
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
        $query = $repo->createQueryBuilder('r')
            ->where("r.$fieldName > :cursor")
            ->andWhere('r.state = :state')
            ->setParameter('state', AbstractFollowing::STATE_NORMAL)
            ->orderBy('r.' . $fieldName)
            ->getQuery();

        return new DatetimeQueryCursorableIterator($query, $fieldName, null, $initDatetime);
    }

    /**
     * @param int $userId
     * @param int $targetId
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function userBlackListAdd($userId, $targetId) {
        $this->entityManager->getConnection()->executeUpdate('
            INSERT INTO user_blacklist(user_id, target_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE target_id = target_id
        ', array($userId, $targetId), array(\PDO::PARAM_INT, \PDO::PARAM_INT));
    }

    /**
     * @param int $userId
     * @param int $targetId
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function userBlackListRemove($userId, $targetId) {
        $this->entityManager->getConnection()->executeUpdate('
            DELETE FROM user_blacklist WHERE user_id = ? AND target_id = ?
        ', array($userId, $targetId), array(\PDO::PARAM_INT, \PDO::PARAM_INT));
    }

    /**
     * @param int $userId
     * @param int $targetId
     * @return bool
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function userBlackListHas($userId, $targetId) {
        $statement = $this->entityManager->getConnection()->executeQuery('
            SELECT 1 FROM user_blacklist WHERE user_id = ? AND target_id = ?
        ', array($userId, $targetId), array(\PDO::PARAM_INT, \PDO::PARAM_INT));
        return $statement->rowCount() > 0;
    }

    /**
     * @param int[] $userIds
     * @param int $targetId
     *
     * @return int[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function userBlackListFilterNoBlocking($userIds, $targetId) {
        if (count($userIds) == 0) {
            return array();
        }
        $inSql = implode(',', $userIds);
        $sql = 'SELECT user_id FROM user_blacklist WHERE user_id IN ('.$inSql
            .') AND target_id = '.intval($targetId);
        $stat = $this->entityManager->getConnection()->executeQuery($sql);
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        $result = array();
        foreach ($rows as $row) {
            $result[] = $row['user_id'];
        }
        return ArrayUtility::diffValue($userIds, $result);
    }

    /**
     * @param int $userId
     * @return int
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function userBlackListCount($userId) {
        $statement = $this->entityManager->getConnection()->executeQuery('
            SELECT COUNT(target_id) FROM user_blacklist WHERE user_id = ?
        ', array($userId), array(\PDO::PARAM_INT));
        return intval($statement->fetchColumn(0));
    }

    /**
     * @param int $userId
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function userBlackListList($userId, $cursor, $count, &$nextCursor) {
        if ($count <= 0 || $cursor < 0) {
            $nextCursor = $cursor;
            return array();
        }

        $statement = $this->entityManager->getConnection()->executeQuery('
            SELECT target_id FROM user_blacklist WHERE user_id = ? LIMIT ?, ?
        ', array($userId, $cursor, $count), array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

        if (count($result) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $cursor + $count;
        }

        return ArrayUtility::columns($result, 'target_id');
    }

    /**
     * @param int $userId
     * @param int[] $otherIds
     * @return BlackListResolver
     */
    public function userBlackListBuildResolver($userId, $otherIds) {
        if (count($otherIds) == 0) {
            return new ParticalBlackListResolver(array());
        }

        $statement = $this->entityManager->getConnection()->executeQuery(
            sprintf('SELECT target_id FROM user_blacklist WHERE user_id = ? AND target_id IN (%s)',
                implode(',', $otherIds)),
            array($userId),
            array(\PDO::PARAM_INT)
        );
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $blockedIds = ArrayUtility::columns($result, 'target_id');
        return new ParticalBlackListResolver(array_flip($blockedIds));
    }

	/**
	 * @param $followeeId
	 * @param \DateTime $date
	 * @param int $state
	 *
	 * @return int
	 */
    private function getFollowStateCountByDate($followeeId, \DateTime $date, $state = UserFollowing::STATE_NORMAL) {
	    $nextDate = clone $date;
	    $nextDate->modify('+1 day');
	    $query = $this->entityManager->getRepository(UserFollowing::class)
	                                 ->createQueryBuilder('uf')
	                                 ->select('COUNT(uf.id) followerCount')
	                                 ->where('uf.followeeId = :followeeId')
	                                 ->andWhere('uf.updateTime>=:startDate')
	                                 ->andWhere('uf.updateTime<:endDate')
	                                 ->andWhere('uf.state=:state')
	                                 ->setParameter('followeeId', $followeeId)
	                                 ->setParameter('startDate', $date)
	                                 ->setParameter('endDate', $nextDate )
	                                 ->setParameter('state', $state)
	                                 ->getQuery();
	    $result = $query->getOneOrNullResult();
	    if (!$result) {
		    return 0;
	    } else {
		    return (int)$result['followerCount'];
	    }
    }

	/**
	 * @param $followeeId
	 * @param \DateTime $date
	 *
	 * @return int
	 */
    public function getFollowerCountByDate($followeeId, \DateTime $date) {
    	return $this->getFollowStateCountByDate($followeeId, $date);
    }

	/**
	 * @param $followeeId
	 * @param \DateTime $date
	 *
	 * @return int
	 */
    public function getUnFollowerCountByDate($followeeId, \DateTime $date) {
    	return $this->getFollowStateCountByDate($followeeId, $date, UserFollowing::STATE_REMOVED);
    }


    private function dispatchEvent($eventName, $event)
    {
        $this->serviceContainer->get('lychee.dynamic_dispatcher_async')->dispatch($eventName, $event);
    }

    /**
     * 关注用户后事件，消费消息队列异步执行
     *
     * @param $eventBody
     */
    public function asyncAfterFollowUser($eventBody) {

        $triggers = [];
        $triggers[] = [$this->serviceContainer->get('lychee.module.relation.robot'), 'dispatchFollowUserTaskWhenFollowUserEventHappen'];

        foreach ($triggers as $trigger) {
            list($class, $method) = $trigger;
            foreach ($eventBody['targetIds'] as $targetId) {
                try {
                    $class->$method($eventBody['userId'], $targetId);
                } catch (\Exception $e) {
                    $this->serviceContainer->get('logger')->error($e->__toString());
                }
            }
        }
        return true;
    }

} 