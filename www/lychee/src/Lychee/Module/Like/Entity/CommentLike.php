<?php
namespace Lychee\Module\Like\Entity;

use Doctrine\ORM\Mapping as ORM;
use Lychee\Module\Like\LikeState;

/**
 * @ORM\Entity
 * @ORM\Table(name="like_comment", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="liker_comment_state_udx", columns={"liker_id", "comment_id", "state"}),
 *   @ORM\UniqueConstraint(name="comment_state_id_udx", columns={"comment_id", "state", "id"})
 * }, indexes={
 *   @ORM\Index(name="update_time_idx", columns={"update_time"})
 * })
 */
class CommentLike {

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
     * @ORM\Column(name="comment_id", type="bigint")
     */
    public $commentId;

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