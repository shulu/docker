<?php
namespace Lychee\Module\Activity;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Lychee\Component\Database\DoctrineUtility;
use Lychee\Component\Foundation\CursorableIterator\QueryCursorableIterator;
use Lychee\Module\Activity\Entity\Activity;
use Lychee\Component\Foundation\ArrayUtility;

class ActivityService {
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct($registry) {
        $this->entityManager = $registry->getManager();
    }

    public function fetch($ids) {
        $activities = $this->entityManager
            ->getRepository(Activity::class)
            ->findBy(array('id' => $ids));
        return ArrayUtility::mapByColumn($activities, 'id');
    }

    /**
     * @param int $userId
     * @param int $cursor
     * @param int $count
     * @param int|null $nextCursor
     *
     * @return array
     */
    public function fetchIdsByUser($userId, $cursor, $count, &$nextCursor = null) {
        return $this->fetchIdsByUsers(array($userId), $cursor, $count, $nextCursor);
    }

    /**
     * @param array $userIds
     * @param int $cursor
     * @param int $count
     * @param int|null $nextCursor
     *
     * @return array
     */
    public function fetchIdsByUsers($userIds, $cursor, $count, &$nextCursor = null) {
        if ($count === 0 || empty($userIds)) {
            return array();
        }
        if ($cursor === 0) {
            $cursor = PHP_INT_MAX;
        }

        $sql = 'SELECT id FROM activity USE INDEX (user_id_index) WHERE user_id IN ('.implode(',', $userIds)
            .') AND id < '.$cursor.' ORDER BY id DESC LIMIT '.$count;
        $stat = $this->entityManager->getConnection()->executeQuery($sql);
        $ids = ArrayUtility::columns($stat->fetchAll(\PDO::FETCH_ASSOC), 'id');

        if (count($ids) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $ids[count($ids) - 1];
        }

        return $ids;
    }

    private function addActivite($userId, $action, $targetId) {
        $activite = new Activity();
        $activite->createTime = new \DateTime();
        $activite->userId = $userId;
        $activite->action = $action;
        $activite->targetId = $targetId;

        $this->entityManager->persist($activite);
        $this->entityManager->flush($activite);
    }

    /**
     * @param int $userId
     * @param int $postId
     */
    public function addPostActivite($userId, $postId) {
        $this->addActivite($userId, Activity::ACTION_POST, $postId);
    }

    /**
     * @param int $userId
     * @param int $followeeId
     */
    public function addFollowUserActivite($userId, $followeeId) {
        $this->addActivite($userId, Activity::ACTION_FOLLOW_USER, $followeeId);
    }

    /**
     * @param int $userId
     * @param int $topicId
     */
    public function addFollowTopicActivite($userId, $topicId) {
        $this->addActivite($userId, Activity::ACTION_FOLLOW_TOPIC, $topicId);
    }

    /**
     * @param int $userId
     * @param int $postId
     */
    public function addLikePostActivite($userId, $postId) {
        $this->addActivite($userId, Activity::ACTION_LIKE_POST, $postId);
    }

    /**
     * @param int $userId
     * @param int $topicId
     */
    public function addCreateTopicActivite($userId, $topicId) {
        $this->addActivite($userId, Activity::ACTION_TOPIC_CREATE, $topicId);
    }

    /**
     * @param $period
     * @param int $count
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getActiveUsers($period, $count = 100) {
        if (!is_numeric($period) || $period <= 0 || $period > 90) {
            $period = 7;
        }
        $startDate = new \DateTime('-' . $period . ' days midnight');
	    $maxId = DoctrineUtility::getMaxIdWithTime($this->entityManager, Activity::class, 'id', 'createTime', $startDate);
        $conn = $this->entityManager->getConnection();
        $stmt = $conn->prepare(
	        'SELECT user_id FROM activity WHERE id>=:maxId GROUP BY user_id ORDER BY user_id LIMIT ' . $count
        );
        $stmt->bindValue(':maxId', $maxId);
        $stmt->execute();
        $result = $stmt->fetchAll();

        return array_map(function($user) {
            return $user['user_id'];
        }, $result);
    }
}