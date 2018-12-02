<?php
namespace Lychee\Component\GraphStorage\Redis;

use Lychee\Component\GraphStorage\FollowingCounter;
use Lychee\Component\GraphStorage\FollowingResolver;
use Lychee\Component\GraphStorage\FollowingStorage;
use Lychee\Component\GraphStorage\Exception\FollowingException;
use Lychee\Component\GraphStorage\ParticalFollowingCounter;
use Lychee\Component\GraphStorage\ParticalFollowingResolver;

class RedisFollowingStorage implements FollowingStorage {
    /**
     * @var \Predis\Client|\Redis
     */
    private $redis;

    /**
     * @var string
     */
    private $followerPrefix;

    /**
     * @var string
     */
    private $followeePrefix;

    /**
     * @param \Predis\Client|\Redis $redis
     * @param string $followerPrefix
     * @param string $followeePrefix
     */
    public function __construct($redis, $followerPrefix, $followeePrefix) {
        $this->redis = $redis;
        $this->followerPrefix = $followerPrefix;
        $this->followeePrefix = $followeePrefix;
    }

    /**
     * @param int $follower
     * @param int $followee
     * @throws FollowingException
     */
    public function follow($follower, $followee) {
        //TODO: protect the relation consistency
        $command = new Command\CreateRelationCommand();
        $command->setArguments(array($this->followerPrefix.$followee, $follower));
        $addFollowerOk = $this->redis->executeCommand($command) === 1;
        if ($addFollowerOk) {
            $command->setArguments(array($this->followeePrefix.$follower, $followee));
            $addFollowingOk = $this->redis->executeCommand($command) === 1;
            if ($addFollowingOk === true) {
                return;
            }
            $removeFollowerOk = $this->redis->zrem($this->followerPrefix.$followee, $follower) === 1;
            if ($removeFollowerOk === false) {
                //here maybe a bug!
            }
        }
        throw new FollowingException();
    }

    /**
     * @param int   $follower
     * @param array $followees
     * @throws FollowingException
     */
    public function multiFollow($follower, $followees) {
        if (empty($followees)) {
            return;
        }

        //TODO: protect the relation consistency
        $command = new Command\CreateRelationCommand();

        foreach ($followees as $followee) {
            $command->setArguments(array($this->followerPrefix.$followee, $follower));
            $this->redis->executeCommand($command);
        }

        $multiCommand = new Command\CreateMultiRelationCommand();
        $multiCommand->setArguments(array($this->followeePrefix.$follower, $followees));
        $okFollowees = $this->redis->executeCommand($multiCommand);

        if (count($okFollowees) === count($followees)) {
            return;
        }
        $failFollowees = array_diff($followees, $okFollowees);
        foreach ($failFollowees as $failFollowee) {
            $this->redis->zrem($this->followerPrefix.$failFollowee, $follower);
        }
    }

    /**
     * @param int $follower
     * @param int $followee
     */
    public function unfollow($follower, $followee) {
        //TODO: protect the relation consistency
        $this->redis->zRem($this->followeePrefix.$follower, $followee);
        $this->redis->zRem($this->followerPrefix.$followee, $follower);
    }

    /**
     * @param int   $follower
     * @param array $followees
     */
    public function multiUnfollow($follower, $followees) {
        if (empty($followees)) {
            return;
        }
        //TODO: protect the relation consistency
        $arguments = $followees;
        array_unshift($arguments, $this->followeePrefix.$follower);
        $removeFolloweeCommand = $this->redis->createCommand('zrem', $arguments);
        $this->redis->executeCommand($removeFolloweeCommand);

        foreach ($followees as $followee) {
            $this->redis->zRem($this->followerPrefix.$followee, $follower);
        }
    }

    /**
     * @param int $followee
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     *
     * @return array
     */
    public function fetchFollowers($followee, $cursor, $count, &$nextCursor) {
        $command = new Command\IterateRelationCommand();
        $command->setArguments(array($this->followerPrefix.$followee, $cursor, $count));
        $result = $this->redis->executeCommand($command);
        $nextCursor = $result[0];
        return $result[1];
    }

    /**
     * @param int $follower
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     *
     * @return array
     */
    public function fetchFollowees($follower, $cursor, $count, &$nextCursor) {
        $command = new Command\IterateRelationCommand();
        $command->setArguments(array($this->followeePrefix.$follower, $cursor, $count));
        $result = $this->redis->executeCommand($command);
        $nextCursor = $result[0];
        return $result[1];
    }

    /**
     * @param int $followee
     *
     * @return int
     */
    public function countFollowers($followee) {
        return $this->redis->zCard($this->followerPrefix.$followee);
    }

    /**
     * @param int $follower
     *
     * @return int
     */
    public function countFollowees($follower) {
        return $this->redis->zCard($this->followeePrefix.$follower);
    }

    /**
     * @param int $source
     * @param int $target
     *
     * @return boolean
     */
    public function isFollowing($source, $target) {
        return $this->redis->zScore($this->followeePrefix.$source, $target) > 0;
    }

    /**
     * @param int   $source
     * @param array $targets
     * @param int   $hint
     *
     * @return FollowingResolver
     */
    public function buildResolver($source, $targets, $hint = FollowingResolver::HINT_NONE) {
        if ($source === 0 || empty($targets)) {
            return new ParticalFollowingResolver(array(), array());
        }

        $command = new Command\ResolveRelationCommand();
        if ($hint !== FollowingResolver::HINT_FOLLOWER && $hint !== FollowingResolver::HINT_NO_FOLLOWER) {
            $command->setArguments(array($this->followerPrefix.$source, $targets));
            $followers = $this->redis->executeCommand($command);
        }
        if ($hint !== FollowingResolver::HINT_FOLLOWEE && $hint !== FollowingResolver::HINT_NO_FOLLOWEE) {
            $command->setArguments(array($this->followeePrefix.$source, $targets));
            $followees = $this->redis->executeCommand($command);
        }
        return new ParticalFollowingResolver(
            array_fill_keys(isset($followers) ? $followers : array(), true),
            array_fill_keys(isset($followees) ? $followees : array(), true),
            $hint
        );
    }

    /**
     * @param array $sources
     * @param int $hint
     *
     * @return FollowingCounter
     */
    public function buildCounter($sources, $hint = FollowingCounter::HINT_NONE) {
        if (empty($sources)) {
            return new ParticalFollowingCounter(array(), array());
        }

        $command = new Command\CountRelationCommand();
        if ($hint !== FollowingCounter::HINT_NO_FOLLOWER) {
            $command->setArguments(array($this->followerPrefix, $sources));
            $followersCountMap = $this->redis->executeCommand($command);
        }
        if ($hint !== FollowingCounter::HINT_NO_FOLLOWEE) {
            $command->setArguments(array($this->followeePrefix, $sources));
            $followeesCountMap = $this->redis->executeCommand($command);
        }

        return new ParticalFollowingCounter(
            isset($followersCountMap) ? $followersCountMap : array(),
            isset($followeesCountMap) ? $followeesCountMap : array()
        );
    }

} 