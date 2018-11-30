<?php

namespace Lychee\Module\Like;

interface LikeResolver {
    /**
     * @param int $targetId
     *
     * @return boolean
     */
    public function isLiked($targetId);
} 