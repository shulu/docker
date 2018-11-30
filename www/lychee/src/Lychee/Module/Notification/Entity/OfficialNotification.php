<?php
namespace Lychee\Module\Notification\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="notification_official", indexes={@ORM\Index(name="publish_time_idx", columns={"publish_time"})})
 */
class OfficialNotification {

    const TYPE_POST = 1;
    const TYPE_TOPIC = 2;
    const TYPE_USER = 3;
    const TYPE_COMMENT = 4;//不再支持。2016-10-19 16:44:39
    const TYPE_SITE = 5;
    const TYPE_SUBJECT = 6;
    const TYPE_LIVE = 7;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var int
     *
     * @ORM\Column(name="from_id", type="bigint")
     */
    public $fromId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_time", type="datetime")
     */
    public $createTime;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="smallint")
     */
    public $type;

    /**
     * @var int
     *
     * @ORM\Column(name="target_id", type="bigint", nullable=true)
     */
    public $targetId;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=2083, nullable=true)
     */
    public $url;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", length=2083, nullable=true)
     */
    public $image;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="string", length=1000, nullable=true)
     */
    public $message;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="publish_time", type="datetime")
     */
    public $publishTime;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="views", type="integer", options={"default":"0"})
	 */
	public $views = 0;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="unique_views", type="integer", options={"default":"0"})
	 */
	public $uniqueViews = 0;
}