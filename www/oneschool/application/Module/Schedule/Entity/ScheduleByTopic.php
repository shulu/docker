<?php
namespace Lychee\Module\Schedule\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="schedule_by_topic", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="topic_starttime_schedule_udx", columns={"topic_id", "start_time", "schedule_id"})
 * })
 */
class ScheduleByTopic {

    /**
     * @var int
     *
     * @ORM\Column(name="topic_id", type="bigint")
     * @ORM\Id
     */
    public $topicId;

    /**
     * @var int
     *
     * @ORM\Column(name="schedule_id", type="bigint")
     * @ORM\Id
     */
    public $scheduleId;

    /**
     * @var int
     *
     * @ORM\Column(name="start_time", type="integer")
     */
    public $startTime;

    /**
     * @var int
     *
     * @ORM\Column(name="post_id", type="bigint")
     */
    public $postId;
}