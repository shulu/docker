<?php
namespace Lychee\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="post_comment_image")
 */
class PostImageComment {
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="post_id", type="bigint")
     */
    public $postId;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="comment_id", type="bigint")
     */
    public $commentId;
} 