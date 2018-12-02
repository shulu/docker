<?php
namespace Lychee\Module\Recommendation\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="recommendation_topic_test")
 */
class RecommendationTopicTest {

    const PROPERTY_ZHAI = 'zhai';
    const PROPERTY_MENG = 'meng';
    const PROPERTY_RAN = 'ran';
    const PROPERTY_FU = 'fu';
    const PROPERTY_JIAN = 'jian';
    const PROPERTY_AO = 'ao';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="property", type="string", length=10)
     */
    public $property;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="score", type="smallint")
     */
    public $score;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="topic_id", type="bigint")
     */
    public $topicId;
} 