<?php
namespace Lychee\Module\Notification;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Lychee\Module\Notification\Entity\NotificationCounting;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Notification\Entity\OfficialNotification;
use Lychee\Module\Notification\Entity\OfficialNotificationPush;
use Lychee\Utility;
use Lychee\Component\Foundation\ImageUtility;

class OfficialNotificationService {
    /**
     * @var EntityManager
     */
    private $em;
    private $countingService;

    /**
     * @param Registry $registry
     * @param NotificationCountingService $countingService
     */
    public function __construct($registry, $countingService) {
        $this->em = $registry->getManager();
        $this->countingService = $countingService;
    }

    public function fetchOfficialsByIds($ids) {
        $entities = $this->em->getRepository(OfficialNotification::class)
            ->findBy(array('id' => $ids));
        return ArrayUtility::mapByColumn($entities, 'id');
    }


    public function formatFields($item) {
        if (empty($item)) {
            return null;
        }

        if (isset($item->image)) {
            $item->image = ImageUtility::formatUrl($item->image);
        }
        return $item;
    }

    public function fetchOfficials($cursor, $count, &$nextCursor = null) {
        if ($count === 0) {
            return array();
        }
        if ($cursor === 0) {
            $cursor = PHP_INT_MAX;
        }

        $query = $this->em->createQuery('
            SELECT n
            FROM '.OfficialNotification::class.' n
            WHERE n.id < :cursor AND n.publishTime <= :now
            ORDER BY n.publishTime DESC
        ')->setMaxResults($count);
        $notifications = $query->execute(array(
            'cursor' => $cursor,
            'now' => new \DateTime(),
        ));

        if (count($notifications) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $notifications[count($notifications) - 1]->id;
        }

        foreach ($notifications as $item) {
            $this->formatFields($item);
        }

        return $notifications;
    }

    /**
     * @param int $fromId
     * @param \DateTime $time
     * @param string $message
     * @param string $image
     * @param int $type
     * @param int $targetId
     * @param string $url
     * @return OfficialNotification
     * @param \DateTime|null $publishTime
     * @return OfficialNotification
     */
    public function addOfficial($fromId, $time, $message, $image, $type, $targetId, $url, \DateTime $publishTime = null) {
        $notification = new OfficialNotification();
        $notification->fromId = $fromId;
        $notification->image = $image;
        $notification->message = $message;
        $notification->type = $type;
        $notification->targetId = $targetId;
        $notification->url = $url;
        $notification->createTime = $time;
        if ($publishTime) {
            $notification->publishTime = $publishTime;
        } else {
            $notification->publishTime = $time;
        }

        $this->em->persist($notification);
        $this->em->flush($notification);

        return $notification;
    }

    public function addOfficialSite($fromId, $time, $message, $image, $site, \DateTime $publishTime = null) {
        $url = Utility::buildClientSiteUrl($site);
        return $this->addOfficial($fromId, $time, $message, $image, OfficialNotification::TYPE_SITE, null, $url, $publishTime);
    }

    public function addOfficialPost($fromId, $time, $message, $image, $postId, \DateTime $publishTime = null) {
        $url = Utility::buildClientPostUrl($postId);
        return $this->addOfficial($fromId, $time, $message, $image, OfficialNotification::TYPE_POST, $postId, $url, $publishTime);
    }

    public function addOfficialTopic($fromId, $time, $message, $image, $topicId, \DateTime $publishTime = null) {
        $url = Utility::buildClientTopicUrl($topicId);
        return $this->addOfficial($fromId, $time, $message, $image, OfficialNotification::TYPE_TOPIC, $topicId, $url, $publishTime);
    }

    public function addOfficialUser($fromId, $time, $message, $image, $userId, \DateTime $publishTime = null) {
        $url = Utility::buildClientUserUrl($userId);
        return $this->addOfficial($fromId, $time, $message, $image, OfficialNotification::TYPE_USER, $userId, $url, $publishTime);
    }

    public function addOfficialSubject($fromId, $time, $message, $image, $subjectId, \DateTime $publishTime = null) {
        $url = Utility::buildClientSubjectUrl($subjectId);
        return $this->addOfficial($fromId, $time, $message, $image, OfficialNotification::TYPE_SUBJECT, $subjectId, $url, $publishTime);
    }

    public function addOfficialLive($fromId, $time, $message, $image, $liveId, \DateTime $publishTime = null) {
        $url = Utility::buildClientLiveUrl($liveId);
        return $this->addOfficial($fromId, $time, $message, $image, OfficialNotification::TYPE_LIVE, $liveId, $url, $publishTime);
    }

    /**
     * @param $id
     * @param $message
     * @param \DateTime $pushTime
     * @param $platform
     * @param null $tags
     * @return OfficialNotificationPush
     */
    public function setOfficialNotificationPush($id, $message, \DateTime $pushTime, $platform, $tags = null) {
        $officialNotificationPush = new OfficialNotificationPush();
        $officialNotificationPush->notificationId = $id;
        $officialNotificationPush->message = $message;
        $officialNotificationPush->pushTime = $pushTime;
        $officialNotificationPush->nextPushTime = $pushTime;
        $officialNotificationPush->platform = $platform;
        $officialNotificationPush->tags = $tags;
        $this->em->persist($officialNotificationPush);
        $this->em->flush();

        return $officialNotificationPush;
    }

    /**
     * @param $ids
     * @return array
     */
    public function fetchOfficialNotificationPush($ids) {
        $repo = $this->em->getRepository(OfficialNotificationPush::class);
        $result = $repo->findBy([
            'notificationId' => $ids
        ]);

        return ArrayUtility::mapByColumn($result, 'notificationId');
    }

    public function fetchAllOfficials($cursor, $count, &$nextCursor = null) {
        if ($count === 0) {
            return array();
        }
        if ($cursor === 0) {
            $cursor = PHP_INT_MAX;
        }

        $query = $this->em->createQuery('
            SELECT n
            FROM '.OfficialNotification::class.' n
            WHERE n.id < :cursor
            ORDER BY n.publishTime DESC
        ')->setMaxResults($count);
        $notifications = $query->execute(array('cursor' => $cursor));

        if (count($notifications) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $notifications[count($notifications) - 1]->id;
        }

        return $notifications;
    }

}