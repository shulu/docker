<?php

namespace Lychee\Module\Schedule;


class ScheduleJoinResolver {

    private $joinedScheduleMap = array();

    public function __construct($joinedSchedule) {
        foreach ($joinedSchedule as $scheduleId) {
            $this->joinedScheduleMap[$scheduleId] = 1;
        }
    }

    /**
     * @param int $scheduleId
     * @return boolean
     */
    public function hasJoin($scheduleId) {
        if (isset($this->joinedScheduleMap[$scheduleId])) {
            return true;
        } else {
            return false;
        }
    }

}