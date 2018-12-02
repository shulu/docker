<?php
namespace Lychee\Component\GraphStorage;

class ParticalFollowingCounter implements FollowingCounter {
    /**
     * @var array
     */
    private $followerCountMap;

    /**
     * @var array
     */
    private $followeeCountMap;

    /**
     * @var int
     */
    private $hint;

    /**
     * @param array $followerCountMap
     * @param array $followeeCountMap
     * @param int $hint
     */
    public function __construct($followerCountMap, $followeeCountMap, $hint = FollowingCounter::HINT_NONE) {
        $this->followerCountMap = $followerCountMap;
        $this->followeeCountMap = $followeeCountMap;
        $this->hint = $hint;
    }

    /**
     * @param int $source
     *
     * @return int
     */
    public function countFollowers($source) {
        if (isset($this->followerCountMap[$source])) {
            return $this->followerCountMap[$source];
        } else {
            return 0;
        }
    }

    /**
     * @param int $source
     *
     * @return int mixed
     */
    public function countFollowees($source) {
        if (isset($this->followeeCountMap[$source])) {
            return $this->followeeCountMap[$source];
        } else {
            return 0;
        }
    }
} 