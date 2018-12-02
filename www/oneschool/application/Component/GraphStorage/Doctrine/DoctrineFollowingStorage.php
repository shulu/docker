<?php
namespace Lychee\Component\GraphStorage\Doctrine;

use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\GraphStorage\FollowingCounter;
use Lychee\Component\GraphStorage\FollowingResolver;
use Lychee\Component\GraphStorage\FollowingStorage;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Connection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Lychee\Component\GraphStorage\ParticalFollowingResolver;
use Lychee\Component\GraphStorage\ParticalFollowingCounter;

class DoctrineFollowingStorage implements FollowingStorage {

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $entityName;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var string
     */
    private $metaTableName;

    /**
     * @param EntityManager $entityManager
     * @param string $managerName
     * @param string $entityName
     */
    public function __construct($entityManager, $entityName, $metaEntityName) {
        $this->entityManager = $entityManager;
        $this->connection = $this->entityManager->getConnection();
        $this->entityName = $entityName;
        $this->tableName = $this->entityManager->getClassMetadata($entityName)->getTableName();
        if ($metaEntityName) {
            $this->metaTableName = $this->entityManager->getClassMetadata($metaEntityName)->getTableName();
        } else {
            $this->metaTableName = null;
        }
    }

    /**
     * @param int $follower
     * @param int $followee
     */
    public function follow($follower, $followee) {
        $ret = [];
        $ret['isFollowed'] = false;

        $this->connection->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
        $this->connection->transactional(function() use ($follower, $followee) {
            $updateTime = $this->connection->convertToDatabaseValue(new \DateTime(), 'datetime');
            $this->connection->setFetchMode(\PDO::FETCH_ASSOC);
            $fetchResult = $this->connection->fetchAll(sprintf('
                SELECT state FROM %s
                WHERE follower_id = ? AND followee_id = ?
                FOR UPDATE
            ', $this->tableName), array($follower, $followee));

            if (count($fetchResult) > 0) {

                $ret['isFollowed'] = true;

                if ($fetchResult[0]['state'] != AbstractFollowing::STATE_REMOVED) {
                    return $ret;
                }
                $effectedCount = $this->connection->executeUpdate(sprintf('
                    UPDATE %s SET state = ?, update_time = ?
                    WHERE follower_id = ? AND followee_id = ? AND state = ?
                ', $this->tableName), array(
                    AbstractFollowing::STATE_NORMAL, $updateTime, $follower, $followee, AbstractFollowing::STATE_REMOVED
                ));
            } else {
                $effectedCount = $this->connection->executeUpdate(sprintf('
                    INSERT INTO %s(follower_id, followee_id, state, update_time)
                    VALUES(?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE state = ?, update_time = ?
                ', $this->tableName), array(
                    $follower, $followee, AbstractFollowing::STATE_NORMAL, $updateTime, AbstractFollowing::STATE_NORMAL, $updateTime
                ));
            }

            if ($this->metaTableName) {
                $this->connection->executeUpdate(sprintf('
                    INSERT INTO %s (target_id, follower_count, followee_count)
                    VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE followee_count = followee_count + ?;
                    INSERT INTO %s (target_id, follower_count, followee_count)
                    VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE follower_count = follower_count + ?
                ', $this->metaTableName, $this->metaTableName), array(
                    $follower, 0, $effectedCount, $effectedCount,
                    $followee, $effectedCount, 0, $effectedCount
                ));
            }
        });

        return $ret;
    }

    /**
     * @param int   $follower
     * @param array $followees
     */
    public function multiFollow($follower, $followees) {
        if (count($followees) === 0) {
            return;
        }
        $this->connection->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
        $this->connection->transactional(function() use ($follower, $followees) {
            $updateTime = $this->connection->convertToDatabaseValue(new \DateTime(), 'datetime');
            $this->connection->setFetchMode(\PDO::FETCH_ASSOC);

            $fetchResult = $this->connection->fetchAll(sprintf('
                SELECT followee_id, state FROM %s
                WHERE follower_id = ? AND followee_id IN (%s)
                FOR UPDATE
            ', $this->tableName, $this->buildInSql(count($followees))), array_merge(
                array($follower), $followees)
            );
            $followedBefore = array();
            $hadFollowed = array();
            foreach ($fetchResult as $eachResult) {
                if ($eachResult['state'] == AbstractFollowing::STATE_REMOVED) {
                    $followedBefore[] = $eachResult['followee_id'];
                }
                $hadFollowed[] = $eachResult['followee_id'];
            }

            $effectedCount = 0;
            if (count($followedBefore) > 0) {
                $updatedCount = $this->connection->executeUpdate(sprintf('
                    UPDATE %s SET state = ?, update_time = ?
                    WHERE follower_id = ? AND followee_id IN (%s) AND state = ?
                ', $this->tableName, $this->buildInSql(count($followedBefore))),
                    array_merge(
                        array(AbstractFollowing::STATE_NORMAL, $updateTime, $follower),
                        $followedBefore,
                        array(AbstractFollowing::STATE_REMOVED)
                    )
                );

                $effectedCount += $updatedCount;
            }

            $newFollowees = ArrayUtility::diffValue($followees, $hadFollowed);
            if (count($newFollowees) > 0) {
                $valuesSql = implode(',', array_pad(array(), count($newFollowees), '(?,?,?,?)'));
                $params = array();
                foreach ($newFollowees as $followeeId) {
                    $params[] = $follower;
                    $params[] = $followeeId;
                    $params[] = AbstractFollowing::STATE_NORMAL;
                    $params[] = $updateTime;
                }
                $params[] = AbstractFollowing::STATE_NORMAL;
                $params[] = $updateTime;

                $insertedCount = $this->connection->executeUpdate(sprintf('
                    INSERT INTO %s(follower_id, followee_id, state, update_time)
                    VALUES %s
                    ON DUPLICATE KEY UPDATE state = ?, update_time = ?
                ', $this->tableName, $valuesSql), $params);

                $effectedCount += $insertedCount;
            }

            if ($this->metaTableName) {
                $updateMetaSqls = array();
                $updateMetaSqls[] = sprintf('
                    INSERT INTO %s (target_id, follower_count, followee_count)
                    VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE followee_count = followee_count + ?
                ', $this->metaTableName);
                $updateMetaParams = array($follower, 0, $effectedCount, $effectedCount);
                foreach (array_merge($followedBefore, $newFollowees) as $eachfollowee) {
                    $updateMetaSqls[] = sprintf('
                        INSERT INTO %s (target_id, follower_count, followee_count)
                        VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE follower_count = follower_count + ?
                    ', $this->metaTableName);
                    $updateMetaParams[] = $eachfollowee;
                    $updateMetaParams[] = 1;
                    $updateMetaParams[] = 0;
                    $updateMetaParams[] = 1;
                }

                $this->connection->executeUpdate(implode(';', $updateMetaSqls), $updateMetaParams);
            }

        });
    }

    /**
     * @param int $follower
     * @param int $followee
     */
    public function unfollow($follower, $followee) {
        $this->connection->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
        $this->connection->transactional(function() use ($follower, $followee) {
            $updateTime = $this->connection->convertToDatabaseValue(new \DateTime(), 'datetime');

            $updatedCount = $this->connection->executeUpdate(sprintf('
                UPDATE %s SET state = ?, update_time = ?
                WHERE follower_id = ? AND followee_id = ? AND state != ?
            ', $this->tableName), array(
                AbstractFollowing::STATE_REMOVED, $updateTime, $follower, $followee, AbstractFollowing::STATE_REMOVED
            ));
            if ($updatedCount > 0 && $this->metaTableName) {
                $this->connection->executeUpdate(sprintf('
                    UPDATE %s SET followee_count = followee_count - 1
                    WHERE target_id = ?;
                    UPDATE %s SET follower_count = follower_count - 1
                    WHERE target_id = ?;
                ', $this->metaTableName, $this->metaTableName), array(
                    $follower, $followee
                ));
            }
        });
    }

    /**
     * @param int   $follower
     * @param array $followees
     */
    public function multiUnfollow($follower, $followees) {
        $this->connection->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
        $this->connection->transactional(function() use ($follower, $followees) {
            $updateTime = $this->connection->convertToDatabaseValue(new \DateTime(), 'datetime');
            $this->connection->setFetchMode(\PDO::FETCH_ASSOC);

            $fetchResult = $this->connection->fetchAll(sprintf('
                SELECT followee_id FROM %s
                WHERE follower_id = ? AND followee_id IN (%s) AND state != ?
                FOR UPDATE
            ', $this->tableName, $this->buildInSql(count($followees))), array_merge(
                array($follower), $followees, array(AbstractFollowing::STATE_REMOVED)
            ));
            $followingIds = ArrayUtility::columns($fetchResult, 'followee_id');

            $this->connection->executeUpdate(sprintf('
                UPDATE %s SET state = ?, update_time = ?
                WHERE follower_id = ? AND followee_id IN (%s)
            ', $this->tableName, $this->buildInSql(count($followingIds))), array_merge(
                array(AbstractFollowing::STATE_REMOVED, $updateTime, $follower), $followingIds
            ));

            if ($this->metaTableName) {
                $updateMetaSqls = array();
                $updateMetaSqls[] = sprintf('
                    UPDATE %s SET followee_count = followee_count - ? WHERE target_id = ?
                ', $this->metaTableName);
                $updateMetaParams = array(count($followingIds), $follower);
                foreach ($followingIds as $eachfollowee) {
                    $updateMetaSqls[] = sprintf('
                        UPDATE %s SET follower_count = follower_count - ? WHERE target_id = ?
                    ', $this->metaTableName);
                    $updateMetaParams[] = 1;
                    $updateMetaParams[] = $eachfollowee;
                }
                $this->connection->executeUpdate(implode(';', $updateMetaSqls), $updateMetaParams);
            }

        });
    }

    private function buildInSql($count) {
        return implode(',', array_pad(array(), $count, '?'));
    }

    /**
     * @param int $followee
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     *
     * @return array
     */
    public function fetchFollowers($followee, $cursor, $count, &$nextCursor) {
        if ($count <= 0) {
            $nextCursor = $cursor;
            return array();
        }
        if ($cursor == 0) {
            $cursor = PHP_INT_MAX;
        }

        $statement = $this->connection->executeQuery(sprintf('
            SELECT id, follower_id FROM %s
            WHERE followee_id = ? AND state = ? AND id < ?
            ORDER BY id DESC
            LIMIT ?
        ', $this->tableName), array($followee, AbstractFollowing::STATE_NORMAL, $cursor, intval($count)),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

        if (count($result) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $result[count($result) - 1]['id'];
        }

        return ArrayUtility::columns($result, 'follower_id');
    }

    /**
     * @param int $follower
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     *
     * @return array
     */
    public function fetchFollowees($follower, $cursor, $count, &$nextCursor) {
        if ($count <= 0) {
            $nextCursor = $cursor;
            return array();
        }
        if ($cursor == 0) {
            $cursor = PHP_INT_MAX;
        }

        $statement = $this->connection->executeQuery(sprintf('
            SELECT id, followee_id FROM %s
            WHERE follower_id = ? AND state = ? AND id < ?
            ORDER BY id DESC
            LIMIT ?
        ', $this->tableName), array($follower, AbstractFollowing::STATE_NORMAL, $cursor, intval($count)),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

        if (count($result) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $result[count($result) - 1]['id'];
        }

        return ArrayUtility::columns($result, 'followee_id');
    }

    /**
     * @param int $followee
     *
     * @return int
     */
    public function countFollowers($followee) {
        if ($this->metaTableName) {
            $statement = $this->connection->executeQuery(
                sprintf('SELECT follower_count FROM %s WHERE target_id = ?', $this->metaTableName),
                array($followee)
            );
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
	        if (!$result) {
	        	return 0;
	        }
            return $result[0]['follower_count'];
        } else {
            $statement = $this->connection->executeQuery(
                sprintf('SELECT count(id) follower_count FROM %s WHERE followee_id = ? AND state = ?', $this->tableName),
                array($followee, AbstractFollowing::STATE_NORMAL),
                array(\PDO::PARAM_INT, \PDO::PARAM_INT)
            );
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            return intval($result[0]['follower_count']);
        }

    }

    /**
     * @param int $follower
     *
     * @return int
     */
    public function countFollowees($follower) {
        if ($this->metaTableName) {
            $statement = $this->connection->executeQuery(
                sprintf('SELECT followee_count FROM %s WHERE target_id = ?', $this->metaTableName),
                array($follower), array(\PDO::PARAM_INT)
            );
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            return isset($result[0]['followee_count']) ? $result[0]['followee_count'] : 0;
        } else {
            $statement = $this->connection->executeQuery(
                sprintf('SELECT count(id) followee_count FROM %s WHERE follower_id = ? AND state = ?', $this->tableName),
                array($follower, AbstractFollowing::STATE_NORMAL), array(\PDO::PARAM_INT, \PDO::PARAM_INT)
            );
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            return intval($result[0]['followee_count']);
        }
    }

    /**
     * @param int $source
     * @param int $target
     *
     * @return boolean
     */
    public function isFollowing($source, $target) {
        $statement = $this->entityManager->getConnection()->prepare('
            SELECT 1 FROM '.$this->tableName.'
            WHERE follower_id = :followerId
            AND followee_id = :followeeId
            AND state = '.AbstractFollowing::STATE_NORMAL.'
        ');
        $statement->bindParam('followerId', $source, \PDO::PARAM_INT);
        $statement->bindParam('followeeId', $target, \PDO::PARAM_INT);
        $statement->execute();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return count($result) > 0;
    }

    /**
     * @param int   $source
     * @param array $targets
     * @param int   $hint
     *
     * @return FollowingResolver
     */
    public function buildResolver($source, $targets, $hint = FollowingResolver::HINT_NONE) {
        if ($source === 0 || empty($targets)) {
            return new ParticalFollowingResolver(array(), array());
        }

        if ($hint !== FollowingResolver::HINT_FOLLOWER && $hint !== FollowingResolver::HINT_NO_FOLLOWER) {
            $followers = $this->findFollowings('followee_id', $source, 'follower_id', $targets);
        }
        if ($hint !== FollowingResolver::HINT_FOLLOWEE && $hint !== FollowingResolver::HINT_NO_FOLLOWEE) {
            $followees = $this->findFollowings('follower_id', $source, 'followee_id', $targets);
	        if (!in_array($source, $followees)) {
	        	array_push($followees, $source);
	        }
        }
        return new ParticalFollowingResolver(
            array_fill_keys(isset($followers) ? $followers : array(), true),
            array_fill_keys(isset($followees) ? $followees : array(), true),
            $hint
        );
    }

    private function findFollowings($sourceField, $source, $targetField, $targets) {
        $inSql = implode(',', array_pad(array(), count($targets), '?'));
        $sql = sprintf('SELECT %s FROM %s WHERE %s = ? AND state = %d AND %s IN (%s)',
            $targetField, $this->tableName, $sourceField,
            AbstractFollowing::STATE_NORMAL, $targetField, $inSql);
        $params = array_merge(array($source), $targets);
        $stat = $this->connection->executeQuery($sql, $params, array_pad(array(), count($targets) + 1, \PDO::PARAM_INT));
        $result = $stat->fetchAll(\PDO::FETCH_ASSOC);
        return ArrayUtility::columns($result, $targetField);
    }

    /**
     * @param array $sources
     * @param int   $hint
     *
     * @return FollowingCounter
     */
    public function buildCounter($sources, $hint = FollowingCounter::HINT_NONE) {
        if (empty($sources)) {
            return new ParticalFollowingCounter(array(), array());
        }

        if ($this->metaTableName) {
            $sql = sprintf(
                'SELECT target_id, follower_count, followee_count FROM %s WHERE target_id IN (%s)',
                $this->metaTableName, $this->buildInSql(count($sources)));
            $statement = $this->connection->executeQuery($sql, $sources,
                array_pad(array(), count($sources), \PDO::PARAM_INT));
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

            if ($hint !== FollowingCounter::HINT_NO_FOLLOWER) {
                $followersCountMap = ArrayUtility::columns($result, 'follower_count', 'target_id');
            }
            if ($hint !== FollowingCounter::HINT_NO_FOLLOWEE) {
                $followeesCountMap = ArrayUtility::columns($result, 'followee_count', 'target_id');
            }
        } else {
            if ($hint !== FollowingCounter::HINT_NO_FOLLOWER) {
                $statement = $this->connection->executeQuery(
                    sprintf('SELECT followee_id, count(id) follower_count
                        FROM %s WHERE followee_id IN (%s) AND state = ?',
                        $this->tableName, $this->buildInSql(count($sources))
                    ),
                    array_merge($sources, array(AbstractFollowing::STATE_NORMAL))
                );
                $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
                $followersCountMap = ArrayUtility::columns($result, 'follower_count', 'followee_id');
            }
            if ($hint !== FollowingCounter::HINT_NO_FOLLOWEE) {
                $statement = $this->connection->executeQuery(
                    sprintf('SELECT follower_id, count(id) followee_count
                        FROM %s WHERE follower_id IN (%s) AND state = ?',
                        $this->tableName, $this->buildInSql(count($sources))
                    ),
                    array_merge($sources, array(AbstractFollowing::STATE_NORMAL))
                );
                $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
                $followeesCountMap = ArrayUtility::columns($result, 'followee_count', 'follower_id');
            }
        }

        return new ParticalFollowingCounter(
            isset($followersCountMap) ? $followersCountMap : array(),
            isset($followeesCountMap) ? $followeesCountMap : array()
        );
    }

} 