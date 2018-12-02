<?php
namespace Lychee\Module\Account\Mission;

use Lychee\Module\Account\Mission\Entity\UserMissionState;

abstract class AbstractMissionImp implements Mission {

    protected $fieldName;
    protected $experience;

    /**
     * @param string $fieldName
     * @param int $experience
     */
    public function __construct($fieldName, $experience) {
        if ($experience <= 0) {
            throw new \LogicException('experience of mission must greater than 0.');
        }
        $this->fieldName = $fieldName;
        $this->experience = $experience;
    }

    /**
     * @return int
     */
    public function getExperience() {
        return $this->experience;
    }

    /**
     * @return string
     */
    public function getFieldName() {
        return $this->fieldName;
    }
}