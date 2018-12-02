<?php
namespace Lychee\Module\Recommendation\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="topic_category_score")
 */
class TopicCategoryScore {

    /**
     * @var int
     * @ORM\Column(name="category_id", type="integer")
     * @ORM\Id
     */
    public $categoryId;

    /**
     * @var int
     * @ORM\Column(name="score", type="integer")
     * @ORM\Id
     */
    public $score;

    /**
     * @var int
     * @ORM\Column(name="`order`", type="integer")
     * @ORM\Id
     */
    public $order;

    /**
     * @var int
     * @ORM\Column(name="topic_id", type="bigint")
     * @ORM\Id
     */
    public $topicId;
}