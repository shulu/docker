<?php

namespace Lychee\Component\GraphStorage;

interface FollowingStorage {
    /**
     * @param int $follower
     * @param int $followee
     */
    public function follow($follower, $followee);

    /**
     * @param int $follower
     * @param array $followees
     */
    public function multiFollow($follower, $followees);

    /**
     * @param int $follower
     * @param int $followee
     */
    public function unfollow($follower, $followee);

    /**
     * @param int $follower
     * @param array $followees
     */
    public function multiUnfollow($follower, $followees);

    /**
     * @param int $followee
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     *
     * @return array
     */
    public function fetchFollowers($followee, $cursor, $count, &$nextCursor);

    /**
     * @param int $follower
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     *
     * @return array
     */
    public function fetchFollowees($follower, $cursor, $count, &$nextCursor);

    /**
     * @param int $followee
     *
     * @return int
     */
    public function countFollowers($followee);

    /**
     * @param int $follower
     *
     * @return int
     */
    public function countFollowees($follower);

    /**
     * @param int $source
     * @param int $target
     *
     * @return boolean
     */
    public function isFollowing($source, $target);

    /**
     * @param int $source
     * @param array $targets
     * @param int $hint
     *
     * @return FollowingResolver
     */
    public function buildResolver($source, $targets, $hint = FollowingResolver::HINT_NONE);

    /**
     * @param array $sources
     * @param int $hint
     *
     * @return FollowingCounter
     */
    public function buildCounter($sources, $hint = FollowingCounter::HINT_NONE);
}