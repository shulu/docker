<?php
namespace Lychee\Module\Recommendation;

interface RecommendationType {
    const TOPIC = 'topic';
    const POST = 'post';
    const COMMENT = 'comment';
    const USER = 'user';
    const SPECIAL_SUBJECT = 'special_subject';
    const APP = 'app';
    const VIDEO_POST = 'video_post';
}