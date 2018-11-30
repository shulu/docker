<?php
namespace Lychee\Module\Post\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("topic_sticky_post", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="topic_post_index", columns={"topic_id", "level", "post_id"})
 * })
 */
class TopicStickyPost {

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(name="id", type="bigint")
     */
    public $id;

    /**
     * @var int
     *
     * @ORM\Column(name="topic_id", type="bigint")
     */
    public $topicId;

    /**
     * @var int
     *
     * @ORM\Column(name="post_id", type="bigint")
     */
    public $postId;

    /**
     * @var int
     *
     * @ORM\Column(name="level", type="smallint")
     */
    public $level;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_time", type="datetime")
     */
    public $createTime;
} 