<?php
namespace Lychee\Module\Topic\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="topic_visitor_log")
 */
class TopicVisitorLog {
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="bigint")
     */
    public $id;

    /**
     * @var int
     * @ORM\Column(name="topic_id", type="bigint")
     */
    public $topicId;

    /**
     * @var int
     * @ORM\Column(name="user_id", type="bigint")
     */
    public $userId;

    /**
     * @var \DateTime
     * @ORM\Column(name="create_time", type="datetime")
     */
    public $createTime;
}