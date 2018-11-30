<?php
namespace Lychee\Bundle\ApiBundle\Error;

use Lychee\Bundle\ApiBundle\Error\Error;

class LiveError {
    const CODE_PizusAccountHasBeenBound = 120001;
    const CODE_UserNotExist = 120002;
    const CODE_GiftsJsonInvalid = 120101;
    const CODE_SignatureInvalid = 120102;
    const CODE_InsufficientBalance = 120103;

    static public function PizusAccountHasBeenBound() {
        $_message = "Pizus account has been bound";
        $_display = "账号已绑定";
        return new Error(self::CODE_PizusAccountHasBeenBound, $_message, $_display);
    }

    static public function UserNotExist() {
        $_message = "User not exist";
        $_display = "用户不存在(账号未绑定)";
        return new Error(self::CODE_UserNotExist, $_message, $_display);
    }

    static public function GiftsJsonInvalid() {
        $_message = "Gifts json invalid";
        $_display = "无法解析礼物信息";
        return new Error(self::CODE_GiftsJsonInvalid, $_message, $_display);
    }

    static public function SignatureInvalid() {
        $_message = "Signature invalid";
        $_display = "签名错误";
        return new Error(self::CODE_SignatureInvalid, $_message, $_display);
    }

    static public function InsufficientBalance() {
        $_message = "Insufficient balance";
        $_display = "余额不足";
        return new Error(self::CODE_InsufficientBalance, $_message, $_display);
    }
}