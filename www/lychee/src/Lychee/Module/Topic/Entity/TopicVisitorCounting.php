<?php
namespace Lychee\Module\Topic\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="topic_visitor_counting", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="user_id_topic_id_udx", columns={"user_id", "topic_id"})
 * }, indexes={
 *   @ORM\Index(name="user_id_count_idx", columns={"user_id", "count"})
 * })
 */
class TopicVisitorCounting {
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="bigint")
     */
    public $id;

    /**
     * @var int
     * @ORM\Column(name="topic_id", type="bigint")
     */
    public $topicId;

    /**
     * @var int
     * @ORM\Column(name="user_id", type="bigint")
     */
    public $userId;

    /**
     * @var int
     * @ORM\Column(name="count", type="bigint")
     */
    public $count;
}