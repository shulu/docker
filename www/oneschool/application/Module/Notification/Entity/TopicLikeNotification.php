<?php
namespace Lychee\Module\Notification\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="notification_topic_like", indexes={
 *   @ORM\Index(name="user_id_index", columns={"user_id", "id"}),
 *   @ORM\Index(name="user_topic_id_idx", columns={"user_id", "topic_id", "id"})
 * })
 */
class TopicLikeNotification {

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
     * @ORM\Column(name="user_id", type="bigint")
     */
    public $userId;

    /**
     * @var int
     *
     * @ORM\Column(name="topic_id", type="bigint", nullable=true)
     */
    public $topicId;

    /**
     * @var int
     *
     * @ORM\Column(name="liker_id", type="bigint")
     */
    public $likerId;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="smallint")
     */
    public $type;

    /**
     * @var int
     *
     * @ORM\Column(name="likee_id", type="bigint")
     */
    public $likeeId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_time", type="datetime")
     */
    public $createTime;
}