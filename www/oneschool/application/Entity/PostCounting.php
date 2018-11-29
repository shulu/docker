<?php
namespace app\entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="post_counting")
 */
class PostCounting {
    /**
     * @var int
     *
     * @ORM\Column(name="post_id", type="bigint")
     * @ORM\Id
     */
    public $postId;

    /**
     * @var int
     *
     * @ORM\Column(name="liked_count", type="integer")
     */
    public $likedCount = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="commented_count", type="integer")
     */
    public $commentedCount = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="reposted_count", type="integer")
     */
    public $repostedCount = 0;
}