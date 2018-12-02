<?php
namespace Lychee\Bundle\ApiBundle\Error;

use Lychee\Bundle\ApiBundle\Error\Error;

class AuthenticationError {
    const CODE_EmailUsed = 20001;
    const CODE_EmailInvalid = 20002;
    const CODE_PasswordInvalid = 20003;
    const CODE_NicknameUsed = 20004;
    const CODE_NicknameInvalid = 20005;
    const CODE_PhoneUsed = 20006;
    const CODE_PhoneInvalid = 20007;
    const CODE_PhoneVerifyFail = 20008;
    const CODE_RequirePhone = 20009;
    const CODE_EmailNonexist = 20101;
    const CODE_NicknameNonexist = 20102;
    const CODE_AccountOrPasswordError = 20103;
    const CODE_PasswordWrong = 20104;
    const CODE_PhoneNonexist = 20105;
    const CODE_PasswordIsExist = 20106;
    const CODE_BadAuthentication = 20201;
    const CODE_AccessTokenInvalid = 20202;
    const CODE_WeiboTokenInvalid = 20301;
    const CODE_QQTokenInvalid = 20401;
    const CODE_QQOpenIdInvalid = 20402;
    const CODE_WechatTokenInvalid = 20501;
	const CODE_BilibiliAccessKeyInvalid = 20601;
	const CODE_DmzjTokenInvalid = 20701;
	const CODE_FacebookTokenInvalid = 20702;

    static public function EmailUsed() {
        $_message = "Email Used";
        $_display = null;
        return new Error(self::CODE_EmailUsed, $_message, $_display);
    }

    static public function EmailInvalid() {
        $_message = "Email Invalid";
        $_display = null;
        return new Error(self::CODE_EmailInvalid, $_message, $_display);
    }

    static public function PasswordInvalid() {
        $_message = "Password Invalid";
        $_display = null;
        return new Error(self::CODE_PasswordInvalid, $_message, $_display);
    }

    static public function NicknameUsed() {
        $_message = "Nickname Used";
        $_display = null;
        return new Error(self::CODE_NicknameUsed, $_message, $_display);
    }

    static public function NicknameInvalid() {
        $_message = "Nickname Invalid";
        $_display = null;
        return new Error(self::CODE_NicknameInvalid, $_message, $_display);
    }

    static public function PhoneUsed() {
        $_message = "Phone Used";
        $_display = null;
        return new Error(self::CODE_PhoneUsed, $_message, $_display);
    }

    static public function PhoneInvalid() {
        $_message = "Phone Invalid";
        $_display = null;
        return new Error(self::CODE_PhoneInvalid, $_message, $_display);
    }

    static public function PhoneVerifyFail() {
        $_message = "Phone Verify Fail";
        $_display = null;
        return new Error(self::CODE_PhoneVerifyFail, $_message, $_display);
    }

    static public function EmailNonexist() {
        $_message = "Email Nonexist";
        $_display = null;
        return new Error(self::CODE_EmailNonexist, $_message, $_display);
    }

    static public function NicknameNonexist() {
        $_message = "Nickname Nonexist";
        $_display = null;
        return new Error(self::CODE_NicknameNonexist, $_message, $_display);
    }

    static public function AccountOrPasswordError() {
        $_message = "Account Or Password Error";
        $_display = null;
        return new Error(self::CODE_AccountOrPasswordError, $_message, $_display);
    }

    static public function PasswordWrong() {
        $_message = "Password Wrong";
        $_display = null;
        return new Error(self::CODE_PasswordWrong, $_message, $_display);
    }

    static public function PhoneNonexist() {
        $_message = "Phone Nonexist";
        $_display = null;
        return new Error(self::CODE_PhoneNonexist, $_message, $_display);
    }

    static public function BadAuthentication() {
        $_message = "Bad Authentication";
        $_display = null;
        return new Error(self::CODE_BadAuthentication, $_message, $_display);
    }

    static public function AccessTokenInvalid() {
        $_message = "AccessToken Invalid";
        $_display = null;
        return new Error(self::CODE_AccessTokenInvalid, $_message, $_display);
    }

    static public function WeiboTokenInvalid() {
        $_message = "Weibo Token Invalid";
        $_display = null;
        return new Error(self::CODE_WeiboTokenInvalid, $_message, $_display);
    }

    static public function QQTokenInvalid() {
        $_message = "Q Q Token Invalid";
        $_display = null;
        return new Error(self::CODE_QQTokenInvalid, $_message, $_display);
    }

    static public function QQOpenIdInvalid() {
        $_message = "Q Q Open Id Invalid";
        $_display = null;
        return new Error(self::CODE_QQOpenIdInvalid, $_message, $_display);
    }

    static public function WechatTokenInvalid() {
        $_message = "Wechat Token Invalid";
        $_display = null;
        return new Error(self::CODE_WechatTokenInvalid, $_message, $_display);
    }

	static public function BilibiliAccessKeyInvalid() {
		$_message = "Bilibili Access Key Invalid";
		$_display = null;
		return new Error(self::CODE_BilibiliAccessKeyInvalid, $_message, $_display);
	}

	static public function DmzjTokenInvalid() {
		$_message = "Dmzj Token Invalid";
		$_display = null;
		return new Error(self::CODE_DmzjTokenInvalid, $_message, $_display);
	}

    static public function FacebookTokenInvalid() {
        $_message = "Facebook Token Invalid";
        $_display = null;
        return new Error(self::CODE_FacebookTokenInvalid, $_message, $_display);
    }


    static public function PasswordIsExist() {
        $_message = "Password is exist";
        $_display = null;
        return new Error(self::CODE_PasswordIsExist, $_message, $_display);
    }

    static public function RequirePhone() {
        $_message = "Require Phone";
        $_display = "需要绑定手机后才能操作哦";
        return new Error(self::CODE_RequirePhone, $_message, $_display);
    }
}