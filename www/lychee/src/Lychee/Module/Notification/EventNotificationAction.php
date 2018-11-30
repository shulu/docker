<?php

namespace Lychee\Module\Notification;


interface EventNotificationAction {
    const FOLLOW = 1;
    const COMMENT = 2;
    const REPOST = 3;
    const MENTION_IN_COMMENT = 4;
    const MENTION_IN_POST = 5;
    const INVITE_TOPIC = 6;
    const TOPIC_APPLY_TO_FOLLOW = 7;
    const TOPIC_APPLY_CONFIRMED = 8;
    const TOPIC_KICKOUT = 9;
    const SCHEDULE_CANCELLED = 10;
    const SCHEDULE_ABOUT_TO_START = 11;
    const TOPIC_APPLY_REJECTED = 12;
    const TOPIC_ANNOUNCEMENT = 13;
    const BECOME_CORE_MEMBER = 14;
    const REMOVE_CORE_MEMBER = 15;
    const TOPIC_CREATE_CONFIRMED = 16;
    const TOPIC_CREATE_REJECTED = 17;
    const ILLEGAL_POST_DELETED = 18;
    const MY_ILLEGAL_POST_DELETED = 19;
}