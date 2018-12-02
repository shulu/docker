<?php
namespace Lychee\Module\Topic\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="topic_group_rel")
 */
class TopicGroupRel {
    /**
     * @ORM\Id
     * @ORM\Column(name="topic_id", type="bigint", options={"unsigned":true, "comment":"次元id，topic.id"})
     */
    public $topicId;

    /**
     * @ORM\Id
     * @ORM\Column(name="group_id", type="integer", options={"unsigned":true, "comment":"次元分组id，topic_group.id"})
     */
    public $groupId;

    /**
     * @var int
     *
     * @ORM\Column(name="update_time", type="integer")
     */
    public $updateTime;
}