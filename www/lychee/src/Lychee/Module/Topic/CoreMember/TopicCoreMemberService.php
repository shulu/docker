<?php
namespace Lychee\Module\Topic\CoreMember;

use Doctrine\ORM\EntityManagerInterface;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Topic\Exception\TooMuchCoreMemberException;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\DBAL\Connection;
use Lychee\Module\Topic\Entity\TopicCoreMember;

class TopicCoreMemberService {

    const MAX_CORE_MEMBER = 9;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * TopicCoreMemberService constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct($registry) {
        $this->em = $registry->getManager();
    }


    /**
     * @param int $topicId
     *
     * @return TopicCoreMember[]
     */
    public function getCoreMembers($topicId) {
        $sql = 'SELECT m FROM '.TopicCoreMember::class.' m WHERE m.topicId = :topicId ORDER BY m.order ASC';
        $query = $this->em->createQuery($sql);
        $query->setParameters(array('topicId' => $topicId));
        $query->execute();
        return $query->getResult();
    }

    /**
     * @param array $topicIdAndUserIds
     * @return CoreMemberTitleResolver
     */
    public function getTitleResolver($topicIdAndUserIds) {
        if (empty($topicIdAndUserIds)) {
            return new CoreMemberTitleResolver(array());
        }
        $groups = array();
        foreach ($topicIdAndUserIds as $item) {
            $topicId = intval($item[0]);
            if ($topicId > 0) {
                if (isset($groups[$topicId])) {
                    $groups[$topicId][] = $item[1];
                } else {
                    $groups[$topicId] = array($item[1]);
                }
            }
        }

        $sqls = array();
        foreach ($groups as $topicId => $userIds) {
            $sqls[] = '(SELECT title, topic_id, user_id FROM topic_core_members WHERE topic_id = '
                .$topicId.' AND user_id IN ('.implode(',', array_unique($userIds)).'))';
        }
        $sql = implode('UNION ALL', $sqls);
        $stat = $this->em->getConnection()->executeQuery($sql);
        $result = $stat->fetchAll(\PDO::FETCH_ASSOC);
        $map = array();
        foreach ($result as $row) {
            $topicId = $row['topic_id'];
            $userId = $row['user_id'];
            if (isset($map[$topicId])) {
                $map[$topicId][$userId] = $row['title'];
            } else {
                $map[$topicId] = array($userId => $row['title']);
            }
        }
        return new CoreMemberTitleResolver($map);
    }

    /**
     * @param int $topicId
     * @param int $userId
     * @param string $title
     * @return bool
     *
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws TooMuchCoreMemberException
     * @throws \Exception
     */
    public function addCoreMember($topicId, $userId, $title) {
        $conn = $this->em->getConnection();
        try {
            $conn->beginTransaction();
            $count = $this->lockMeta($conn, $topicId);
            if ($count == self::MAX_CORE_MEMBER) {
                throw new TooMuchCoreMemberException();
            }
            $insertSql = 'INSERT INTO topic_core_members(topic_id, user_id, `order`, title) VALUES(?, ?, ?, ?)'
                .' ON DUPLICATE KEY UPDATE title = title';
            $affectedRows = $conn->executeUpdate($insertSql, array($topicId, $userId, $count + 1, $title),
                array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_STR));
            if ($affectedRows == 1) {
                $this->updateMeta($conn, $topicId, $count + 1);
            }
            $conn->commit();

            return $affectedRows == 1;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    /**
     * @param Connection $conn
     * @param int $topicId
     * @return int
     */
    private function lockMeta($conn, $topicId) {
        $count = $this->tryLockAndGetCount($conn, $topicId);
        if ($count !== false) {
            return $count;
        }

        $conn->rollBack();
        $conn->beginTransaction();
        $insertSql = 'INSERT INTO topic_core_member_meta(topic_id, core_member_count) VALUES(?, 0)'
            .' ON DUPLICATE KEY UPDATE core_member_count = core_member_count';
        $affactedRows = $conn->executeUpdate($insertSql, array($topicId), array(\PDO::PARAM_INT));
        if ($affactedRows == 1) {
            return 0;
        } else {
            $count = $this->tryLockAndGetCount($conn, $topicId);
            if ($count !== false) {
                return $count;
            }
        }

        throw new \LogicException('can not reach here.');
    }

    /**
     * @param Connection $conn
     * @param int $topicId
     *
     * @return int|false
     */
    private function tryLockAndGetCount($conn, $topicId) {
        $sql = 'SELECT core_member_count FROM topic_core_member_meta WHERE topic_id = ? FOR UPDATE';
        $stat = $conn->executeQuery($sql, array($topicId), array(\PDO::PARAM_INT));
        $items = $stat->fetchAll();
        if (!empty($items)) {
            return intval($items[0]['core_member_count']);
        } else {
            return false;
        }
    }

    /**
     * @param Connection $conn
     * @param int $topicId
     * @param int $count
     */
    private function updateMeta($conn, $topicId, $count) {
        $sql = 'UPDATE topic_core_member_meta SET core_member_count = ? WHERE topic_id = ?';
        $conn->executeUpdate($sql, array($count, $topicId),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT));
    }

    /**
     * @param Connection $conn
     * @param int $topicId
     * @param int $userId
     * @param int $order
     * @param string $title
     */
    private function insertCoreMember($conn, $topicId, $userId, $order, $title) {
        $sql = 'INSERT INTO topic_core_members(topic_id, user_id, `order`, title) VALUES(?, ?, ?, ?)';
        $conn->executeUpdate($sql, array($topicId, $userId, $order, $title),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_STR));
    }

    /**
     * @param int $topicId
     * @param int $userId
     * @return bool
     *
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    public function removeCoreMember($topicId, $userId) {
        $conn = $this->em->getConnection();
        try {
            $conn->beginTransaction();
            $deleteSql = 'DELETE FROM topic_core_members WHERE topic_id = ? AND user_id = ?';
            $affactedRows = $conn->executeUpdate($deleteSql, array($topicId, $userId),
                array(\PDO::PARAM_INT, \PDO::PARAM_INT));
            if ($affactedRows == 1) {
                $sql = 'UPDATE topic_core_member_meta SET core_member_count = core_member_count - 1 WHERE topic_id = ?';
                $conn->executeUpdate($sql, array($topicId), array(\PDO::PARAM_INT));
            }
            $conn->commit();
            return $affactedRows == 1;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    /**
     * @param int $topicId
     * @param int $userId
     * @param string $title
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function updateTitle($topicId, $userId, $title) {
        $sql = 'UPDATE topic_core_members SET title = ? WHERE topic_id = ? AND user_id = ?';
        $this->em->getConnection()->executeUpdate($sql, array($title, $topicId, $userId),
            array(\PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT));
    }

    /**
     * @param int $topicId
     * @param int[] $orderedUserIds
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function updateOrder($topicId, $orderedUserIds) {
        if (count($orderedUserIds) == 0) {
            return;
        }

        $sql = 'UPDATE topic_core_members SET `order` = (CASE user_id';
        foreach ($orderedUserIds as $index => $userId) {
            $sql .= ' WHEN '.$userId.' THEN '.($index + 1);
        }
        $sql .= ' END) WHERE topic_id = '.intval($topicId).' AND user_id IN('
            .implode(',', $orderedUserIds).')';
        $this->em->getConnection()->executeUpdate($sql);
    }

    /**
     * @param int $topicId
     * @param int $userId
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function isCoreMember($topicId, $userId) {
        $sql = 'SELECT 1 FROM topic_core_members WHERE topic_id = ? AND user_id = ?';
        $stat = $this->em->getConnection()->executeQuery($sql, array($topicId, $userId),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT));
        return count($stat->fetchAll()) > 0;
    }

}