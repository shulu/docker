<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 7/1/15
 * Time: 4:53 PM
 */

namespace Lychee\Module\Notification\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="notification_official_push")
 */
class OfficialNotificationPush {

    const PLATFORM_ALL = 0;
    const PLATFORM_IOS = 1;
    const PLATFORM_ANDROID = 2;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="notification_id", type="bigint")
     */
    public $notificationId;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    public $message;

    /**
     * @var \DateTime
     * @ORM\Column(name="push_time", type="datetime")
     */
    public $pushTime;

    /**
     * @var string
     * @ORM\Column(type="string", length=10)
     */
    public $platform;

    /**
     * @var int
     * @ORM\Column(type="smallint")
     */
    public $pushed = 0;

    /**
     * @var string
     * @ORM\Column(type="string", length=1023, nullable=true)
     */
    public $tags;

    /**
     * @var \DateTime
     * @ORM\Column(name="next_push_time", type="datetime")
     */
    public $nextPushTime;
}