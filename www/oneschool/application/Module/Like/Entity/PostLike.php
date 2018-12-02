<?php
namespace Lychee\Module\Like\Entity;

use Doctrine\ORM\Mapping as ORM;
use Lychee\Module\Like\LikeState;

/**
 * @ORM\Entity
 * @ORM\Table(name="like_post", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="liker_post_state_udx", columns={"liker_id", "post_id", "state"}),
 *   @ORM\UniqueConstraint(name="post_state_id_udx", columns={"post_id", "state", "id"})
 * }, indexes={
 *   @ORM\Index(name="update_time_idx", columns={"update_time"})
 * })
 */
class PostLike {

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(name="id", type="bigint")
     */
    public $id;

    /**
     * @var int
     *
     * @ORM\Column(name="liker_id", type="bigint")
     */
    public $likerId;

    /**
     * @var int
     *
     * @ORM\Column(name="post_id", type="bigint")
     */
    public $postId;

    /**
     * @var int
     *
     * @ORM\Column(name="state", type="smallint")
     */
    public $state = LikeState::NORMAL;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_time", type="datetime")
     */
    public $updateTime;
}