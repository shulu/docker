<?php
namespace Lychee\Component\GraphStorage;

class ParticalFollowingResolver implements FollowingResolver {
    private $followerMap;
    private $followeeMap;
    private $hint;

    public function __construct($followerMap, $followeeMap, $hint = self::HINT_NONE) {
        $this->followerMap = $followerMap;
        $this->followeeMap = $followeeMap;
        $this->hint = $hint;
    }

    public function isFollower($target) {
        if ($this->hint === self::HINT_FOLLOWER) {
            return true;
        }
        if ($this->hint === self::HINT_NO_FOLLOWER) {
            return false;
        }
        if (isset($this->followerMap[$target])) {
            return true;
        } else {
            return false;
        }
    }

    public function isFollowee($target) {
        if ($this->hint === self::HINT_FOLLOWEE) {
            return true;
        }
        if ($this->hint === self::HINT_NO_FOLLOWEE) {
            return false;
        }
        if (isset($this->followeeMap[$target])) {
            return true;
        } else {
            return false;
        }
    }
} 