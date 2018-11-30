<?php
namespace Lychee\Module\Relation\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="robot_user_following", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="follower_followee__udx", columns={"follower_id", "followee_id"})
 * }, indexes={
 *   @ORM\Index(name="time_idx", columns={"time"}),
 *   @ORM\Index(name="followee_follower_idx", columns={"followee_id", "follower_id"}),
 * })
 */
class RobotUserFollowing {

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(name="id", type="bigint", options={"unsigned":true})
     */
    public $id;

    /**
     * @var int
     *
     * @ORM\Column(name="follower_id", type="bigint", options={"unsigned":true, "comment":"点赞用户id"})
     */
    public $followerId;

    /**
     * @var int
     *
     * @ORM\Column(name="followee_id", type="bigint", options={"unsigned":true, "comment":"帖子id"})
     */
    public $followeeId;

    /**
     * @var int
     *
     * @ORM\Column(name="time", type="integer")
     */
    public $time;
}