<?php
namespace Lychee\Module\Recommendation\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="recommendable_topic")
 */
class RecommendableTopic {

    /**
     * @var int
     * @ORM\Column(name="topic_id", type="bigint")
     * @ORM\Id
     */
    public $topicId;
    
}