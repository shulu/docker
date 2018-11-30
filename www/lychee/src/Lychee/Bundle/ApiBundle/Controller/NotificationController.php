<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Bundle\ApiBundle\DataSynthesizer\PushSettingSynthesizer;
use Lychee\Bundle\ApiBundle\Error\CommentError;
use Lychee\Bundle\ApiBundle\Error\CommonError;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Notification\Entity\GroupEventNotification;
use Lychee\Module\Notification\Entity\PushSetting;
use Lychee\Module\Notification\NotificationCountingService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Lychee\Module\Notification\Entity\TopicLikeNotification;
use Lychee\Module\Notification\NotificationService;
use Lychee\Component\Foundation\CursorWrapper;

/**
 * @Route("/notification")
 */
class NotificationController extends Controller {

    /**
     * @return NotificationService
     */
    private function topicNotification() {
        return $this->get('lychee.module.notification');
    }

    /**
     * @return NotificationCountingService
     */
    private function notificationCounting() {
        return $this->get('lychee.module.notification.counting');
    }

    /**
     * @Route("/event")
     * @Method("GET")
     * @ApiDoc(
     *   section="notification",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function listEventAction(Request $request) {
        $account = $this->requireAuth($request);
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 100);

        $notifications = $this->topicNotification()->fetchEventsByUser(
            $account->id, $cursor, $count, $nextCursor
        );
        if ($cursor == 0) {
            $this->notificationCounting()->resetEventsUnreadCounting($account->id);
        }

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildEventNotificationSynthesizer($notifications, $account->id);
        return $this->arrayResponse(
            'notifications', $synthesizer->synthesizeAll(), $nextCursor
        );
    }

    /**
     * @Route("/event/{group}")
     * @Method("GET")
     * @ApiDoc(
     *   section="notification",
     *   requirements={
     *     {"name"="group", "dataType"="string", "description"="enum(comments,topics,mentions,announcements)"}
     *   },
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function listEventGroupAction(Request $request, $group) {
        $account = $this->requireAuth($request);
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 100);

        $groupMap = [
            'comments' => GroupEventNotification::GROUP_COMMENTS,
            'topics' => GroupEventNotification::GROUP_TOPICS,
            'mentions' => GroupEventNotification::GROUP_MENTIONS,
            'announcements' => GroupEventNotification::GROUP_ANNOUNCEMENTS,
        ];
        if (!isset($groupMap[$group])) {
            return $this->arrayResponse('notifications', [], 0);
        }
        $groupType = $groupMap[$group];

        $notifications = $this->topicNotification()->fetchEventsByUserGroup(
            $account->id, $groupType, $cursor, $count, $nextCursor
        );
        if ($cursor == 0) {
            $this->notificationCounting()->resetGroupUnreadCounting($account->id, $groupType);
        }

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildEventNotificationSynthesizer($notifications, $account->id);
        return $this->arrayResponse(
            'notifications', $synthesizer->synthesizeAll(), $nextCursor
        );
    }

    /**
     * @Route("/like")
     * @Method("GET")
     * @ApiDoc(
     *   section="notification",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function listLikeAction(Request $request) {
        $account = $this->requireAuth($request);
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 100);

        $notifications = $this->topicNotification()->fetchLikesByUser(
            $account->id, $cursor, $count, $nextCursor
        );
        if ($cursor == 0) {
            $this->notificationCounting()->resetLikesUnreadCounting($account->id);
        }

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildLikeNotificationSynthesizer($notifications, $account->id);
        return $this->arrayResponse(
            'notifications', $synthesizer->synthesizeAll(), $nextCursor
        );
    }

    /**
     * @Route("/official")
     * @Method("GET")
     * @ApiDoc(
     *   section="notification",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function listOfficialAction(Request $request) {
        $account = $this->requireAuth($request);
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 100);

        $notifications = $this->officialNotification()->fetchOfficials(
            $cursor, $count, $nextCursor
        );
        if ($cursor == 0) {
            $this->notificationCounting()->resetOfficialUnreadCounting($account->id);
        }

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildOfficialNotificationSynthesizer($notifications, $account->id);
        $result = $synthesizer->synthesizeAll();
        $result = ArrayUtility::filterValuesNonNull($result);

        $clientVersion = $request->query->get(self::CLIENT_APP_VERSION_KEY);
        if ($clientVersion && version_compare($clientVersion, '2.5', '<')) {
            foreach ($result as &$item) {
                unset($item['id']);
            }
            unset($item);
        }

        return $this->arrayResponse(
            'notifications', $result, $nextCursor
        );
    }

    /**
     * @Route("/unread_count")
     * @Method("GET")
     * @ApiDoc(
     *   section="notification",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="last_update_time", "dataType"="integer", "required"=false}
     *   }
     * )
     */
    public function countUnreadAction(Request $request) {
        $account = $this->requireAuth($request);
        $lastUpdateTime = $request->query->get('last_update_time');
        if (!$lastUpdateTime && $lastUpdateTime !== '0') {
        	$lastUpdateTime = new \DateTime();
        } else {
        	$lastUpdateTime = (new \DateTime())->setTimestamp($lastUpdateTime);
        }

        $countingService = $this->notificationCounting();
        $eventsUnread = $countingService->countEventsUnread($account->id);
        $commentsUnread = $countingService->countGroupUnread($account->id, GroupEventNotification::GROUP_COMMENTS);
        $topicsUnread = $countingService->countGroupUnread($account->id, GroupEventNotification::GROUP_TOPICS);
        $mentionsUnread = $countingService->countGroupUnread($account->id, GroupEventNotification::GROUP_MENTIONS);
        $announcementsUnread = $countingService->countGroupUnread($account->id, GroupEventNotification::GROUP_ANNOUNCEMENTS);
        $likesUnread = $countingService->countLikesUnread($account->id);
        $officialUnread = $countingService->countOfficialUnread($account->id);
        $followeesUnread = $countingService->countFolloweesUnread($account->id, $lastUpdateTime);

        $officials = $this->officialNotification()->fetchOfficials(0, 1, $nextCursor);
        if (count($officials) > 0) {
            $officialNotification = $officials[0];
            $lastOfficial = $this->getSynthesizerBuilder()
                ->buildOfficialNotificationSynthesizer(array(
                    $officialNotification->id => $officialNotification), $account->id)
                ->synthesizeOne($officialNotification->id);
        } else {
            $lastOfficial = null;
        }

        $likes = $this->topicNotification()->fetchLikesByUser($account->id, 0, 1, $nextCursor);
        if (count($likes) > 0) {
            /** @var TopicLikeNotification $likeNotification */
            $likeNotification = $likes[0];
            $lastLikerId = $likeNotification->likerId;

            $synthesizer = $this->getSynthesizerBuilder()->buildSimpleUserSynthesizer(array($lastLikerId));
            $lastLiker = $synthesizer->synthesizeOne($lastLikerId);
        } else {
            $lastLiker = null;
        }

        $lastFolloweesActivityId = $this->getLastActivityIdOfFollowees($account->id);
        if ($lastFolloweesActivityId) {
            $synthesizer = $this->getSynthesizerBuilder()
                ->buildActivitySynthesizer(array($lastFolloweesActivityId), $account->id);
            $lastActivity = $synthesizer->synthesizeOne($lastFolloweesActivityId);
        } else {
            $lastActivity = null;
        }

        return $this->dataResponse(array(
            'events' => $eventsUnread,
            'comments' => $commentsUnread,
            'topics' => $topicsUnread,
            'mentions' => $mentionsUnread,
            'announcements' => $announcementsUnread,
            'likes' => $likesUnread,
            'officials' => $officialUnread,
	        'followees' => $followeesUnread,
            'last_liker' => $lastLiker,
            'last_official' => $lastOfficial,
            'last_activity' => $lastActivity
        ));
    }

    /**
     * @param int $userId
     * @return int|null
     */
    private function getLastActivityIdOfFollowees($userId) {
        $userIterator = new CursorWrapper(
            function($cursor, $count, &$nextCursor) use ($userId) {
                return $this->relation()->fetchFolloweeIdsByUserId(
                    $userId, $cursor, $count, $nextCursor
                );
            },
            100
        );

        $ids = array();
        foreach ($userIterator as $userIds) {
            $particalIds = $this->activity()->fetchIdsByUsers($userIds, 0, 1);
            $ids = array_merge($ids, $particalIds);
        }
        return empty($ids) ? null : max($ids);
    }

    /**
     * @Route("/push/setting/update")
     * @Method("POST")
     * @ApiDoc(
     *   section="notification",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="no_disturb", "dataType"="boolean", "required"=false, "description"="是否开启免打扰，1为打开，0为不打开。如果此参数为1，则no_timezone、nd_from、nd_to必须提供"},
     *     {"name"="nd_timezone", "dataType"="string", "required"=false, "description"="免打扰时间的时区"},
     *     {"name"="nd_from", "dataType"="string", "required"=false, "description"="免打扰时间的起始时间，格式为'00:00'，24小时制"},
     *     {"name"="nd_to", "dataType"="string", "required"=false, "description"="免打扰时间的结束时间，格式为'00:00'，24小时制"},
     *     {"name"="mention", "dataType"="string", "required"=false, "description"="'提及'的push方式，可选值为，'all':全部人，'no':不推送, 'followee':我关注的人"},
     *     {"name"="comment", "dataType"="string", "required"=false, "description"="'评论'的push方式，可选值为，'all':全部人，'no':不推送, 'followee':我关注的人"},
     *     {"name"="image_comment", "dataType"="string", "required"=false, "description"="'改图'的push方式，可选值为，'all':全部人，'no':不推送, 'followee':我关注的人"},
     *     {"name"="follow", "dataType"="string", "required"=false, "description"="'关注'的push方式，可选值为，'all':全部人，'no':不推送, 'followee':我关注的人"},
     *     {"name"="like", "dataType"="string", "required"=false, "description"="'赞'的push方式，可选值为，'all':全部人，'no':不推送, 'followee':我关注的人"},
     *     {"name"="message", "dataType"="string", "required"=false, "description"="'私信'的push方式，可选值为，'all':全部人，'no':不推送, 'followee':我关注的人"},
     *     {"name"="topic_apply", "dataType"="string", "required"=false, "description"="'申请加入次元'的push方式，可选值为，'all':全部人，'no':不推送, 'followee':我关注的人"},
     *     {"name"="schedule", "dataType"="string", "required"=false, "description"="'申请加入次元'的push方式，可选值为，'all':全部人，'no':不推送, 'followee':我关注的人"},
     *     {"name"="followee_post", "dataType"="string", "required"=false, "description"="'关注推送'的push方式，可选值为，'all':全部人，'no':不推送, 'followee':我关注的人"},
     *     {"name"="followee_anchor", "dataType"="string", "required"=false, "description"="'关注的主播开播'的push方式，可选值为，'all':全部人，'no':不推送"},
     *   }
     * )
     */
    public function updatePushSetting(Request $request) {
        $account = $this->requireAuth($request);

        if ($request->request->has('no_disturb')) {
            $noDisturb = $request->request->getInt('no_disturb', 0) == 1 ? true : false;
        } else {
            $noDisturb = null;
        }

        $setting = new PushSetting();
        if ($noDisturb !== null) {
            $setting->noDisturb = $noDisturb;
            if ($noDisturb) {
                $ndTimezone = $this->requireParam($request->request, 'nd_timezone');
                $ndFrom = $this->requireParam($request->request, 'nd_from');
                $ndTo = $this->requireParam($request->request, 'nd_to');

                try {
                    new \DateTimeZone($ndTimezone);
                } catch (\Exception $e) {
                    return $this->errorsResponse(CommonError::ParameterInvalid('nd_timezone', $ndTimezone));
                }
                if ($this->isTimeStringValid($ndFrom) === false) {
                    return $this->errorsResponse(CommonError::ParameterInvalid('nd_from', $ndFrom));
                }
                if ($this->isTimeStringValid($ndTo) === false) {
                    return $this->errorsResponse(CommonError::ParameterInvalid('nd_to', $ndTo));
                }

                $setting->noDisturb = true;
                $setting->noDisturbTimeZone = $ndTimezone;
                $setting->noDisturbStartTime = $ndFrom;
                $setting->noDisturbEndTime = $ndTo;
            }
        }

        $paramMap = [
            'mentionMeType' => 'mention',
            'commentMeType' => 'comment',
            'imageCommentMeType' => 'image_comment',
            'followMeType' => 'follow',
            'likeMeType' => 'like',
            'messageMeType' => 'message',
            'topicApplyType' => 'topic_apply',
            'scheduleType' => 'schedule',
            'followeePostType' => 'followee_post',
	        'followeeAnchorType' => 'followee_anchor',
        ];

        foreach ($paramMap as $type => $param) {
            $value = $this->pushTypeFromString($request->request->get($param, null));
            if ($value !== null && property_exists($setting, $type)) {
                $setting->$type = $value;
            }
        }

        $psm = $this->get('lychee.module.notification.push_setting');
        $psm->update($account->id, $setting);

        return $this->sucessResponse();
    }

    /**
     * @param $string
     * @return int|null
     */
    private function pushTypeFromString($string) {
        if ($string === 'no') {
            return PushSetting::TYPE_NONE;
        } else if ($string === 'followee') {
            return PushSetting::TYPE_MY_FOLLOWEE;
        } else if ($string === 'all') {
            return PushSetting::TYPE_ALL;
        } else {
            return null;
        }
    }

    private function isTimeStringValid($string) {
        if (preg_match('/^(\d{2}):(\d{2})$/', $string, $matches)) {
            $hour = intval($matches[1]);
            $minute = intval($matches[2]);
            if (0 <= $hour && $hour <= 23 && 0 <= $minute && $minute <= 59) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @Route("/push/setting")
     * @Method("GET")
     * @ApiDoc(
     *   section="notification",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true}
     *   }
     * )
     */
    public function getPushSetting(Request $request) {
        $account = $this->requireAuth($request);

        $psm = $this->get('lychee.module.notification.push_setting');
        $pushSetting = $psm->fetchOne($account->id);

        $synthesizer = new PushSettingSynthesizer(array($pushSetting->userId => $pushSetting));
        return $this->dataResponse($synthesizer->synthesizeOne($pushSetting->userId));
    }
} 