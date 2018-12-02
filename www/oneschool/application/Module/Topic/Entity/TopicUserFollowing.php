<?php
namespace Lychee\Module\Topic\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="topic_user_following", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="user_state_position", columns={"user_id", "state", "position"})
 * }, indexes={
 *   @ORM\Index(name="time_idx", columns={"create_time"})
 * })
 */
class TopicUserFollowing {

    const STATE_DELETED = 0;
    const STATE_NORMAL = 1;
    const STATE_FAVORITE = 2;

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
     * @ORM\Column(name="topic_id", type="bigint")
     * @ORM\Id
     */
    public $topicId;

    /**
     * @var int
     *
     * @ORM\Column(name="state", type="smallint")
     */
    public $state;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="integer")
     */
    public $position;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_time", type="datetime")
     */
    public $createTime;
}