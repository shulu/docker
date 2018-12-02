<?php
namespace Lychee\Module\Topic\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="topic_follower", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="topic_user", columns={"topic_id", "user_id"})
 * })
 */
class TopicFollower {

    /**
     * @var int
     *
     * @ORM\Column(name="topic_id", type="bigint")
     * @ORM\Id
     */
    public $topicId;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="integer")
     * @ORM\Id
     */
    public $position;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="bigint")
     * @ORM\Id
     */
    public $userId;
}