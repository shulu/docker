<?php
namespace Lychee\Module\Topic\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="topic_default_group")
 */
class TopicDefaultGroup {

    /**
     * @var int
     * @ORM\Column(name="topic_id", type="bigint")
     * @ORM\Id
     */
    public $topicId;

    /**
     * @var int
     * @ORM\Column(name="group_id", type="bigint")
     */
    public $groupId;

}