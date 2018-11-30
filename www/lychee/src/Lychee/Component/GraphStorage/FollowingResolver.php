<?php
namespace Lychee\Component\GraphStorage;

interface FollowingResolver {
    const HINT_NONE = 0;
    const HINT_FOLLOWEE = 1;
    const HINT_NO_FOLLOWEE = 2;
    const HINT_FOLLOWER = 3;
    const HINT_NO_FOLLOWER = 4;

    /**
     * @param int $target
     *
     * @return boolean
     */
    public function isFollowee($target);

    /**
     * @param int $target
     *
     * @return boolean
     */
    public function isFollower($target);
} 