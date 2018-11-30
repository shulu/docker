<?php
namespace Lychee\Module\Topic\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="topic_user_meta")
 */
class TopicUserMeta {

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="bigint")
     * @ORM\Id
     */
    public $userId;

    /**
     * @var int
     *
     * @ORM\Column(name="followee_count", type="integer")
     */
    public $followeeCount;

    /**
     * @var int
     *
     * @ORM\Column(name="followee_position", type="integer")
     */
    public $followeePosition;

}