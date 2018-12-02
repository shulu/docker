<?php
namespace Lychee\Bundle\ApiBundle\Error;

use Lychee\Bundle\ApiBundle\Error\Error;

class PostError {
    const CODE_PostNotExist = 30001;
    const CODE_NotYourOwnPost = 30002;
    const CODE_AnnotationTooLong = 30004;
    const CODE_AnnotationError = 30005;
    const CODE_ContentTooLong = 30006;
    const CODE_TitleTooLong = 30007;
    const CODE_ContentContainSensitiveWord = 30008;
    const CODE_TitleContainSensitiveWord = 30009;
    const CODE_UrlIsForbidden = 30010;
    const CODE_StickyPostExceedLimit = 30011;
    const CODE_ResourceIsForbidden = 30012;
    const CODE_ResourceInvalid = 30013;
    const CODE_Forbidden = 30014;
    const CODE_PostForbidden = 30015;
	const CODE_CityNotSet = 30016;
    const CODE_BGMInvalid = 30017;
    const CODE_SVIDInvalid = 30018;
    const CODE_VideoUrlInvalid = 30019;
    const CODE_SVForbidden = 30020;

    static public function PostNotExist($postId) {
        $_message = "post with id {$postId} not exist";
        $_display = null;
        return new Error(self::CODE_PostNotExist, $_message, $_display);
    }

    static public function NotYourOwnPost() {
        $_message = "Not Your Own Post";
        $_display = null;
        return new Error(self::CODE_NotYourOwnPost, $_message, $_display);
    }

    static public function AnnotationTooLong($limit) {
        $_message = "annotation is too long, it should have {$limit} characters or less.";
        $_display = null;
        return new Error(self::CODE_AnnotationTooLong, $_message, $_display);
    }

    static public function AnnotationError() {
        $_message = "annotation must with json format";
        $_display = null;
        return new Error(self::CODE_AnnotationError, $_message, $_display);
    }

    static public function ContentTooLong($limit) {
        $_message = "content is too long. it should have {$limit} characters or less.";
        $_display = "内容不能超过{$limit}个字";
        return new Error(self::CODE_ContentTooLong, $_message, $_display);
    }

    static public function TitleTooLong($limit) {
        $_message = "title is too long. it should have {$limit} characters or less.";
        $_display = null;
        return new Error(self::CODE_TitleTooLong, $_message, $_display);
    }

    static public function ContentContainSensitiveWord() {
        $_message = "Content Contain Sensitive Word";
        $_display = null;
        return new Error(self::CODE_ContentContainSensitiveWord, $_message, $_display);
    }

    static public function TitleContainSensitiveWord() {
        $_message = "Title Contain Sensitive Word";
        $_display = null;
        return new Error(self::CODE_TitleContainSensitiveWord, $_message, $_display);
    }

    static public function UrlIsForbidden() {
        $_message = "your content contains a forbidden url.";
        $_display = "嘤嘤嘤，帖子内暂不支持此站链接";
        return new Error(self::CODE_UrlIsForbidden, $_message, $_display);
    }

    static public function StickyPostExceedLimit() {
        $_message = "Sticky Post Exceed Limit";
        $_display = null;
        return new Error(self::CODE_StickyPostExceedLimit, $_message, $_display);
    }

    static public function ResourceIsForbidden() {
        $_message = "Resource Is Forbidden";
        $_display = "嘤嘤嘤，暂不支持此站链接";
        return new Error(self::CODE_ResourceIsForbidden, $_message, $_display);
    }

    static public function ResourceInvalid() {
        $_message = "Resource Invalid";
        $_display = "链接无效，换个链接再来发贴吧";
        return new Error(self::CODE_ResourceInvalid, $_message, $_display);
    }

    static public function Forbidden() {
        $_message = "Forbidden";
        $_display = "禁止发布带有百度云盘的内容";
        return new Error(self::CODE_Forbidden, $_message, $_display);
    }

    static public function PostForbidden() {
        $_message = "Post Forbidden";
        $_display = "发帖失败，请升级到最新的次元社客户端";
        return new Error(self::CODE_PostForbidden, $_message, $_display);
    }

	static public function CityNotSetup() {
		$_message = "CityNotSetup";
		$_display = "请先设置您的所在地";
		return new Error(self::CODE_CityNotSet, $_message, $_display);
	}

    static public function BGMInvalid() {
        $_message = "BGM Id Invalid";
        $_display = "嘤嘤嘤，短视频背景音乐不合法";
        return new Error(self::CODE_BGMInvalid, $_message, $_display);
    }

    static public function SVIdInvalid() {
        $_message = "SV Id Invalid";
        $_display = "嘤嘤嘤，短视频文件不存在";
        return new Error(self::CODE_SVIDInvalid, $_message, $_display);
    }
    static public function VideoUrlInvalid() {
        $_message = "Video Url Invalid";
        $_display = "视频url无效";
        return new Error(self::CODE_VideoUrlInvalid, $_message, $_display);
    }
    static public function SVForbidden() {
        $_message = "SV Forbidden";
        $_display = "嘤嘤嘤，不允许发布短视频";
        return new Error(self::CODE_SVForbidden, $_message, $_display);
    }
}