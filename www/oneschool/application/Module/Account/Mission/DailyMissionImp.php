<?php
namespace Lychee\Module\Account\Mission;

use Lychee\Module\Account\Mission\Entity\UserMissionState;

class DailyMissionImp extends AbstractMissionImp implements DailyMission {

    static protected $today;
    /**
     * @return \DateTime
     */
    static protected function getToday() {
        if (self::$today == null) {
            self::$today = (new \DateTime())->setTime(0, 0, 0);
        }

        return self::$today;
    }

    private $dailyCount;

    /**
     * @param string $fieldName
     * @param int $experience
     * @param int $dailyCount
     */
    public function __construct($fieldName, $experience, $dailyCount) {
        parent::__construct($fieldName, $experience);
        if ($dailyCount <= 0) {
            throw new \LogicException('daily count of mission must greater than 0.');
        }
        $this->dailyCount = $dailyCount;
    }

    /**
     * @return int
     */
    public function getDailyCount() {
        return $this->dailyCount;
    }

    /**
     * @param $state
     *
     * @return bool
     */
    public function accomplish(UserMissionState $state) {
        $today = self::getToday();
        if ($state->dailyDate === null || $state->dailyDate < $today) {
            $state->dailyDate = $today;
            $state->dailyComment = 0;
            $state->dailyImageComment = 0;
            $state->dailyLikePost = 0;
            $state->dailyPost = 0;
            $state->dailyShare = 0;
            $state->dailySignin = 0;
        }

        $fieldName = $this->getFieldName();
        if ($state->dailyDate > $today || $state->$fieldName >= $this->getDailyCount()) {
            return false;
        } else {
            $state->$fieldName += 1;
            return true;
        }
    }

    /**
     * @param UserMissionState $state
     *
     * @return bool
     */
    public function isCompleted(UserMissionState $state) {
        $today = self::getToday();
        $fieldName = $this->getFieldName();

        if ($state->dailyDate === null || $state->dailyDate < $today) {
            return false;
        } else if ($state->dailyDate > $today || $state->$fieldName >= $this->getDailyCount()) {
            return true;
        } else {
            return false;
        }
    }
}