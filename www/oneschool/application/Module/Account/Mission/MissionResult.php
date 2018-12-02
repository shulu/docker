<?php
namespace Lychee\Module\Account\Mission;

class MissionResult {
    private $level = 0;
    private $experience = 0;
    private $levelUp = false;

    /**
     * @param int $level
     * @param int $experience
     * @param bool $levelUp
     */
    public function __construct($level, $experience, $levelUp) {
        $this->level = $level;
        $this->experience = $experience;
        $this->levelUp = $levelUp;
    }

    /**
     * @return int
     */
    public function getLevel() {
        return $this->level;
    }

    /**
     * @return int
     */
    public function getExperience() {
        return $this->experience;
    }

    /**
     * @return bool
     */
    public function isLevelUp() {
        return $this->levelUp;
    }

    /**
     * @param MissionResult $otherMissionResult
     */
    public function add($otherMissionResult) {
        $this->level = max($otherMissionResult->level, $this->level);
        $this->levelUp = $this->levelUp || $otherMissionResult->levelUp;
        $this->experience += $otherMissionResult->experience;
    }
}