<?php

namespace Lychee\Module\Account;


use Symfony\Component\EventDispatcher\Event;

class AccountEvent extends Event {
    const CREATE = 'lychee.account.create';
    const UPDATE = 'lychee.account.update';
    const UPDATE_NICKNAME = 'lychee.account.update_nickname';
    const FREEZE = 'lychee.account.freeze';
    const UNFREEZE = 'lychee.account.unfreeze';

    /**
     * @var int
     */
    private $accountId;

    /**
     * @param int $accountId
     */
    public function __construct($accountId) {
        $this->accountId = $accountId;
    }

    /**
     * @return int
     */
    public function getAccountId() {
        return $this->accountId;
    }
}