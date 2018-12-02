<?php

namespace Lychee\Module\Notification\Push;


abstract class PushEventType {
    const MENTION = 1;
    const COMMENT = 2;
    const IMAGE_COMMENT = 3;
    const REPLY = 4;
    const FOLLOW = 5;
    const LIKE = 6;
    const MESSAGE = 7;
    const TOPIC_APPLY = 8;
    const TOPIC_APPLY_CONFIRM = 9;
    const TOPIC_KICKOUT = 10;
    const SCHEDULE_CANCELLED = 11;
    const SCHEDULE_ABOUT_TO_START = 12;
    const TOPIC_APPLY_REJECT = 13;
    const TOPIC_ANNOUNCEMENT = 14;
    const BECOME_CORE_MEMBER = 15;
    const REMOVE_CORE_MEMBER = 16;
    const TOPIC_CREATE_CONFIRMED = 17;
    const TOPIC_CREATE_REJECTED = 18;
    const ILLEGAL_POST_DELETED = 19;
    const POST = 20;
    const ANCHOR = 21;

    /**
     * @param $string
     * @return int
     * @throws \InvalidArgumentException
     */
    static public function fromString($string) {
        switch ($string) {
            case 'like':
                return self::LIKE;
            case 'reply':
                return self::REPLY;
            case 'comment':
                return self::COMMENT;
            case 'follow':
                return self::FOLLOW;
            case 'image_comment':
                return self::IMAGE_COMMENT;
            case 'mention':
                return self::MENTION;
            case 'message':
                return self::MESSAGE;
            case 'topic_apply':
                return self::TOPIC_APPLY;
            case 'topic_apply_confirm':
                return self::TOPIC_APPLY_CONFIRM;
            case 'topic_kickout':
                return self::TOPIC_KICKOUT;
            case 'schedule_cancelled':
                return self::SCHEDULE_CANCELLED;
            case 'schedule_about_to_start':
                return self::SCHEDULE_ABOUT_TO_START;
            case 'topic_apply_reject':
                return self::TOPIC_APPLY_REJECT;
            case 'topic_announcement':
                return self::TOPIC_ANNOUNCEMENT;
            case 'become_core_member':
                return self::BECOME_CORE_MEMBER;
            case 'remove_core_member':
                return self::REMOVE_CORE_MEMBER;
            case 'topic_create_confirmed':
                return self::TOPIC_CREATE_CONFIRMED;
            case 'topic_create_rejected':
                return self::TOPIC_CREATE_REJECTED;
            case 'illegal_post_deleted':
                return self::ILLEGAL_POST_DELETED;
            case 'post':
                return self::POST;
	        case 'anchor':
	        	return self::ANCHOR;
            default:
                throw new \InvalidArgumentException($string);
        }
    }

    /**
     * @param int $type
     * @return string
     * @throws \InvalidArgumentException
     */
    static public function toString($type) {
        switch ($type) {
            case self::MENTION:
                return 'mention';
            case self::COMMENT:
                return 'comment';
            case self::IMAGE_COMMENT:
                return 'image_comment';
            case self::REPLY:
                return 'reply';
            case self::FOLLOW:
                return 'follow';
            case self::LIKE:
                return 'like';
            case self::MESSAGE:
                return 'message';
            case self::TOPIC_APPLY:
                return 'topic_apply';
            case self::TOPIC_APPLY_CONFIRM:
                return 'topic_apply_confirm';
            case self::TOPIC_KICKOUT:
                return 'topic_kickout';
            case self::SCHEDULE_CANCELLED:
                return 'schedule_cancelled';
            case self::SCHEDULE_ABOUT_TO_START:
                return 'schedule_about_to_start';
            case self::TOPIC_APPLY_REJECT:
                return 'topic_apply_reject';
            case self::TOPIC_ANNOUNCEMENT:
                return 'topic_announcement';
            case self::BECOME_CORE_MEMBER:
                return 'become_core_member';
            case self::REMOVE_CORE_MEMBER:
                return 'remove_core_member';
            case self::TOPIC_CREATE_CONFIRMED:
                return 'topic_create_confirmed';
            case self::TOPIC_CREATE_REJECTED:
                return 'topic_create_rejected';
            case self::ILLEGAL_POST_DELETED:
                return 'illegal_post_deleted';
            case self::POST:
                return 'post';
	        case self::ANCHOR:
	        	return 'anchor';
            default:
                throw new \InvalidArgumentException($type);
        }
    }
}