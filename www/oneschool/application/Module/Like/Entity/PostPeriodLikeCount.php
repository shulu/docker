<?php
namespace Lychee\Module\Like\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="like_post_period_count", indexes={
 *   @ORM\Index(name="last_id", columns={"last_id"}),
 *   @ORM\Index(name="post_id_count", columns={"post_id", "count"})
 * })
 */
class PostPeriodLikeCount {

    /**
     * @var int
     *
     * @ORM\Column(name="post_id", type="bigint", options={"unsigned":true, "comment":"帖子id"})
     * @ORM\Id
     */
    public $postId;

    /**
     * @var int
     *
     * @ORM\Column(name="first_id", type="bigint", options={"unsigned":true, "comment":"统计时的第一条点赞流水id"})
     */
    public $firstId;

    /**
     * @var int
     *
     * @ORM\Column(name="last_id", type="bigint", options={"unsigned":true, "comment":"统计时的最后一条点赞流水id"})
     */
    public $lastId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="count", type="bigint", options={"unsigned":true, "comment":"点赞数"})
     */
    public $count;
}