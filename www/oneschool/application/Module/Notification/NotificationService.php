<?php
namespace Lychee\Module\Notification;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Lychee\Component\Database\DoctrineUtility;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Constant;
use Lychee\Module\Notification\Entity\GroupEventNotification;
use Lychee\Module\Notification\Entity\TopicLikeNotification;
use Lychee\Module\Notification\Push\PushService;
use Lychee\Module\Notification\Push\PushEventType;
use Lychee\Module\Topic\TopicService;
use Lychee\Module\Account\AccountService;
use Lychee\Module\Schedule\Entity\Schedule;
use Lychee\Module\Post\PostService;
use Lychee\Module\Topic\CoreMember\TopicCoreMemberService;

class NotificationService {
    /**
     * @var EntityManager
     */
    private $em;

    private $countingService;

    private $topicService;
    private $accountService;
    private $pusher;
    private $postService;
    private $topicCoreMemberService;

    /**
     * @param Registry $registry
     * @param NotificationCountingService $countingService
     * @param TopicService $topicService
     * @param AccountService $accountService
     * @param PushService $pusher
     * @param PostService $postService
     * @param TopicCoreMemberService $topicCoreMemberService
     */
    public function __construct($registry, $countingService, $topicService, $accountService, $pusher, $postService, $topicCoreMemberService) {
        $this->em = $registry->getManager();
        $this->countingService = $countingService;
        $this->topicService = $topicService;
        $this->accountService = $accountService;
        $this->pusher = $pusher;
        $this->postService = $postService;
        $this->topicCoreMemberService = $topicCoreMemberService;
    }

    /**
     * @param int $userId
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     *
     * @return GroupEventNotification[]
     */
    public function fetchEventsByUser($userId, $cursor, $count, &$nextCursor = null) {
        if ($count === 0) {
            return array();
        }
        if ($cursor === 0) {
            $cursor = PHP_INT_MAX;
        }

        $query = $this->em->createQuery('
            SELECT n
            FROM '.GroupEventNotification::class.' n
            WHERE n.userId = :userId and n.id < :cursor
            ORDER BY n.id DESC
        ')->setMaxResults($count);
        $notifications = $query->execute(array(
            'userId' => $userId, 'cursor' => $cursor));

        if (count($notifications) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $notifications[count($notifications) - 1]->id;
        }

        return $notifications;
    }

    /**
     * @param int $userId
     * @param int $group
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     *
     * @return GroupEventNotification[]
     */
    public function fetchEventsByUserGroup($userId, $group, $cursor, $count, &$nextCursor = null) {
        if ($count === 0) {
            return array();
        }
        if ($cursor === 0) {
            $cursor = PHP_INT_MAX;
        }

        $query = $this->em->createQuery('SELECT n FROM '.GroupEventNotification::class.' n WHERE n.userId = :userId AND n.groupType = :groupType and n.id < :cursor ORDER BY n.id DESC
        ')->setMaxResults($count);
        $notifications = $query->execute(array(
            'userId' => $userId, 'groupType' => $group, 'cursor' => $cursor));

        if (count($notifications) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $notifications[count($notifications) - 1]->id;
        }

        return $notifications;
    }

    /**
     * @param int $userId
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     *
     * @return TopicLikeNotification[]
     */
    public function fetchLikesByUser($userId, $cursor, $count, &$nextCursor = null) {
        if ($count === 0) {
            return array();
        }
        if ($cursor === 0) {
            $cursor = PHP_INT_MAX;
        }

        $query = $this->em->createQuery('
            SELECT n
            FROM '.TopicLikeNotification::class.' n
            WHERE n.userId = :userId AND n.id < :cursor
            ORDER BY n.id DESC
        ')->setMaxResults($count);
        $notifications = $query->execute(array(
            'userId' => $userId, 'cursor' => $cursor));

        if (count($notifications) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $notifications[count($notifications) - 1]->id;
        }

        return $notifications;
    }

    private function getUserName($userId) {
        $user = $this->accountService->fetchOne($userId);
        return $user ? $user->nickname : '某人';
    }

    private function notifyLikeEvent($to, $topicId, $from, $type, $targetId) {
        $notification = new TopicLikeNotification();
        $notification->createTime = new \DateTime();
        $notification->userId = $to;
        $notification->topicId = $topicId;
        $notification->likerId = $from;
        $notification->likeeId = $targetId;
        $notification->type = $type;

        $this->em->persist($notification);
        $this->em->flush($notification);
        $this->em->clear(TopicLikeNotification::class);
        $this->countingService->increaseLikesUnreadCount($to);
    }

    public function notifyLikePostEvent($to, $topicId, $from, $postId) {
        $this->notifyLikeEvent($to, $topicId, $from,
            LikeNotificationType::POST, $postId);

        $this->pusher->pushEvent($from, $to, PushEventType::LIKE,
            $this->getUserName($from) . ': 赞了你');
    }

    private function notifyEvent($to, $topicId, $from, $action, $targetId = null, $message = null) {
        $time = new \DateTime();
//        $n = new TopicEventNotification();
//        $n->createTime = $time;
//        $n->userId = $to;
//        $n->topicId = $topicId;
//        $n->actorId = $from;
//        $n->targetId = $targetId;
//        $n->action = $action;
//        $n->message = $message;
//
//        $this->em->persist($n);
//        $this->em->flush($n);

        $gn = new GroupEventNotification();
//        $gn->id = $n->id;
        $gn->createTime = $time;
        $gn->userId = $to;
        $gn->topicId = $topicId;
        $gn->actorId = $from;
        $gn->targetId = $targetId;
        $gn->action = $action;
        $gn->message = $message;
        $gn->groupType = $this->groupTypeFromAction($action);
        $this->em->persist($gn);
        $this->em->flush($gn);

//        $this->em->clear(TopicEventNotification::class);
        $this->em->clear(GroupEventNotification::class);
    }

    private function groupTypeFromAction($action) {
        switch ($action) {
            case EventNotificationAction::COMMENT:
                return GroupEventNotification::GROUP_COMMENTS;
            case EventNotificationAction::MENTION_IN_POST:
            case EventNotificationAction::MENTION_IN_COMMENT:
                return GroupEventNotification::GROUP_MENTIONS;
            case EventNotificationAction::TOPIC_APPLY_TO_FOLLOW:
            case EventNotificationAction::TOPIC_APPLY_CONFIRMED:
            case EventNotificationAction::TOPIC_APPLY_REJECTED:
            case EventNotificationAction::TOPIC_KICKOUT:
            case EventNotificationAction::SCHEDULE_ABOUT_TO_START:
            case EventNotificationAction::SCHEDULE_CANCELLED:
            case EventNotificationAction::BECOME_CORE_MEMBER:
            case EventNotificationAction::REMOVE_CORE_MEMBER:
            case EventNotificationAction::TOPIC_CREATE_CONFIRMED:
            case EventNotificationAction::TOPIC_CREATE_REJECTED:
            case EventNotificationAction::ILLEGAL_POST_DELETED:
            case EventNotificationAction::MY_ILLEGAL_POST_DELETED:
                return GroupEventNotification::GROUP_TOPICS;
            case EventNotificationAction::TOPIC_ANNOUNCEMENT:
                return GroupEventNotification::GROUP_ANNOUNCEMENTS;
            default:
                throw new \UnexpectedValueException('unknown action '.$action);
        }
    }

    public function notifyCommentEvent($to, $topicId, $from, $commentId) {
        $this->notifyEvent($to, $topicId, $from,
            EventNotificationAction::COMMENT, $commentId
        );
        $this->countingService->increaseGroupUnreadCounting($to, GroupEventNotification::GROUP_COMMENTS);
        $this->pusher->pushEvent($from, $to, PushEventType::COMMENT,
            $this->getUserName($from) . ': 评论了你');
    }

    public function notifyCommentReplyEvent($to, $topicId, $from, $commentId) {
        $this->notifyEvent($to, $topicId, $from,
            EventNotificationAction::COMMENT, $commentId
        );
        $this->countingService->increaseGroupUnreadCounting($to, GroupEventNotification::GROUP_COMMENTS);
        $this->pusher->pushEvent($from, $to, PushEventType::REPLY,
            $this->getUserName($from) . ': 回复了你');
    }

    public function notifyMentionInPostEvent($to, $topicId, $from, $postId) {
        $this->notifyEvent($to, $topicId, $from,
            EventNotificationAction::MENTION_IN_POST, $postId
        );
        $this->countingService->increaseGroupUnreadCounting($to, GroupEventNotification::GROUP_MENTIONS);
        $this->pusher->pushEvent($from, $to, PushEventType::MENTION,
            $this->getUserName($from) . ': 提到了你');
    }

    public function notifyMentionInCommentEvent($to, $topicId, $from, $commentId) {
        $this->notifyEvent($to, $topicId, $from,
            EventNotificationAction::MENTION_IN_COMMENT, $commentId
        );
        $this->countingService->increaseGroupUnreadCounting($to, GroupEventNotification::GROUP_MENTIONS);
        $this->pusher->pushEvent($from, $to, PushEventType::MENTION,
            $this->getUserName($from) . ': 提到了你');
    }

    public function notifyApplyToFollowEvent($to, $applicantId, $topicId, $description) {
        $topic = $this->topicService->fetchOne($topicId);
        if ($topic == null) {
            return;
        }

        $this->notifyEvent(
            $to, null, $applicantId,
            EventNotificationAction::TOPIC_APPLY_TO_FOLLOW, $topicId, $description
        );
        $this->countingService->increaseGroupUnreadCounting($to, GroupEventNotification::GROUP_TOPICS);

        $this->pusher->pushEvent($applicantId, $to, PushEventType::TOPIC_APPLY,
            $this->getUserName($applicantId) . ': 申请加入[' . $topic->title . ']次元');
    }

    public function notifyApplicationConfirmedEvent($to, $managerId, $topicId) {
        $topic = $this->topicService->fetchOne($topicId);
        if ($topic == null) {
            return;
        }

        $this->notifyEvent(
            $to, null, $managerId,
            EventNotificationAction::TOPIC_APPLY_CONFIRMED, $topicId
        );
        $this->countingService->increaseGroupUnreadCounting($to, GroupEventNotification::GROUP_TOPICS);
        $this->pusher->pushEvent($managerId, $to, PushEventType::TOPIC_APPLY_CONFIRM,
            '欢迎加入[' . $topic->title . ']次元');
    }

    public function notifyApplicationRejectedEvent($to, $managerId, $topicId) {
        $topic = $this->topicService->fetchOne($topicId);
        if ($topic == null) {
            return;
        }

        $this->notifyEvent(
            $to, null, $managerId,
            EventNotificationAction::TOPIC_APPLY_REJECTED, $topicId
        );
        $this->countingService->increaseGroupUnreadCounting($to, GroupEventNotification::GROUP_TOPICS);
        $this->pusher->pushEvent($managerId, $to, PushEventType::TOPIC_APPLY_REJECT,
            '[' . $topic->title . ']: 你的申请已被拒绝');
    }

    public function notifyTopicKickoutEvent($to, $managerId, $topicId) {
        $topic = $this->topicService->fetchOne($topicId);
        if ($topic == null) {
            return;
        }

        $this->notifyEvent(
            $to, null, $managerId,
            EventNotificationAction::TOPIC_KICKOUT, $topicId
        );
        $this->countingService->increaseGroupUnreadCounting($to, GroupEventNotification::GROUP_TOPICS);
        $this->pusher->pushEvent($managerId, $to, PushEventType::TOPIC_KICKOUT,
            '你被踢出[' . $topic->title . ']次元');
    }

    /**
     * @param int[] $toUserIds
     * @param int $topicId
     * @param Schedule $schedule
     * @param $cancellerId
     */
    public function notifyScheduleCancelledEvent($toUserIds, $topicId, $schedule, $cancellerId) {
        foreach ($toUserIds as $userId) {
            $this->notifyEvent($userId, $topicId, $cancellerId,
                EventNotificationAction::SCHEDULE_CANCELLED, $schedule->id);
            $this->countingService->increaseGroupUnreadCounting($userId, GroupEventNotification::GROUP_TOPICS);
        }

        $this->pusher->pushEvent($cancellerId, $toUserIds, PushEventType::SCHEDULE_CANCELLED,
            $schedule->name.'活动已取消');
    }

    /**
     * @param int[] $toUserIds
     * @param int $topicId
     * @param int $scheduleId
     * @param int $scheduleCreatorId
     * @param string $message
     */
    public function notifyScheduleAboutToStartEvent($toUserIds, $topicId, $scheduleId,
                                                    $scheduleCreatorId, $message) {
        foreach ($toUserIds as $userId) {
            $this->notifyEvent($userId, $topicId, $scheduleCreatorId,
                EventNotificationAction::SCHEDULE_ABOUT_TO_START, $scheduleId, $message);
            $this->countingService->increaseGroupUnreadCounting($userId, GroupEventNotification::GROUP_TOPICS);
        }

        $this->pusher->pushEvent($scheduleCreatorId, $toUserIds, PushEventType::SCHEDULE_ABOUT_TO_START,
            $message);
    }
    
    public function notifyBecomeCoreMemberEvent($to, $topicId, $managerId) {
        $topic = $this->topicService->fetchOne($topicId);
        if ($topic == null) {
            return;
        }

        $this->notifyEvent($to, $topicId, $managerId,
                EventNotificationAction::BECOME_CORE_MEMBER, $topicId, null);
        $this->countingService->increaseGroupUnreadCounting($to, GroupEventNotification::GROUP_TOPICS);
        $this->pusher->pushEvent($managerId, $to, PushEventType::BECOME_CORE_MEMBER,
            '恭喜你成为了['.$topic->title.']次元的管理员');
    }

    public function notifyRemoveCoreMemberEvent($to, $topicId, $managerId) {
        $topic = $this->topicService->fetchOne($topicId);
        if ($topic == null) {
            return;
        }

        $this->notifyEvent($to, $topicId, $managerId,
            EventNotificationAction::REMOVE_CORE_MEMBER, $topicId, null);
        $this->countingService->increaseGroupUnreadCounting($to, GroupEventNotification::GROUP_TOPICS);
        $this->pusher->pushEvent($managerId, $to, PushEventType::REMOVE_CORE_MEMBER,
            '领主将你从['.$topic->title.']次元的管理员中移除');
    }

    public function notifyTopicCreateSuccessEvent($to, $topicId) {
        $topic = $this->topicService->fetchOne($topicId);
        if ($topic == null) {
            return;
        }

        $this->notifyEvent($to, $topicId, Constant::CIYUANJIANG_ID,
            EventNotificationAction::TOPIC_CREATE_CONFIRMED, $topicId, null);
        $this->countingService->increaseGroupUnreadCounting($to, GroupEventNotification::GROUP_TOPICS);
        $this->pusher->pushEvent(Constant::CIYUANJIANG_ID, $to, PushEventType::TOPIC_CREATE_CONFIRMED,
            '你申请的['.$topic->title.']次元已经通过审核，快去次元里愉快的玩耍吧。');
    }

    public function notifyTopicCreateFailureEvent($to, $topicName) {
        $this->notifyEvent($to, null, Constant::CIYUANJIANG_ID,
            EventNotificationAction::TOPIC_CREATE_REJECTED, null, $topicName);
        $this->countingService->increaseGroupUnreadCounting($to, GroupEventNotification::GROUP_TOPICS);
        $this->pusher->pushEvent(Constant::CIYUANJIANG_ID, $to, PushEventType::TOPIC_CREATE_CONFIRMED,
            '你申请的['.$topicName.']次元封面、名称或简介中含有色情、暴力等敏感元素，请修改后再次申请。');
    }

    public function notifyIllegalPostDeletedBySystemEvent($postId) {
        $post = $this->postService->fetchOne($postId);
        if (!$post || !$post->topicId) {
            return;
        }

        $topic = $this->topicService->fetchOne($post->topicId);
        if (!$topic) {
            return;
        }

        $coreMembers = $this->topicCoreMemberService->getCoreMembers($post->topicId);
        $coreMemberIds = ArrayUtility::columns($coreMembers, 'userId');

        $tos = array_diff(array_merge(array($post->authorId, $topic->managerId), $coreMemberIds), array(Constant::CIYUANJIANG_ID));

        foreach ($tos as $to) {
            $action = $to == $post->authorId ? EventNotificationAction::MY_ILLEGAL_POST_DELETED :
                EventNotificationAction::ILLEGAL_POST_DELETED;

            $this->notifyEvent($to, $post->topicId, Constant::CIYUANJIANG_ID,
                $action, $postId, mb_substr($post->content, 0, 20, 'utf8'));
            $this->countingService->increaseGroupUnreadCounting($to, GroupEventNotification::GROUP_TOPICS);
        }
    }

    public function notifyIllegalPostDeletedByTopicManagerEvent($postId, $deleterId) {
        $post = $this->postService->fetchOne($postId);
        if (!$post || !$post->topicId) {
            return;
        }

        $topic = $this->topicService->fetchOne($post->topicId);
        if (!$topic) {
            return;
        }

        $tos = array_diff(array($post->authorId, $topic->managerId), array($deleterId));

        foreach ($tos as $to) {
            $action = $to == $post->authorId ? EventNotificationAction::MY_ILLEGAL_POST_DELETED :
                EventNotificationAction::ILLEGAL_POST_DELETED;

            $this->notifyEvent($to, $post->topicId, $deleterId, $action, $postId, mb_substr($post->content, 0, 20, 'utf8'));
            $this->countingService->increaseGroupUnreadCounting($to, GroupEventNotification::GROUP_TOPICS);
        }
    }

    public function notifyTopicAnnouncementEvent($tos, $topicId, $publisherId, $postId) {
        $topic = $this->topicService->fetchOne($topicId);
        if ($topic == null) {
            return;
        }

        foreach ($tos as $userId) {
            $this->notifyEvent($userId, $topicId, $publisherId,
                EventNotificationAction::TOPIC_ANNOUNCEMENT, $postId, null);
            $this->countingService->increaseGroupUnreadCounting($userId, GroupEventNotification::GROUP_ANNOUNCEMENTS);
        }

        $this->pusher->pushEvent($publisherId, $tos, PushEventType::TOPIC_ANNOUNCEMENT,
            '['.$topic->title.']次元发布了公告');
    }

    public function clearNotificationsBefore(\DateTimeInterface $time) {
        $conn = $this->em->getConnection();
        $eventMinId = DoctrineUtility::getMinIdWithTime($this->em, GroupEventNotification::class,
            'id', 'createTime', $time);
        $eventSql = 'DELETE FROM notification_group_event WHERE id < ? ORDER BY id ASC LIMIT 10000';
        $done = false;
        while (!$done) {
            $deleted = $conn->executeUpdate($eventSql, array($eventMinId), array(\PDO::PARAM_INT));
            $done = $deleted == 0;
        }
    }

}