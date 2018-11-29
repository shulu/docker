<?php
namespace app\entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="post_comment", indexes={
 *   @ORM\Index(name="post_image_comment_idx", columns={"post_id", "has_image", "comment_id"})
 * })
 */
class PostComment {
    /**
     * @var int
     *
     * @ORM\Column(name="post_id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public $postId;

    /**
     * @var int
     *
     * @ORM\Column(name="comment_id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public $commentId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="has_image", type="boolean")
     */
    public $hasImage;
} 