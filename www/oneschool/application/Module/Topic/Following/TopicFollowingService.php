<?php
namespace Lychee\Module\Topic\Following;

use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Foundation\CursorableIterator\CustomizedCursorableIterator;
use Lychee\Module\Topic\Entity\Topic;
use Lychee\Module\Topic\Entity\TopicUserFollowing;
use Lychee\Module\Topic\Exception\FollowingTooMuchTopicException;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;
use Lychee\Module\Topic\Exception\TopicMissingException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TopicFollowingService {
    /**
     * @var Connection
     */
    private $conn;
    private $eventDispatcher;
    private $container;
    /**
     * @param RegistryInterface $registry
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct($registry, $eventDispatcher, $container) {
        $this->conn = $registry->getConnection();
        $this->eventDispatcher = $eventDispatcher;
        $this->container = $container;
    }

    private function lockUserMeta($userId) {
        $this->conn->beginTransaction();
        $lockSql = 'SELECT followee_count, followee_position FROM topic_user_meta WHERE user_id = ? FOR UPDATE';
        $stat = $this->conn->executeQuery($lockSql, array($userId), array(\PDO::PARAM_INT));
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        if (count($rows) > 0) {
            return array($rows[0]['followee_count'], $rows[0]['followee_position']);
        }
        $this->conn->rollBack();

        $this->conn->beginTransaction();
        $insertSql = 'INSERT INTO topic_user_meta(user_id, followee_count, followee_position) VALUES (?, 0, 0) '
            . 'ON DUPLICATE KEY UPDATE followee_position = followee_position';
        $inserted = $this->conn->executeUpdate($insertSql, array($userId), array(\PDO::PARAM_INT));
        if ($inserted == 1) {
            return array(0, 0);
        } else {
            $stat = $this->conn->executeQuery($lockSql, array($userId), array(\PDO::PARAM_INT));
            $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
            return array($rows[0]['followee_count'], $rows[0]['followee_position']);
        }
    }

    private function updateUserMeta($userId, $followeeCount, $followeePosition) {
        $sql = 'UPDATE topic_user_meta SET followee_count = ?, followee_position = ? WHERE user_id = ?';
        $this->conn->executeUpdate($sql, array($followeeCount, $followeePosition, $userId),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
    }

    /**
     * @param int $topicId
     *
     * @return array
     * @throws TopicMissingException
     * @throws \Doctrine\DBAL\DBALException
     */
    private function lockTopicMeta($topicId) {
        $sql = 'SELECT follower_count, follower_position FROM topic WHERE id = ? FOR UPDATE';
        $stat = $this->conn->executeQuery($sql, array($topicId), array(\PDO::PARAM_INT));
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        if (count($rows) > 0) {
            return array($rows[0]['follower_count'], $rows[0]['follower_position']);
        } else {
            throw new TopicMissingException();
        }
    }

    private function updateTopicMeta($topicId, $followerCount, $followerPosition) {
        $sql = 'UPDATE topic SET follower_count = ?, follower_position = ? WHERE id = ?';
        $this->conn->executeUpdate($sql, array($followerCount, $followerPosition, $topicId),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
    }

    private function topicAddFollower($topicId, $followerId) {
        list($followerCount, $followerPosition) = $this->lockTopicMeta($topicId);
        $sql = 'INSERT INTO topic_follower(topic_id, position, user_id) VALUES(?, ?, ?)'
            . ' ON DUPLICATE KEY UPDATE position = position';
        $affectedRows = $this->conn->executeUpdate($sql, array($topicId, $followerPosition + 1, $followerId),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
        if ($affectedRows > 0) {
            $this->updateTopicMeta($topicId, $followerCount + 1, $followerPosition + 1);
        }
    }

    private function topicRemoveFollower($topicId, $followerId) {
        list($followerCount, $followerPosition) = $this->lockTopicMeta($topicId);
        $sql = 'DELETE FROM topic_follower WHERE topic_id = ? AND user_id = ?';
        $affectedRows = $this->conn->executeUpdate($sql, array($topicId, $followerId),array(\PDO::PARAM_INT, \PDO::PARAM_INT));
        if ($affectedRows > 0) {
            $this->updateTopicMeta($topicId, $followerCount - 1, $followerPosition);
        }
    }

    /**
     * @param int $userId
     * @param int $topicId
     * @param bool $followedBefore
     *
     * @return bool
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws TopicMissingException
     * @throws FollowingTooMuchTopicException
     * @throws \Exception
     */
    public function follow($userId, $topicId, &$followedBefore = null) {
        try {
            $timeString = (new \DateTime())->format($this->conn->getDatabasePlatform()->getDateTimeFormatString());
            list($followeeCount, $followeePosition) = $this->lockUserMeta($userId);
            if ($followeeCount >= 1000) {
                throw new FollowingTooMuchTopicException();
            }

            $sql = 'INSERT INTO topic_user_following(user_id, state, position, topic_id, create_time) VALUES(?, 1, ?, ?, ?)'
                .' ON DUPLICATE KEY UPDATE position = IF(state = 0, VALUES(position), position), state = IF(state = 0, 1, state)';
            $affectedRows = $this->conn->executeUpdate($sql,
                array($userId, $followeePosition + 1, $topicId, $timeString),
                array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_STR));
            if ($affectedRows > 0) {
                $this->updateUserMeta($userId, $followeeCount + 1, $followeePosition + 1);
            }
            if ($affectedRows != 1) {
                $followedBefore = true;
            } else {
                $followedBefore = false;
            }
            $this->topicAddFollower($topicId, $userId);
            $this->eventDispatcher->dispatch(TopicFollowingEvent::FOLLOW,
                    new TopicFollowingEvent($topicId, $userId, $followedBefore));

            $this->conn->commit();
            return $affectedRows != 0;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * @param int $userId
     * @param int $topicId
     * @param bool $deleted
     * @param bool $favorite
     * @param \DateTime $updateTime
     *
     * @return bool
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    public function followMigrate($userId, $topicId, $deleted, $favorite, $updateTime) {
        try {
            $timeString = $updateTime->format($this->conn->getDatabasePlatform()->getDateTimeFormatString());

            $state = $deleted ? TopicUserFollowing::STATE_DELETED :
                ($favorite ? TopicUserFollowing::STATE_FAVORITE : TopicUserFollowing::STATE_NORMAL);
            list($followeeCount, $followeePosition) = $this->lockUserMeta($userId);

            $checkSql = 'SELECT state FROM topic_user_following WHERE user_id = ? AND topic_id = ?';
            $stateStat = $this->conn->executeQuery($checkSql, array($userId, $topicId));
            $stateResult = $stateStat->fetch(\PDO::FETCH_ASSOC);
            $oldState = $stateResult === false ? TopicUserFollowing::STATE_DELETED : $stateResult['state'];

            $sql = 'INSERT INTO topic_user_following(user_id, state, position, topic_id, create_time) VALUES(?, ?, ?, ?, ?)'
                .' ON DUPLICATE KEY UPDATE state = VALUES(state),'
                .'position = IF(state = 0 AND VALUES(state) > 0, VALUES(position), position)';
            $affectedRows = $this->conn->executeUpdate($sql,
                array($userId, $state, $followeePosition + 1, $topicId, $timeString),
                array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_STR));
            if ($affectedRows > 0) {
                $newCount = $followeeCount;
                $newPosition = $followeePosition;
                if ($oldState == TopicUserFollowing::STATE_DELETED && $state > 0) {
                    $newCount = $followeeCount + 1;
                    $newPosition = $followeePosition + 1;
                } else if ($oldState > 0 && $state == TopicUserFollowing::STATE_DELETED) {
                    $newCount = $followeeCount - 1;
                    $newPosition = $followeePosition;
                }
                if ($stateResult === false && $state == TopicUserFollowing::STATE_DELETED) {
                    $newPosition = $followeePosition + 1;
                }

                $this->updateUserMeta($userId, $newCount, $newPosition);
            }

            if ($deleted) {
                $this->topicRemoveFollower($topicId, $userId);
            } else {
                $this->topicAddFollower($topicId, $userId);
            }

            $this->conn->commit();
            return $affectedRows != 0;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * @param int $userId
     * @param int $topicId
     *
     * @return bool
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    public function unfollow($userId, $topicId) {
        try {
            list($followeeCount, $followeePosition) = $this->lockUserMeta($userId);
            $sql = 'UPDATE topic_user_following SET state = 0 WHERE user_id = ? AND topic_id = ?';
            $affectedRows = $this->conn->executeUpdate($sql, array($userId, $topicId), array(\PDO::PARAM_INT, \PDO::PARAM_INT));
            if ($affectedRows > 0) {
                $this->updateUserMeta($userId, $followeeCount - 1, $followeePosition);
            }

            $this->topicRemoveFollower($topicId, $userId);
            $this->eventDispatcher->dispatch(TopicFollowingEvent::UNFOLLOW,
                new TopicFollowingEvent($topicId, $userId, true));
            $this->conn->commit();
            return $affectedRows > 0;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * @param int $userId
     * @param int $topicId
     *
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function setFavorite($userId, $topicId) {
        $sql = 'UPDATE topic_user_following SET state = 2 WHERE user_id = ? AND topic_id = ? AND state = 1';
        $affectedRows = $this->conn->executeUpdate($sql, array($userId, $topicId), array(\PDO::PARAM_INT, \PDO::PARAM_INT));
        return $affectedRows > 0;
    }

    /**
     * @param int $userId
     * @param int $topicId
     *
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function unsetFavorite($userId, $topicId) {
        $sql = 'UPDATE topic_user_following SET state = 1 WHERE user_id = ? AND topic_id = ? AND state = 2';
        $affectedRows = $this->conn->executeUpdate($sql, array($userId, $topicId), array(\PDO::PARAM_INT, \PDO::PARAM_INT));
        return $affectedRows > 0;
    }

    public function getSortedTopicFollowerIterator($topicId){

	    return new CustomizedCursorableIterator(function($cursor, $step, &$nextCursor) use($topicId) {
		    if ($step == 0) {
			    $nextCursor = $cursor;
			    return array();
		    }

		    $offset = $cursor * $step;
		    $sql = 'SELECT f.user_id, f.position FROM `topic_follower` AS f 
					INNER JOIN `user` AS u ON f.user_id = u.id 
					LEFT OUTER JOIN `user_vip` as v ON v.user_id = u.id 
					WHERE f.topic_id = ? ORDER BY u.level DESC, v.id DESC, f.user_id ASC LIMIT ?, ?';
		    $stat = $this->conn->executeQuery($sql, array($topicId, $offset, $step),
			    array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
		    $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
		    if (count($rows) < $step) {
			    $nextCursor = 0;
		    } else {
			    $nextCursor = $cursor + 1;
		    }
		    return ArrayUtility::columns($rows, 'user_id');
	    });
    }

    public function getTopicFollowerIterator($topicId) {
        return new CustomizedCursorableIterator(function($cursor, $step, &$nextCursor) use($topicId) {
            if ($step == 0) {
                $nextCursor = $cursor;
                return array();
            }
            if ($cursor == 0) {
                $cursor = PHP_INT_MAX;
            }
            $sql = 'SELECT user_id, position FROM topic_follower '
                .'WHERE topic_id = ? AND position < ? ORDER BY position DESC LIMIT ?';
            $stat = $this->conn->executeQuery($sql, array($topicId, $cursor, $step),
                array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
            $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
            if (count($rows) < $step) {
                $nextCursor = 0;
            } else {
                $nextCursor = $rows[count($rows) - 1]['position'];
            }
            return ArrayUtility::columns($rows, 'user_id');
        });
    }

    public function getUserFolloweeIterator($userId) {
        return new UserFollowingIterator($this->conn, $userId);
    }

    public function getTopicsFollowerCounter($topicIds) {
        if (count($topicIds) == 0) {
            $map = array();
        } else {
            $sql = 'SELECT id, follower_count FROM topic WHERE id IN ('.implode(',', $topicIds).')';
            $stat = $this->conn->executeQuery($sql);
            $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
            $map = ArrayUtility::columns($rows, 'follower_count', 'id');
        }
        return new Counter($map);
    }

    public function getTopicFollowerCount($topicId) {
        return $this->getTopicsFollowerCounter(array($topicId))->getCount($topicId);
    }

    public function getUsersFollowingCounter($userIds) {
        if (count($userIds) == 0) {
            $map = array();
        } else {
            $sql = 'SELECT user_id, followee_count FROM topic_user_meta WHERE user_id IN ('.implode(',', $userIds).')';
            $stat = $this->conn->executeQuery($sql);
            $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
            $map = ArrayUtility::columns($rows, 'followee_count', 'user_id');
        }
        return new Counter($map);
    }

    public function getUserFollowingCount($userId) {
        return $this->getUsersFollowingCounter(array($userId))->getCount($userId);
    }

    public function getUserFollowingResolver($userId, $topicIds) {
        if (count($topicIds) == 0) {
            $map = array();
        } else {
            $sql = 'SELECT topic_id, state FROM topic_user_following '
                .'WHERE user_id = ? AND topic_id IN ('.implode(',', $topicIds).') AND state > 0';
            $stat = $this->conn->executeQuery($sql, array($userId), array(\PDO::PARAM_INT));
            $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
            $map = ArrayUtility::columns($rows, 'state', 'topic_id');
        }

        return new FollowingResolver($map);
    }

    /**
     * @param int $userId
     * @param int $topicId
     * @return bool
     */
    public function isFollowing($userId, $topicId) {
        return $this->getUserFollowingResolver($userId, array($topicId))->isFollowing($topicId);
    }
    
    public function filterTopicFollower($topicId, $userIds) {
        if (empty($userIds)) {
            return array();
        }
        
        $sql = 'SELECT user_id FROM topic_follower WHERE topic_id = '.intval($topicId).' AND user_id IN ('
            . implode(',', $userIds) . ')';
        $stat = $this->conn->executeQuery($sql);
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        return ArrayUtility::columns($rows, 'user_id');
    }

	/**
	 * @param $userId
	 * @param $keyword
	 *
	 * @return array
	 */
    public function searchUserFollowingTopicByKeyword($userId, $keyword) {
    	$sql = 'SELECT f.topic_id, t.title
			FROM topic_user_following f
			JOIN topic t ON t.id = f.topic_id
			WHERE f.user_id=:userId AND f.state <> 0 AND t.deleted=0 AND t.title LIKE :keyword';
	    $stmt = $this->conn->prepare($sql);
	    $keyword = '%'.$keyword.'%';
	    $stmt->bindParam(':userId', $userId);
	    $stmt->bindParam(':keyword', $keyword);
	    $stmt->execute();

	    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

	/**
	 * @param $followerId
	 *
	 * @return array
	 */
    public function fetchTopicIdsByFollower($followerId) {
    	$sql = 'SELECT topic_id FROM topic_user_following WHERE user_id=:followerId AND state<>:state';
    	$stmt = $this->conn->prepare($sql);
	    $stmt->bindValue(':followerId', $followerId);
	    $stmt->bindValue(':state', TopicUserFollowing::STATE_DELETED, \PDO::PARAM_INT);
	    $stmt->execute();
	    $result = $stmt->fetchAll();
	    $topicIds = array_map(function($item) { return $item['topic_id']; }, $result);
	    $topicIds = array_unique($topicIds);

	    return $topicIds;
    }


    /**
     * 订阅帖子创建事件，自动关注次元
     *
     * @param $eventBody
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function followOnCreatePost($eventBody) {
        $postId = $eventBody['postId'];
        $postService = $this->container->get('lychee.module.post');
        $post = $postService->fetchOne($postId);
        if (empty($post)) {
            return false;
        }

        $userId = $post->authorId;
        $topicId = $post->topicId;

        $r = $this->isFollowing($userId, $post->topicId);
        if ($r) {
            return true;
        }

        // 自动关注
        try {
            $this->follow($userId, $topicId);
        } catch (FollowingTooMuchTopicException $e) {
            return false;
        } catch (TopicMissingException $e) {
            return false;
        } catch (\Exception $e) {
            throw new $e;
        }

        return true;
    }
}