<?php

namespace Lychee\Module\Account\Mission;


interface DailyMission extends Mission {
    /**
     * @return int
     */
    public function getDailyCount();
}