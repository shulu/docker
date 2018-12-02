<?php
namespace Lychee\Module\Schedule\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="schedule_by_user", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="user_starttime_schedule_udx", columns={"user_id", "start_time", "schedule_id"})
 * })
 */
class ScheduleByUser {
    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="bigint")
     * @ORM\Id
     */
    public $userId;

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