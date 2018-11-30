<?php
namespace Lychee\Module\Like;

class ParticalLikeResolver implements LikeResolver {

    private $likedMap;

    public function __construct($likedMap) {
        $this->likedMap = $likedMap;
    }

    /**
     * @param int $targetId
     *
     * @return boolean
     */
    public function isLiked($targetId) {
        if (isset($this->likedMap[$targetId])) {
            return true;
        } else {
            return false;
        }
    }

} 