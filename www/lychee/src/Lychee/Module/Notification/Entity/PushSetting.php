<?php
namespace Lychee\Module\Notification\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="notification_push_setting")
 */
class PushSetting {

    const TYPE_ALL = 1;
    const TYPE_NONE = 2;
    const TYPE_MY_FOLLOWEE = 3;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="user_id", type="bigint")
     */
    public $userId;

    /**
     * @var bool
     *
     * @ORM\Column(name="no_disturb", type="boolean")
     */
    public $noDisturb = false;

    /**
     * @var string
     *
     * @ORM\Column(name="no_disturb_timezone", type="string", length=100, nullable=true)
     */
    public $noDisturbTimeZone = null;

    /**
     * @var string
     *
     * @ORM\Column(name="no_disturb_start_time", type="string", length=10, nullable=true)
     */
    public $noDisturbStartTime = null;

    /**
     * @var string
     *
     * @ORM\Column(name="no_disturb_end_time", type="string", length=10, nullable=true)
     */
    public $noDisturbEndTime = null;

    /**
     * @var int
     *
     * @ORM\Column(name="mention_me", type="smallint", options={"default":1})
     */
    public $mentionMeType = self::TYPE_ALL;

    /**
     * @var int
     *
     * @ORM\Column(name="comment_me", type="smallint", options={"default":1})
     */
    public $commentMeType = self::TYPE_ALL;

    /**
     * @var int
     *
     * @ORM\Column(name="image_comment_me", type="smallint", options={"default":1})
     */
    public $imageCommentMeType = self::TYPE_ALL;

    /**
     * @var int
     *
     * @ORM\Column(name="follow_me", type="smallint", options={"default":1})
     */
    public $followMeType = self::TYPE_ALL;

    /**
     * @var int
     *
     * @ORM\Column(name="like_me", type="smallint", options={"default":1})
     */
    public $likeMeType = self::TYPE_ALL;

    /**
     * @var int
     *
     * @ORM\Column(name="message_me", type="smallint", options={"default":1})
     */
    public $messageMeType = self::TYPE_ALL;

    /**
     * @var int
     *
     * @ORM\Column(name="topic_apply", type="smallint", options={"default":1})
     */
    public $topicApplyType = self::TYPE_ALL;

    /**
     * @var int
     *
     * @ORM\Column(name="schedule", type="smallint", options={"default":1})
     */
    public $scheduleType = self::TYPE_ALL;

    /**
     * @var int
     *
     * @ORM\Column(name="followee_post", type="smallint", options={"default":2})
     */
    public $followeePostType = self::TYPE_NONE;

	/**
	 * @var int
	 *
	 * @ORM\Column(name="followee_anchor", type="smallint", options={"default":1})
	 */
    public $followeeAnchorType = self::TYPE_ALL;

    /**
     * @param \DateTime $time
     * @return bool
     */
    public function isTimeNoDisturbing($time) {
        if ($this->noDisturb === false ||
            $this->noDisturbTimeZone == null ||
            $this->noDisturbStartTime == null ||
            $this->noDisturbEndTime == null
        ) {
            return false;
        }

        $year = $time->format('Y');
        $month = $time->format('m');
        $day = $time->format('d');

        $timezone = new \DateTimeZone($this->noDisturbTimeZone);
        $start = \DateTime::createFromFormat('H:i', $this->noDisturbStartTime, $timezone);
        $end = \DateTime::createFromFormat('H:i', $this->noDisturbEndTime, $timezone);

        $start->setTimezone($time->getTimezone())->setDate($year, $month, $day);
        $end->setTimezone($time->getTimezone())->setDate($year, $month, $day);

        if ($start < $end) {
            if ($start <= $time && $time <= $end) {
                return true;
            } else {
                return false;
            }
        } else if ($start > $end) {
            if ($start <= $time || $time <= $end) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

} 