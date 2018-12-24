<?php

namespace app\module\account\mission;


interface MissionType {
    const INVITE = 1;
    const FOLLOW_TOPIC = 2;
    const FILL_PROFILE = 3;
    const SET_FAVORITE_TOPIC = 4;
    const SET_ATTRIBUTES = 5;

    const DAILY_LIKE_POST = 101;
    const DAILY_COMMENT = 102;
    const DAILY_IMAGE_COMMENT = 103;
    const DAILY_POST = 104;
    const DAILY_SHARE = 105;
    const DAILY_SIGNIN = 106;
}