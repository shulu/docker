<?php
namespace app\entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="user_comment_image")
 */
class UserImageComment {
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="user_id", type="bigint")
     */
    public $userId;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="comment_id", type="bigint")
     */
    public $commentId;
} 