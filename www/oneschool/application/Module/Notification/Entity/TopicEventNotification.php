<?php
namespace Lychee\Module\Notification\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="notification_topic_event", indexes={
 *   @ORM\Index(name="user_id_index", columns={"user_id", "id"}),
 *   @ORM\Index(name="user_topic_id_idx", columns={"user_id", "topic_id", "id"})
 * })
 */
class TopicEventNotification {

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
     * @ORM\Column(name="actor_id", type="bigint")
     */
    public $actorId;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="smallint")
     */
    public $action;

    /**
     * @var int
     *
     * @ORM\Column(name="target_id", type="bigint", nullable=true)
     */
    public $targetId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_time", type="datetime")
     */
    public $createTime;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="string", length=200, nullable=true)
     */
    public $message;
}