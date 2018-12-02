<?php
namespace Lychee\Module\Topic\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="topic_core_member_meta")
 */
class TopicCoreMemberMeta {

    /**
     * @var int
     * @ORM\Column(name="topic_id", type="bigint")
     * @ORM\Id
     */
    public $topicId;

    /**
     * @var int
     * @ORM\Column(name="core_member_count", type="smallint")
     */
    public $coreMemberCount;

}