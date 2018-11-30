<?php


namespace Lychee\Module\Measurement\ClientEvent;


interface ClientEventType {
    const POST_SHARE = 1;
    const POST_VIEW = 2;
    const REC_BANNER = 3;
    const GAME_BANNER = 4;
    const PROMOTION_VIEW = 5;
    const OFFICIAL_NOTIFICATION = 6;
}