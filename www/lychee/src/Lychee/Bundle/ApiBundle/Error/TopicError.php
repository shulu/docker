<?php
namespace Lychee\Bundle\ApiBundle\Error;

use Lychee\Bundle\ApiBundle\Error\Error;

class TopicError {
    const CODE_TopicNotExist = 60001;
    const CODE_TopicNameInvalid = 60002;
    const CODE_FollowingTooMuchTopic = 60003;
    const CODE_TopicAlreadyExist = 60004;
    const CODE_RunOutOfCreatingQuota = 60005;
    const CODE_YouAreNotManager = 60006;
    const CODE_RequireFollow = 60007;
    const CODE_RequireForApply = 60008;
    const CODE_ManagerCannotUnfollow = 60009;
    const CODE_AnnounceTooFrequently = 60010;
    const CODE_CannotFollow = 60011;
    const CODE_TooMuchCoreMember = 60101;
    const CODE_CoreMemberTitleInvalid = 60102;

    static public function TopicNotExist($topicId) {
        $_message = "topic with id {$topicId} not exist";
        $_display = null;
        return new Error(self::CODE_TopicNotExist, $_message, $_display);
    }

    static public function TopicNameInvalid() {
        $_message = "Topic Name Invalid";
        $_display = null;
        return new Error(self::CODE_TopicNameInvalid, $_message, $_display);
    }

    static public function FollowingTooMuchTopic() {
        $_message = "Following Too Much Topic";
        $_display = null;
        return new Error(self::CODE_FollowingTooMuchTopic, $_message, $_display);
    }

    static public function TopicAlreadyExist() {
        $_message = "Topic Already Exist";
        $_display = null;
        return new Error(self::CODE_TopicAlreadyExist, $_message, $_display);
    }

    static public function RunOutOfCreatingQuota() {
        $_message = "Run Out Of Creating Quota";
        $_display = null;
        return new Error(self::CODE_RunOutOfCreatingQuota, $_message, $_display);
    }

    static public function YouAreNotManager() {
        $_message = "You Are Not Manager";
        $_display = null;
        return new Error(self::CODE_YouAreNotManager, $_message, $_display);
    }

    static public function RequireFollow() {
        $_message = "Require Follow";
        $_display = "加入次元才能操作哦";
        return new Error(self::CODE_RequireFollow, $_message, $_display);
    }

    static public function RequireForApply() {
        $_message = "Require For Apply";
        $_display = null;
        return new Error(self::CODE_RequireForApply, $_message, $_display);
    }

    static public function ManagerCannotUnfollow() {
        $_message = "Manager Cannot Unfollow";
        $_display = "领主暂不支持退出次元";
        return new Error(self::CODE_ManagerCannotUnfollow, $_message, $_display);
    }

    static public function AnnounceTooFrequently() {
        $_message = "Announce Too Frequently";
        $_display = "每个次元7日内只能公告1次";
        return new Error(self::CODE_AnnounceTooFrequently, $_message, $_display);
    }

    static public function CannotFollow() {
        $_message = "Cannot Follow";
        $_display = "无法加入该次元";
        return new Error(self::CODE_CannotFollow, $_message, $_display);
    }

    static public function TooMuchCoreMember() {
        $_message = "Too Much Core Member";
        $_display = "核心成员太多";
        return new Error(self::CODE_TooMuchCoreMember, $_message, $_display);
    }

    static public function CoreMemberTitleInvalid() {
        $_message = "Core Member Title Invalid";
        $_display = "无效的核心成员的头衔";
        return new Error(self::CODE_CoreMemberTitleInvalid, $_message, $_display);
    }
}