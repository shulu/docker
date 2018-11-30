<?php
namespace Lychee\Module\Relation\BlackList;

class ParticalBlackListResolver implements BlackListResolver {

    private $blockeds = array();

    public function __construct($blockedList) {
        $this->blockeds = $blockedList;
    }

    /**
     * @param int $userId
     * @param int $anotherId
     *
     * @return bool
     */
    public function isBlocking($anotherId) {
        if (isset($this->blockeds[$anotherId])) {
            return true;
        } else {
            return false;
        }
    }

} 