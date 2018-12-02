<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Module\Notification\Entity\GroupEventNotification;
use Lychee\Module\Notification\EventNotificationAction;

class EventNotificationSynthesizer extends AbstractSynthesizer {

    protected $userSynthesizer;
    protected $commentSynthesizer;
    protected $postSynthesizer;
    protected $topicSynthesizer;
    protected $scheduleSynthesizer;

    /**
     * @param array $notificationsByIds
     * @param Synthesizer|null $userSynthesizer
     * @param Synthesizer|null $commentSynthesizer
     * @param PostSynthesizer|null $postSynthesizer
     * @param Synthesizer|null $topicSynthesizer
     * @param Synthesizer|null $scheduleSynthesizer
     */
    public function __construct(
        $notificationsByIds,
        $userSynthesizer,
        $commentSynthesizer,
        $postSynthesizer,
        $topicSynthesizer,
        $scheduleSynthesizer
    ) {
        parent::__construct($notificationsByIds);
        $this->userSynthesizer = $userSynthesizer;
        $this->commentSynthesizer = $commentSynthesizer;
        $this->postSynthesizer = $postSynthesizer;
        $this->topicSynthesizer = $topicSynthesizer;
        $this->scheduleSynthesizer = $scheduleSynthesizer;
    }

    /**
     * @param GroupEventNotification $notification
     * @param mixed $info
     *
     * @return array
     * @throws \RuntimeException
     */
    protected function synthesize($notification, $info = null) {
        switch ($notification->action) {
            case EventNotificationAction::COMMENT:
                return array(
                    'time' => $notification->createTime->getTimestamp(),
                    'type' => 'commented',
                    'topic' => $this->topicSynthesizer ?
                        $this->topicSynthesizer->synthesizeOne($notification->topicId):
                        array('id' => $notification->topicId),
                    'comment' => $this->commentSynthesizer ?
                            $this->commentSynthesizer->synthesizeOne($notification->targetId):
                            array('id' => $notification->targetId)
                        ,
                );
            case EventNotificationAction::MENTION_IN_POST:
                return array(
                    'time' => $notification->createTime->getTimestamp(),
                    'type' => 'mention',
                    'topic' => $this->topicSynthesizer ?
                        $this->topicSynthesizer->synthesizeOne($notification->topicId):
                        array('id' => $notification->topicId),
                    'post' => $this->postSynthesizer ?
                        $this->postSynthesizer->synthesizeOne($notification->targetId):
                        array('id' => $notification->targetId)
                        ,
                );
            case EventNotificationAction::MENTION_IN_COMMENT:
                return array(
                    'time' => $notification->createTime->getTimestamp(),
                    'type' => 'mention',
                    'topic' => $this->topicSynthesizer ?
                        $this->topicSynthesizer->synthesizeOne($notification->topicId):
                        array('id' => $notification->topicId),
                    'comment' => $this->commentSynthesizer ?
                        $this->commentSynthesizer->synthesizeOne($notification->targetId):
                        array('id' => $notification->targetId)
                    ,
                );
            case EventNotificationAction::TOPIC_APPLY_TO_FOLLOW:
                return array(
                    'time' => $notification->createTime->getTimestamp(),
                    'type' => 'topic_apply',
                    'user' => $this->userSynthesizer ?
                        $this->userSynthesizer->synthesizeOne($notification->actorId):
                        array('id' => $notification->actorId),
                    'topic' => $this->topicSynthesizer ?
                        $this->topicSynthesizer->synthesizeOne($notification->targetId):
                        array('id' => $notification->targetId),
                    'description' => $notification->message
                ,
                );
            case EventNotificationAction::TOPIC_APPLY_CONFIRMED:
                return array(
                    'time' => $notification->createTime->getTimestamp(),
                    'type' => 'topic_apply_confirm',
                    'topic' => $this->topicSynthesizer ?
                        $this->topicSynthesizer->synthesizeOne($notification->targetId):
                        array('id' => $notification->targetId)
                ,
                );
            case EventNotificationAction::TOPIC_APPLY_REJECTED:
                return array(
                    'time' => $notification->createTime->getTimestamp(),
                    'type' => 'topic_apply_reject',
                    'topic' => $this->topicSynthesizer ?
                        $this->topicSynthesizer->synthesizeOne($notification->targetId):
                        array('id' => $notification->targetId)
                ,
                );
            case EventNotificationAction::TOPIC_KICKOUT:
                return array(
                    'time' => $notification->createTime->getTimestamp(),
                    'type' => 'topic_kickout',
                    'topic' => $this->topicSynthesizer ?
                        $this->topicSynthesizer->synthesizeOne($notification->targetId):
                        array('id' => $notification->targetId)
                );
            case EventNotificationAction::SCHEDULE_CANCELLED:
                return array(
                    'time' => $notification->createTime->getTimestamp(),
                    'type' => 'schedule_cancelled',
                    'topic' => $this->topicSynthesizer ?
                        $this->topicSynthesizer->synthesizeOne($notification->topicId):
                        array('id' => $notification->topicId),
                    'schedule' => $this->scheduleSynthesizer ?
                        $this->scheduleSynthesizer->synthesizeOne($notification->targetId):
                        array('id' => $notification->targetId),
                    'canceller' => $this->userSynthesizer ?
                        $this->userSynthesizer->synthesizeOne($notification->actorId):
                        array('id' => $notification->actorId),
                );
            case EventNotificationAction::SCHEDULE_ABOUT_TO_START:
                return array(
                    'time' => $notification->createTime->getTimestamp(),
                    'type' => 'schedule_about_to_start',
                    'topic' => $this->topicSynthesizer ?
                        $this->topicSynthesizer->synthesizeOne($notification->topicId):
                        array('id' => $notification->topicId),
                    'schedule' => $this->scheduleSynthesizer ?
                        $this->scheduleSynthesizer->synthesizeOne($notification->targetId):
                        array('id' => $notification->targetId),
                    'creator' => $this->userSynthesizer ?
                        $this->userSynthesizer->synthesizeOne($notification->actorId):
                        array('id' => $notification->actorId),
                    'message' => $notification->message
                );
            case EventNotificationAction::TOPIC_ANNOUNCEMENT:
                return array(
                    'time' => $notification->createTime->getTimestamp(),
                    'type' => 'topic_announcement',
                    'topic' => $this->topicSynthesizer ?
                        $this->topicSynthesizer->synthesizeOne($notification->topicId):
                        array('id' => $notification->topicId),
                    'post' => $this->postSynthesizer ?
                        $this->postSynthesizer->synthesizeOne($notification->targetId):
                        array('id' => $notification->targetId),
                );
            case EventNotificationAction::BECOME_CORE_MEMBER:
                return array(
                    'time' => $notification->createTime->getTimestamp(),
                    'type' => 'become_core_member',
                    'topic' => $this->topicSynthesizer ?
                        $this->topicSynthesizer->synthesizeOne($notification->topicId):
                        array('id' => $notification->topicId),
                );
            case EventNotificationAction::REMOVE_CORE_MEMBER:
                return array(
                    'time' => $notification->createTime->getTimestamp(),
                    'type' => 'remove_core_member',
                    'topic' => $this->topicSynthesizer ?
                        $this->topicSynthesizer->synthesizeOne($notification->topicId):
                        array('id' => $notification->topicId),
                );
            case EventNotificationAction::TOPIC_CREATE_CONFIRMED:
                return array(
                    'time' => $notification->createTime->getTimestamp(),
                    'type' => 'topic_create_confirmed',
                    'topic' => $this->topicSynthesizer ?
                        $this->topicSynthesizer->synthesizeOne($notification->targetId):
                        array('id' => $notification->targetId),
                    'operator' => $this->userSynthesizer ?
                        $this->userSynthesizer->synthesizeOne($notification->actorId):
                        array('id' => $notification->actorId),
                );
            case EventNotificationAction::TOPIC_CREATE_REJECTED:
                return array(
                    'time' => $notification->createTime->getTimestamp(),
                    'type' => 'topic_create_rejected',
                    'topic_name' => $notification->message ? $notification->message : '',
                    'operator' => $this->userSynthesizer ?
                        $this->userSynthesizer->synthesizeOne($notification->actorId):
                        array('id' => $notification->actorId),
                );
            case EventNotificationAction::ILLEGAL_POST_DELETED:
                $r = array(
                    'time' => $notification->createTime->getTimestamp(),
                    'type' => 'illegal_post_deleted',
                    'topic' => $this->topicSynthesizer ?
                        $this->topicSynthesizer->synthesizeOne($notification->topicId):
                        array('id' => $notification->topicId),
                    'post' => $this->postSynthesizer ?
                        $this->postSynthesizer->synthesizeOne($notification->targetId):
                        array('id' => $notification->targetId),
                    'post_content' => $notification->message ? $notification->message : '',
                    'deleter' => $this->userSynthesizer ?
                        $this->userSynthesizer->synthesizeOne($notification->actorId):
                        array('id' => $notification->actorId),
                );
                /** @var Post $post */
                $post = $this->postSynthesizer->getEntityById($notification->targetId);
                if ($post && $this->userSynthesizer) {
                    $r['post_author'] = $this->userSynthesizer->synthesizeOne($post->authorId);
                }
                return $r;
            case EventNotificationAction::MY_ILLEGAL_POST_DELETED:
                return array(
                    'time' => $notification->createTime->getTimestamp(),
                    'type' => 'my_illegal_post_deleted',
                    'topic' => $this->topicSynthesizer ?
                        $this->topicSynthesizer->synthesizeOne($notification->topicId):
                        array('id' => $notification->topicId),
                    'post' => $this->postSynthesizer ?
                        $this->postSynthesizer->synthesizeOne($notification->targetId):
                        array('id' => $notification->targetId),
                    'post_content' => $notification->message ? $notification->message : '',
                    'deleter' => $this->userSynthesizer ?
                        $this->userSynthesizer->synthesizeOne($notification->actorId):
                        array('id' => $notification->actorId),
                );
            default:
                return null;
        }
    }

} 