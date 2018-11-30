<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Module\Notification\Entity\TopicLikeNotification;
use Lychee\Module\Notification\LikeNotificationType;

class LikeNotificationSynthesizer extends AbstractSynthesizer {

    /**
     * @var Synthesizer
     */
    protected $likerSynthesizer;

    /**
     * @var Synthesizer
     */
    protected $postSynthesizer;
    /**
     * @var Synthesizer
     */
    protected $commentSynthesizer;

    /**
     * @param array $notificationsByIds
     * @param Synthesizer|null $likerSynthesizer
     * @param Synthesizer|null $postSynthesizer
     * @param Synthesizer|null $commentSynthesizer
     */
    public function __construct($notificationsByIds, $likerSynthesizer, $postSynthesizer, $commentSynthesizer) {
        parent::__construct($notificationsByIds);
        $this->likerSynthesizer = $likerSynthesizer;
        $this->postSynthesizer = $postSynthesizer;
        $this->commentSynthesizer = $commentSynthesizer;
    }

    /**
     * @param TopicLikeNotification $notification
     * @param mixed $info
     *
     * @return array
     * @throws \RuntimeException
     */
    protected function synthesize($notification, $info = null) {
        switch ($notification->type) {
            case LikeNotificationType::POST:
                return array(
                    'time' => $notification->createTime->getTimestamp(),
                    'liker' => $this->likerSynthesizer ?
                            $this->likerSynthesizer->synthesizeOne($notification->likerId):
                            array('id' => $notification->likerId)
                        ,
                    'post' => $this->postSynthesizer ?
                            $this->postSynthesizer->synthesizeOne($notification->likeeId):
                            array('id' => $notification->likeeId)
                        ,
                );
                break;
            case LikeNotificationType::COMMENT:
                return null;
                break;
            default:
                throw new \RuntimeException('like notification type error');
        }
    }

} 