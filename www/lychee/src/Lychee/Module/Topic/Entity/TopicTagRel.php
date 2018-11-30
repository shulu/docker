<?php
namespace Lychee\Module\Topic\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="topic_tag_rel")
 */
class TopicTagRel {
    /**
     * @ORM\Id
     * @ORM\Column(name="topic_id", type="bigint")
     */
    public $topicId;

    /**
     * @ORM\Id
     * @ORM\Column(name="tag_id", type="integer")
     */
    public $tagId;

    /**
     * @var int
     *
     * @ORM\Column(name="update_time", type="integer")
     */
    public $updateTime;
}