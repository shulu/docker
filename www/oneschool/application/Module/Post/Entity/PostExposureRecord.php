<?php
namespace Lychee\Module\Post\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="post_exposure_records")
 */
class PostExposureRecord {

    /**
     * @var int
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var int
     * @ORM\Column(name="`time`", type="integer")
     */
    public $time;

    /**
     * @var int
     * @ORM\Column(name="post_id", type="bigint")
     */
    public $postId;

    /**
     * @var int
     * @ORM\Column(name="topic_id", type="bigint", nullable=true)
     */
    public $topicId;

}