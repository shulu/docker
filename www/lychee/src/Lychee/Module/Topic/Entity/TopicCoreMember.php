<?php
namespace Lychee\Module\Topic\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="topic_core_members")
 */
class TopicCoreMember {

    /**
     * @var int
     * @ORM\Column(name="topic_id", type="bigint")
     * @ORM\Id
     */
    public $topicId;

    /**
     * @var int
     * @ORM\Column(name="user_id", type="bigint")
     * @ORM\Id
     */
    public $userId;

    /**
     * @var int
     * @ORM\Column(name="`order`", type="smallint")
     */
    public $order;

    /**
     * @var string
     * @ORM\Column(name="title", type="string", length=20)
     */
    public $title;

}