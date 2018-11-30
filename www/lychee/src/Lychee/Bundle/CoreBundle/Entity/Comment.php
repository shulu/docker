<?php
namespace Lychee\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="comment", indexes={
 *   @ORM\Index(name="post_id_liked_count_index", columns={"post_id", "deleted", "liked_count"})
 * })
 */
class Comment {
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     */
    public $id;

    /**
     * @var int
     *
     * @ORM\Column(name="post_id", type="bigint")
     */
    public $postId;

    /**
     * @var int
     *
     * @ORM\Column(name="author_id", type="bigint")
     */
    public $authorId;

    /**
     * @var int
     *
     * @ORM\Column(name="replied_id", type="bigint", nullable=true)
     */
    public $repliedId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_time", type="datetime")
     */
    public $createTime;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=15, nullable=true)
     */
    public $ip;

    /**
     * @var string
     *
     * @ORM\Column(name="district", type="string", length=100, nullable=true)
     */
    public $district;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="string", length=255, nullable=true)
     */
    public $content;

    /**
     * @var string
     *
     * @ORM\Column(name="image_url", type="string", length=2083, nullable=true)
     */
    public $imageUrl;

    /**
     * @var bool
     *
     * @ORM\Column(name="deleted", type="boolean")
     */
    public $deleted = false;

    /**
     * @var int
     *
     * @ORM\Column(name="liked_count", type="integer")
     */
    public $likedCount = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="annotation", type="string", length=1024, nullable=true)
     */
    public $annotation;
}