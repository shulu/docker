<?php
namespace Lychee\Module\City\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="city_topic")
 */
class CityTopic {

    /**
     * @var int
     * @ORM\Column(name="topic_id", type="bigint")
     * @ORM\Id
     */
    public $topicId;
    
}