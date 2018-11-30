<?php
namespace Lychee\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="user_comment", indexes={
 *   @ORM\Index(name="use_image_comment_idx", columns={"user_id", "has_image", "comment_id"})
 * })
 */
class UserComment {
    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public $userId;

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