<?php
namespace Lychee\Bundle\ApiBundle\Error;

use Lychee\Bundle\ApiBundle\Error\Error;

class ScheduleError {
    const CODE_ScheduleNonExist = 100001;
    const CODE_ScheduleStarted = 100002;
    const CODE_ScheduleCancelled = 100003;
    const CODE_ScheduleEnded = 100004;

    static public function ScheduleNonExist() {
        $_message = "Schedule Non Exist";
        $_display = null;
        return new Error(self::CODE_ScheduleNonExist, $_message, $_display);
    }

    static public function ScheduleStarted() {
        $_message = "Schedule Started";
        $_display = null;
        return new Error(self::CODE_ScheduleStarted, $_message, $_display);
    }

    static public function ScheduleCancelled() {
        $_message = "Schedule Cancelled";
        $_display = null;
        return new Error(self::CODE_ScheduleCancelled, $_message, $_display);
    }

    static public function ScheduleEnded() {
        $_message = "Schedule Ended";
        $_display = "活动已结束";
        return new Error(self::CODE_ScheduleEnded, $_message, $_display);
    }
}