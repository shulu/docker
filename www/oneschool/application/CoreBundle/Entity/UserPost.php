<?php
namespace Lychee\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_post", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="user_topic_post_udx", columns={"user_id", "topic_id", "post_id"})
 * })
 */
class UserPost {
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
     * @ORM\Column(name="post_id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public $postId;

    /**
     * @var int
     *
     * @ORM\Column(name="topic_id", type="bigint")
     */
    public $topicId;
} 