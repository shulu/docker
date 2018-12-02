<?php
namespace Lychee\Component\Task;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="task_state", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="name_unique", columns={"task_name"})
 * })
 */
class TaskState {
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(name="task_name", type="string", length=200)
     */
    public $taskName;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_check_time", type="datetime", nullable=true)
     */
    public $lastCheckTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="run_interval", type="integer")
     */
    public $runInterval;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="next_run_time", type="datetime", nullable=true)
     */
    public $nextRunTime;

    /**
     * @var boolean
     *
     * @ORM\Column(name="disabled", type="boolean")
     */
    public $disabled = false;
}