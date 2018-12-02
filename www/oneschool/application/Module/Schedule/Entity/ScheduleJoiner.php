<?php
namespace Lychee\Module\Schedule\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="schedule_joiners", uniqueConstraints={
 *    @ORM\UniqueConstraint(name="schedule_position_udx", columns={"schedule_id", "position", "joiner_id"})
 * })
 */
class ScheduleJoiner {

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
     * @ORM\Column(name="joiner_id", type="bigint")
     * @ORM\Id
     */
    public $joinerId;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="bigint")
     */
    public $position;

}