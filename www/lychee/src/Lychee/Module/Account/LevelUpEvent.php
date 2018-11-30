<?php
namespace Lychee\Module\Account;

use Symfony\Component\EventDispatcher\Event;

class LevelUpEvent extends Event {

    const NAME = 'lychee.account.levelup';

    private $accountId;
    private $oldLevel;
    private $newLevel;

    /**
     * @param int $accountId
     * @param int $oldLevel
     * @param int $newLevel
     */
    public function __construct($accountId, $oldLevel, $newLevel) {
        $this->accountId = $accountId;
        $this->oldLevel = $oldLevel;
        $this->newLevel = $newLevel;
    }

    /**
     * @return int
     */
    public function getAccountId() {
        return $this->accountId;
    }

    /**
     * @return int
     */
    public function getOldLevel() {
        return $this->oldLevel;
    }

    /**
     * @return int
     */
    public function getNewLevel() {
        return $this->newLevel;
    }

}