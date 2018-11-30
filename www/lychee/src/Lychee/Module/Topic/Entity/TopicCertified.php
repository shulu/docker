<?php
namespace Lychee\Module\Topic\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="topic_certified")
 */
class TopicCertified {

    /**
     * @var int
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     */
    public $id;

    /**
     * @var int
     * @ORM\Column(name="topic_id", type="bigint")
     */
    public $topicId;

}