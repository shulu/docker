<?php
namespace Lychee\Bundle\ApiBundle\Error;

use Lychee\Bundle\ApiBundle\Error\Error;

class AccountError {
    const CODE_UserNotExist = 50001;
    const CODE_UserFrozen = 50002;
    const CODE_SignatureInvalid = 50101;
    const CODE_RenameOnceInTwentyFourHour = 50102;

    static public function UserNotExist($userId) {
        $_message = "user with id {$userId} not exist";
        $_display = null;
        return new Error(self::CODE_UserNotExist, $_message, $_display);
    }

    static public function UserFrozen() {
        $_message = "User Frozen";
        $_display = null;
        return new Error(self::CODE_UserFrozen, $_message, $_display);
    }

    static public function SignatureInvalid() {
        $_message = "Signature Invalid";
        $_display = null;
        return new Error(self::CODE_SignatureInvalid, $_message, $_display);
    }

    static public function RenameOnceInTwentyFourHour() {
        $_message = "Rename Once In Twenty Four Hour";
        $_display = "每次修改昵称后需要等待24小时才能再次修改";
        return new Error(self::CODE_RenameOnceInTwentyFourHour, $_message, $_display);
    }
}