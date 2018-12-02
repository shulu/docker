<?php
namespace Lychee\Module\Favorite;

class ParticalFavoritePostResolver implements FavoriteResolver {

    private $favoritedMap;

    public function __construct($likedMap) {
        $this->favoritedMap = $likedMap;
    }

    /**
     * @param int $targetId
     *
     * @return boolean
     */
    public function isFavorited($targetId) {
        if (isset($this->favoritedMap[$targetId])) {
            return true;
        } else {
            return false;
        }
    }

}