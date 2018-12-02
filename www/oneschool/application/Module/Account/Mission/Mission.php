<?php

namespace Lychee\Module\Account\Mission;

use Lychee\Module\Account\Mission\Entity\UserMissionState;

interface Mission {
    /**
     * @return int
     */
    public function getExperience();

    /**
     * @param $state
     *
     * @return bool
     */
    public function accomplish(UserMissionState $state);

    /**
     * @param UserMissionState $state
     *
     * @return bool
     */
    public function isCompleted(UserMissionState $state);
}