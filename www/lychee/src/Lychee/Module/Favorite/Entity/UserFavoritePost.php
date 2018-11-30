<?php
namespace Lychee\Module\Favorite\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="user_favorite_posts", schema="ciyo_favorite", indexes={
 *   @ORM\Index(name="user_position_post", columns={"user_id", "position", "post_id"})
 * })
 */
class UserFavoritePost {

    /**
     * @var int
     * @ORM\Column(name="user_id", type="bigint")
     * @ORM\Id
     */
    public $userId;

    /**
     * @var int
     * @ORM\Column(name="post_id", type="bigint")
     * @ORM\Id
     */
    public $postId;

    /**
     * @var \DateTime
     * @ORM\Column(name="time", type="datetime")
     */
    public $time;

    /**
     * @var int
     * @ORM\Column(name="position", type="bigint")
     */
    public $position;

}