<?php
namespace Lychee\Module\Schedule;

use Symfony\Component\EventDispatcher\Event;

class ScheduleEvent extends Event {

    const CREATE = 'lychee.schedule.create';
    const CANCEL = 'lychee.schdeule.cancel';

    private $scheduleId;

    /**
     * ScheduleEvent constructor.
     *
     * @param int $scheduleId
     */
    public function __construct($scheduleId) {
        $this->scheduleId = $scheduleId;
    }

    /**
     * @return int
     */
    public function getScheduleId() {
        return $this->scheduleId;
    }

}