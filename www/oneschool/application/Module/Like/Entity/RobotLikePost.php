<?php
namespace Lychee\Module\Like\Entity;

use Doctrine\ORM\Mapping as ORM;
use Lychee\Module\Like\LikeState;

/**
 * @ORM\Entity
 * @ORM\Table(name="robot_like_post", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="liker_post_udx", columns={"liker_id", "post_id"})
 * }, indexes={
 *   @ORM\Index(name="time_idx", columns={"time"}),
 *   @ORM\Index(name="post_liker_idx", columns={"post_id", "liker_id"}),
 * })
 */
class RobotLikePost {

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
     * @ORM\Column(name="liker_id", type="bigint", options={"unsigned":true, "comment":"点赞用户id"})
     */
    public $likerId;

    /**
     * @var int
     *
     * @ORM\Column(name="post_id", type="bigint", options={"unsigned":true, "comment":"帖子id"})
     */
    public $postId;

    /**
     * @var int
     *
     * @ORM\Column(name="time", type="integer")
     */
    public $time;
}