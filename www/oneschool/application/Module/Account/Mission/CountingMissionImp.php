<?php
namespace Lychee\Module\Account\Mission;

use Lychee\Module\Account\Mission\Entity\UserMissionState;

class CountingMissionImp extends AbstractMissionImp implements CountingMission {

    private $targetCount;

    /**
     * @param string $fieldName
     * @param int    $experience
     * @param int    $targetCount
     */
    public function __construct($fieldName, $experience, $targetCount) {
        parent::__construct($fieldName, $experience);
        if ($targetCount <= 0) {
            throw new \LogicException('invalid count.');
        }
        $this->targetCount = $targetCount;
    }

    /**
     * @return int
     */
    public function getTargetCount() {
        return $this->targetCount;
    }

    /**
     * @param $state
     *
     * @return bool
     */
    public function accomplish(UserMissionState $state) {
        $fieldName = $this->getFieldName();
        if ($state->$fieldName >= $this->targetCount) {
            return false;
        } else {
            $state->$fieldName += 1;
            if ($state->$fieldName >= $this->targetCount) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * @param UserMissionState $state
     *
     * @return bool
     */
    public function isCompleted(UserMissionState $state) {
        $fieldName = $this->getFieldName();
        if ($state->$fieldName >= $this->targetCount) {
            return true;
        } else {
            return false;
        }
    }
}