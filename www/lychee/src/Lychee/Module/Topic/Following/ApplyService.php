<?php
namespace Lychee\Module\Topic\Following;

use Doctrine\DBAL\Connection;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Lychee\Module\Topic\Exception\FollowingTooMuchTopicException;
use Lychee\Module\Topic\Exception\TopicMissingException;
use Doctrine\ORM\EntityManagerInterface;
use Lychee\Module\Topic\Entity\TopicFollowingApplication;

class ApplyService {

    /**
     * @var EntityManagerInterface
     */
    private $em;
    private $followingService;

    /**
     * @param RegistryInterface $registry
     * @param TopicFollowingService $followingService
     */
    public function __construct($registry, $followingService) {
        $this->em = $registry->getManager();
        $this->followingService = $followingService;
    }

    private function generatePosition() {
        return (int)floor(microtime(true) * 1000);
    }

    /**
     * @param int $userId
     * @param int $topicId
     * @param string $description
     *
     * @return bool
     */
    public function apply($userId, $topicId, $description) {
        $conn = $this->em->getConnection();
        $now = time();
        $sixHourAgo = $now - 3600 * 6;

        $selectSql = 'SELECT apply_time FROM topic_following_application WHERE topic_id = ? '
            .'AND applicant_id = ?';
        $stat = $conn->executeQuery($selectSql, array($topicId, $userId), array(\PDO::PARAM_INT, \PDO::PARAM_INT));
        $r = $stat->fetch(\PDO::FETCH_ASSOC);
        if ($r) {
            $lastApplyTime = $r['apply_time'];
            if ($lastApplyTime < $sixHourAgo) {
                $position = $this->generatePosition();
                $updateSql = 'UPDATE topic_following_application SET position = ?, apply_time = ?, '
                    .'apply_description = ? WHERE topic_id = ? AND applicant_id = ?';
                $affectRow = $conn->executeUpdate($updateSql, array($position, $now, $description, $topicId, $userId),
                    array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT));
                return $affectRow > 0;
            } else {
                $updateSql = 'UPDATE topic_following_application SET apply_description = ? '
                    .'WHERE topic_id = ? AND applicant_id = ?';
                $conn->executeUpdate($updateSql, array($description, $topicId, $userId),
                    array(\PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT));
                return false;
            }
        } else {
            if ($this->followingService->isFollowing($userId, $topicId)) {
                return false;
            }
            $insertSql = 'INSERT INTO topic_following_application(topic_id, applicant_id, position, '
                .'apply_time, apply_description) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE '
                .'apply_description = VALUES(apply_description)';
            $position = $this->generatePosition();
            $affectRow = $conn->executeUpdate($insertSql, array($topicId, $userId, $position, $now,
                $description), array(\PDO::PARAM_INT, \PDO::PARAM_INT,
                \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_STR));

            return $affectRow == 1;
        }
    }

    /**
     * @param int $topicId
     * @param int $userId
     * @return bool
     *
     * @throws FollowingTooMuchTopicException
     * @throws TopicMissingException
     */
    public function confirm($topicId, $userId) {
        $sql = 'DELETE FROM topic_following_application WHERE topic_id = ? AND applicant_id = ?';
        $deletedRow = $this->em->getConnection()->executeUpdate($sql, array($topicId, $userId),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT));
        if ($deletedRow > 0) {
            $this->followingService->follow($userId, $topicId);
        }

        return $deletedRow > 0;
    }

    /**
     * @param int $topicId
     * @param int $userId
     * @return bool
     */
    public function reject($topicId, $userId) {
        $sql = 'DELETE FROM topic_following_application WHERE topic_id = ? AND applicant_id = ?';
        $deletedRow = $this->em->getConnection()->executeUpdate($sql, array($topicId, $userId),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT));

        return $deletedRow > 0;
    }

    /**
     * @param int $topicId
     * @param int|string $cursor
     * @param int $count
     * @param int|string $nextCursor
     *
     * @return TopicFollowingApplication[]
     */
    public function fetchApplicationsByTopic($topicId, $cursor, $count, &$nextCursor) {
        if ($count <= 0) {
            $nextCursor = $cursor;
            return array();
        }
        @list($position, $applicantId) = explode(',', $cursor);
        $position = intval($position);
        if ($position == 0) {
            $position = PHP_INT_MAX;
        }
        $applicantId = intval($applicantId);

        $query = $this->em->createQuery('SELECT a FROM '.TopicFollowingApplication::class
            .' a WHERE a.topicId = :topic AND ((a.position = :position AND a.applicantId < :applicantId) OR a.position < :position)'
            .' ORDER BY a.position DESC, a.applicantId DESC');
        $query->setParameters(array('topic' => $topicId, 'position' => $position, 'applicantId' => $applicantId));
        $query->setMaxResults($count);
        $result = $query->getResult();

        if (count($result) < $count) {
            $nextCursor = 0;
        } else {
            /** @var TopicFollowingApplication $last */
            $last = $result[count($result) - 1];
            $nextCursor = $last->position . ',' . $last->applicantId;
        }

        return $result;
    }

    public function clearTimeoutApplications() {
        $oneMonthAgo = new \DateTime();
        $oneMonthAgo->sub(new \DateInterval('P1M'));
        $oneMonthAgo->setTime(0, 0, 0);
        $conn = $this->em->getConnection();
        $sql = 'DELETE FROM topic_following_application WHERE apply_time < ?';
        $conn->executeUpdate($sql, array($oneMonthAgo->getTimestamp()), array(\PDO::PARAM_INT));
    }
}