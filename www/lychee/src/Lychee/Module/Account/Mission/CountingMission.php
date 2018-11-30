<?php

namespace Lychee\Module\Account\Mission;


interface CountingMission extends Mission {
    /**
     * @return int
     */
    public function getTargetCount();
}