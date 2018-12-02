<?php
namespace Lychee\Component\GraphStorage\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 * @ORM\Table(uniqueConstraints={
 *   @ORM\UniqueConstraint(
 *     name="follower_followee_state_udx",
 *     columns={"follower_id", "followee_id", "state"}
 *   ),
 *   @ORM\UniqueConstraint(
 *     name="followee_follower_state_udx",
 *     columns={"followee_id", "follower_id", "state"}
 *   ),
 *   @ORM\UniqueConstraint(
 *     name="followee_state_id_udx",
 *     columns={"followee_id", "state", "id"}
 *   ),
 *   @ORM\UniqueConstraint(
 *     name="follower_state_id_udx",
 *     columns={"follower_id", "state", "id"}
 *   )
 * })
 */
class AbstractFollowing {

    const STATE_NORMAL = 0;
    const STATE_REMOVED = 1;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint")
     * @ORM\GeneratedValue("AUTO")
     */
    public $id;

    /**
     * @var int
     *
     * @ORM\Column(name="follower_id", type="bigint")
     */
    public $followerId;

    /**
     * @var int
     *
     * @ORM\Column(name="followee_id", type="bigint")
     */
    public $followeeId;

    /**
     * @var int
     *
     * @ORM\Column(name="state", type="smallint")
     */
    public $state;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_time", type="datetime")
     */
    public $updateTime;
} 