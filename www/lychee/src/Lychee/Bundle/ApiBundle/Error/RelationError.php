<?php
namespace Lychee\Bundle\ApiBundle\Error;

use Lychee\Bundle\ApiBundle\Error\Error;

class RelationError {
    const CODE_FollowingTooManyUser = 80001;
    const CODE_YouBeingBlocked = 80002;
    const CODE_YouBlockingThey = 80003;
    const CODE_BlockingTooManyUser = 80004;
    const CODE_CanNotBlockYourself = 80005;

    static public function FollowingTooManyUser() {
        $_message = "Following Too Many User";
        $_display = null;
        return new Error(self::CODE_FollowingTooManyUser, $_message, $_display);
    }

    static public function YouBeingBlocked() {
        $_message = "You Being Blocked";
        $_display = null;
        return new Error(self::CODE_YouBeingBlocked, $_message, $_display);
    }

    static public function YouBlockingThey() {
        $_message = "You Blocking They";
        $_display = null;
        return new Error(self::CODE_YouBlockingThey, $_message, $_display);
    }

    static public function BlockingTooManyUser() {
        $_message = "Blocking Too Many User";
        $_display = null;
        return new Error(self::CODE_BlockingTooManyUser, $_message, $_display);
    }

    static public function CanNotBlockYourself() {
        $_message = "Can Not Block Yourself";
        $_display = null;
        return new Error(self::CODE_CanNotBlockYourself, $_message, $_display);
    }
}