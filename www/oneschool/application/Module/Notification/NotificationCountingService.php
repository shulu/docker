<?php
namespace Lychee\Module\Notification;

use Lychee\Module\Notification\Entity\GroupEventNotification;
use Lychee\Module\Notification\Entity\NotificationCounting;
use Lychee\Module\Post\PostService;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManagerInterface;

class NotificationCountingService {
    /**
     * @var EntityManagerInterface
     */
    private $em;

	/**
	 * @var PostService
	 */
    private $postService;

    /**
     * NotificationCountingService constructor.
     * @param RegistryInterface $registry
	 * @param PostService $postService
	 */
    public function __construct($registry, PostService $postService) {
        $this->em = $registry->getManager();
        $this->postService = $postService;
    }

    public function countOfficialUnread($userId) {
        /** @var NotificationCounting $counting */
        $counting = $this->em->find(NotificationCounting::class, $userId);
        if ($counting === null || $counting->officialCursor == null) {
            return 0;
        } else {
            $cursor = $counting->officialCursor;
        }
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $sql = 'SELECT count(id) as unread FROM notification_official WHERE publish_time <= ? AND id > ? ORDER BY id DESC LIMIT 30';
        $stat = $this->em->getConnection()
            ->executeQuery($sql, array($now, $cursor), array(\PDO::PARAM_STR, \PDO::PARAM_INT));
        $result = $stat->fetchAll(\PDO::FETCH_ASSOC);
        if (count($result) == 0) {
            return 0;
        } else {
            return intval($result[0]['unread']);
        }
    }

    public function resetOfficialUnreadCounting($userId) {
        $conn = $this->em->getConnection();
        $getCursorSql = 'SELECT id FROM notification_official ORDER BY id DESC LIMIT 1';
        $result = $conn->executeQuery($getCursorSql)->fetchAll(\PDO::FETCH_ASSOC);
        if (count($result) == 0 || $result[0]['id'] == null) {
            $cursor = 0;
        } else {
            $cursor = $result[0]['id'];
        }

        $insertSql = 'INSERT INTO notification_counting(user_id, official_cursor) VALUE (?, ?) ON DUPLICATE KEY UPDATE official_cursor = VALUES(official_cursor)';
        $conn->executeUpdate($insertSql, array($userId, $cursor),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT));
    }

    public function increaseLikesUnreadCount($userId) {
        $sql = 'INSERT INTO notification_counting(user_id, likes_unread) VALUES(?, 1) ON DUPLICATE KEY UPDATE likes_unread = likes_unread + 1';
        $this->em->getConnection()->executeUpdate($sql, [$userId], [\PDO::PARAM_INT]);
    }

    public function countLikesUnread($userId) {
        $counting = $this->em->find(NotificationCounting::class, $userId);
        if ($counting === null) {
            return 0;
        } else {
            return $counting->likesUnread;
        }
    }

    public function resetLikesUnreadCounting($userId) {
        $sql = 'UPDATE notification_counting SET likes_unread = 0 WHERE user_id = ?';
        $this->em->getConnection()->executeUpdate($sql,[$userId], [\PDO::PARAM_INT]);
        $this->em->clear(NotificationCounting::class);
    }

    public function increaseEventsUnreadCount($userId) {
        $sql = 'INSERT INTO notification_counting(user_id, events_unread) VALUES(?, 1) ON DUPLICATE KEY UPDATE events_unread = events_unread + 1';
        $this->em->getConnection()->executeUpdate($sql, [$userId], [\PDO::PARAM_INT]);
    }

    public function countEventsUnread($userId) {
        $counting = $this->em->find(NotificationCounting::class, $userId);
        if ($counting === null) {
            return 0;
        } else {
            return $counting->eventsUnread;
        }
    }

    public function resetEventsUnreadCounting($userId) {
        $sql = 'UPDATE notification_counting SET events_unread = 0, comments_unread = 0, topics_unread = 0, mentions_unread = 0, announcements_unread = 0 WHERE user_id = ?';
        $this->em->getConnection()->executeUpdate($sql,[$userId], [\PDO::PARAM_INT]);
        $this->em->clear(NotificationCounting::class);
    }

    private function getGroupFieldName($group) {
        switch ($group) {
            case GroupEventNotification::GROUP_COMMENTS:
                return 'comments_unread';
            case GroupEventNotification::GROUP_TOPICS:
                return 'topics_unread';
            case GroupEventNotification::GROUP_MENTIONS:
                return 'mentions_unread';
            case GroupEventNotification::GROUP_ANNOUNCEMENTS:
                return 'announcements_unread';
            default:
                throw new \UnexpectedValueException('unknonw group '.$group);
        }
    }

    public function increaseGroupUnreadCounting($userId, $group) {
        $field = $this->getGroupFieldName($group);
        $sql = 'INSERT INTO notification_counting(user_id, '.$field.', events_unread) VALUES(?, 1, 1) ON DUPLICATE KEY UPDATE '.$field.' = '.$field.' + 1, events_unread = events_unread + 1';
        $this->em->getConnection()->executeUpdate($sql, [$userId], [\PDO::PARAM_INT]);
    }

    public function resetGroupUnreadCounting($userId, $group) {
        $field = $this->getGroupFieldName($group);
        $sql = 'UPDATE notification_counting SET '.$field.' = 0 WHERE user_id = ?';
        $this->em->getConnection()->executeUpdate($sql,[$userId], [\PDO::PARAM_INT]);
        $this->em->clear(NotificationCounting::class);
    }

    public function countGroupUnread($userId, $group) {
        $counting = $this->em->find(NotificationCounting::class, $userId);
        if ($counting === null) {
            return 0;
        } else {
            switch ($group) {
                case GroupEventNotification::GROUP_COMMENTS:
                    return $counting->commentsUnread;
                case GroupEventNotification::GROUP_TOPICS:
                    return $counting->topicsUnread;
                case GroupEventNotification::GROUP_MENTIONS:
                    return $counting->mentionsUnread;
                case GroupEventNotification::GROUP_ANNOUNCEMENTS:
                    return $counting->announcementsUnread;
                default:
                    throw new \UnexpectedValueException('unknonw group '.$group);
            }
        }
    }

	/**
	 * @param $userId
	 * @param \DateTime $lastUpdateTime
	 *
	 * @return int
	 */
    public function countFolloweesUnread($userId, \DateTime $lastUpdateTime) {
    	$lastPostCursor = $this->postService->getCursorAfterCreateTime($lastUpdateTime);

	    $sql = "SELECT COUNT(up.post_id)
				FROM user_following uf
				JOIN user_post up ON up.user_id=uf.followee_id
				WHERE uf.follower_id=:user_id AND uf.`state`=0 AND up.post_id>:cursor";
	    $stmt = $this->em->getConnection()->prepare($sql);
	    $stmt->bindValue(':user_id', $userId);
	    $stmt->bindValue(':cursor', $lastPostCursor);
	    if ($stmt->execute()) {
	    	$result = $stmt->fetch(\PDO::FETCH_COLUMN);
	    	$unread = (int)$result;
	    }

	    return isset($unread)? $unread : 0;
    }
}