<?php
namespace Lychee\Module\Schedule\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="schedule", indexes={
 *   @ORM\Index(name="starttime_idx", columns={"start_time"})
 * })
 */
class Schedule {

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Id
     */
    public $id;

    /**
     * @var int
     *
     * @ORM\Column(name="creator_id", type="bigint")
     */
    public $creatorId;

    /**
     * @var int
     *
     * @ORM\Column(name="topic_id", type="bigint")
     */
    public $topicId;

    /**
     * @var int
     *
     * @ORM\Column(name="post_id", type="bigint")
     */
    public $postId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=60, nullable=true)
     */
    public $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=400, nullable=true)
     */
    public $description;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=400, nullable=true)
     */
    public $address;

    /**
     * @var string
     *
     * @ORM\Column(name="poi", type="string", length=100, nullable=true)
     */
    public $poi;

    /**
     * @var float
     *
     * @ORM\Column(name="longitude", type="float", nullable=true)
     */
    public $longitude;

    /**
     * @var float
     *
     * @ORM\Column(name="latitude", type="float", nullable=true)
     */
    public $latitude;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_time", type="datetime")
     */
    public $startTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_time", type="datetime")
     */
    public $endTime;

    /**
     * @var bool
     *
     * @ORM\Column(name="cancelled", type="boolean")
     */
    public $cancelled = false;

    /**
     * @var int
     *
     * @ORM\Column(name="canceller_id", type="bigint", nullable=true)
     */
    public $cancellerId;

    /**
     * @var int
     *
     * @ORM\Column(name="joiner_count", type="integer")
     */
    public $joinerCount = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="last_notify_time", type="integer", nullable=true)
     */
    public $lastNotifyTime;

}