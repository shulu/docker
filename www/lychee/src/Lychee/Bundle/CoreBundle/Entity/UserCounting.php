<?php
namespace Lychee\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="user_counting")
 */
class UserCounting {
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
     * @ORM\Column(name="post_count", type="integer")
     */
    public $postCount = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="image_comment_count", type="integer")
     */
    public $imageCommentCount = 0;
} 