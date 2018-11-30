<?php
namespace Lychee\Module\Topic\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="topic_announcing")
 */
class TopicAnnouncing {
    /**
     * @var int
     * @ORM\Column(name="topic_id", type="bigint")
     * @ORM\Id
     */
    public $topicId;

    /**
     * @var int
     * @ORM\Column(name="last_announce_time", type="integer")
     */
    public $lastAnnounceTime;

    /**
     * @var int
     * @ORM\Column(name="last_announce_post", type="bigint")
     */
    public $lastAnnouncePost;

    /**
     * @var int
     * @ORM\Column(name="last_announce_time2", type="integer", nullable=true)
     */
    public $lastAnnounceTime2;

    /**
     * @var int
     * @ORM\Column(name="last_announce_post2", type="bigint", nullable=true)
     */
    public $lastAnnouncePost2;

}