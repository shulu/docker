<?php

namespace Lychee\Module\Recommendation;

interface UserRankingType {
    const FOLLOWED = 'followed';
    const COMMENT = 'comment';
    const POST = 'post';
    const IMAGE_COMMENT = 'image_comment';
} 