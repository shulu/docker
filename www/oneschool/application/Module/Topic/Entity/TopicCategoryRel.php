<?php
namespace Lychee\Module\Topic\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="topic_category_rel", indexes={
 *   @ORM\Index(name="topic_category_idx", columns={"topic_id", "category_id"})
 * })
 */
class TopicCategoryRel {
    /**
     * @ORM\Id
     * @ORM\Column(name="category_id", type="integer")
     */
    public $categoryId;

    /**
     * @ORM\Id
     * @ORM\Column(name="topic_id", type="bigint")
     */
    public $topicId;
}