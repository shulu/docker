<?php
namespace Lychee\Component\GraphStorage;

interface FollowingCounter {

    const HINT_NONE = 0;
    const HINT_NO_FOLLOWER = 1;
    const HINT_NO_FOLLOWEE = 2;

    /**
     * @param int $followee
     *
     * @return int
     */
    public function countFollowers($followee);

    /**
     * @param int $follower
     *
     * @return int mixed
     */
    public function countFollowees($follower);
} 