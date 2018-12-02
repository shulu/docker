<?php
namespace Lychee\Bundle\ApiBundle\Error;

use Lychee\Bundle\ApiBundle\Error\Error;

class CommonError {
    const CODE_UnknownError = 10001;
    const CODE_SystemError = 10002;
    const CODE_SystemBusy = 10003;
    const CODE_ServiceUnavailable = 10004;
    const CODE_PermissionDenied = 10005;
    const CODE_TooManyRequest = 10006;
    const CODE_SignatureInvalid = 10007;
    const CODE_RequestIllegal = 10101;
    const CODE_RequestInvalid = 10102;
    const CODE_ApiNotFound = 10103;
    const CODE_MethodNotAllow = 10104;
    const CODE_ParameterInvalid = 10105;
    const CODE_ParameterMissing = 10106;
    const CODE_ObjectNonExist = 10107;
    const CODE_PleaseUseUTF8 = 10108;
    const CODE_DeviceBlocked = 10109;
    const CODE_ContainsReservedWords = 10110;
	const CODE_ContainsSensitiveWords = 10111;

    static public function UnknownError($message) {
        $_message = "{$message}";
        $_display = null;
        return new Error(self::CODE_UnknownError, $_message, $_display);
    }

    static public function SystemError() {
        $_message = "System Error";
        $_display = null;
        return new Error(self::CODE_SystemError, $_message, $_display);
    }

    static public function SystemBusy() {
        $_message = "System Busy";
        $_display = null;
        return new Error(self::CODE_SystemBusy, $_message, $_display);
    }

    static public function ServiceUnavailable() {
        $_message = "Service Unavailable";
        $_display = null;
        return new Error(self::CODE_ServiceUnavailable, $_message, $_display);
    }

    static public function PermissionDenied() {
        $_message = "Permission Denied";
        $_display = null;
        return new Error(self::CODE_PermissionDenied, $_message, $_display);
    }

    static public function TooManyRequest() {
        $_message = "Too Many Request";
        $_display = "请求太多，请稍后再试";
        return new Error(self::CODE_TooManyRequest, $_message, $_display);
    }

    static public function SignatureInvalid() {
        $_message = "Signature invalid";
        $_display = "签名验证失败";
        return new Error(self::CODE_SignatureInvalid, $_message, $_display);
    }

    static public function RequestIllegal() {
        $_message = "Request Illegal";
        $_display = null;
        return new Error(self::CODE_RequestIllegal, $_message, $_display);
    }

    static public function RequestInvalid() {
        $_message = "Request Invalid";
        $_display = null;
        return new Error(self::CODE_RequestInvalid, $_message, $_display);
    }

    static public function ApiNotFound($uri) {
        $_message = "can not find a api match '{$uri}'";
        $_display = null;
        return new Error(self::CODE_ApiNotFound, $_message, $_display);
    }

    static public function MethodNotAllow($method, $allows) {
        $_message = "method {$method} not allow, and it allows '{$allows}'";
        $_display = null;
        return new Error(self::CODE_MethodNotAllow, $_message, $_display);
    }

    static public function ParameterInvalid($parameter, $value) {
        $_message = "parameter '{$parameter}' with '{$value}' invalid";
        $_display = null;
        return new Error(self::CODE_ParameterInvalid, $_message, $_display);
    }

    static public function ParameterMissing($parameter) {
        $_message = "missing parameter '{$parameter}'";
        $_display = null;
        return new Error(self::CODE_ParameterMissing, $_message, $_display);
    }

    static public function ObjectNonExist($objectId) {
        $_message = "object with id {$objectId} no exist";
        $_display = null;
        return new Error(self::CODE_ObjectNonExist, $_message, $_display);
    }

    static public function PleaseUseUTF8() {
        $_message = "some parameter contains non utf8 chars";
        $_display = null;
        return new Error(self::CODE_PleaseUseUTF8, $_message, $_display);
    }

    static public function DeviceBlocked() {
        $_message = "Device Blocked";
        $_display = "设备因违规已被封印，请联系次元酱！";
        return new Error(self::CODE_DeviceBlocked, $_message, $_display);
    }

    static public function ContainsReservedWords() {
        $_message = "Contains Reserved Words";
        $_display = "官方字已被官方占领~";
        return new Error(self::CODE_ContainsReservedWords, $_message, $_display);
    }

	static public function ContainsSensitiveWords() {
		$_message = "Contains Reserved Words";
		$_display = "内容包含敏感词，请修改后再发布";
		return new Error(self::CODE_ContainsSensitiveWords, $_message, $_display);
	}
}