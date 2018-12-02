<?php
namespace Lychee\Bundle\ApiBundle\Error;

use Lychee\Bundle\ApiBundle\Error\Error;

class IMError {
    const CODE_PermissionDenied = 90001;
    const CODE_CanNotKickYourself = 90002;
    const CODE_MemberExceedLimit = 90003;
    const CODE_GroupNonExist = 90004;
    const CODE_YouHaveBeenKicked = 90005;
    const CODE_JoinTooMuchGroupInTopic = 90006;

    static public function PermissionDenied() {
        $_message = "Permission Denied";
        $_display = null;
        return new Error(self::CODE_PermissionDenied, $_message, $_display);
    }

    static public function CanNotKickYourself() {
        $_message = "Can Not Kick Yourself";
        $_display = null;
        return new Error(self::CODE_CanNotKickYourself, $_message, $_display);
    }

    static public function MemberExceedLimit() {
        $_message = "Member Exceed Limit";
        $_display = null;
        return new Error(self::CODE_MemberExceedLimit, $_message, $_display);
    }

    static public function GroupNonExist() {
        $_message = "Group Non Exist";
        $_display = null;
        return new Error(self::CODE_GroupNonExist, $_message, $_display);
    }

    static public function YouHaveBeenKicked() {
        $_message = "You Have Been Kicked";
        $_display = null;
        return new Error(self::CODE_YouHaveBeenKicked, $_message, $_display);
    }

    static public function JoinTooMuchGroupInTopic() {
        $_message = "Join Too Much Group In Topic";
        $_display = "每人每次元最多加入10个群聊";
        return new Error(self::CODE_JoinTooMuchGroupInTopic, $_message, $_display);
    }
}