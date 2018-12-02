<?php
namespace Lychee\Module\Favorite;

interface FavoriteResolver {
    /**
     * @param int $targetId
     *
     * @return boolean
     */
    public function isFavorited($targetId);
}