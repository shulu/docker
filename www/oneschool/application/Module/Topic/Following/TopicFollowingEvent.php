<?php
namespace Lychee\Module\Topic\Following;

use Symfony\Component\EventDispatcher\Event;

class TopicFollowingEvent extends Event {
    const FOLLOW = 'lychee.topic_following.follow';
    const UNFOLLOW = 'lychee.topic_following.unfollow';

    private $topicId;
    private $userId;
    private $followedBefore;

    /**
     * @param int $topicId
     * @param int $followerId
     * @param bool $followedBefore
     */
    public function __construct($topicId, $userId, $followedBefore) {
        $this->topicId = $topicId;
        $this->userId = $userId;
        $this->followedBefore = $followedBefore;
    }

    /**
     * @return int
     */
    public function getTopicId() {
        return $this->topicId;
    }

    /**
     * @return int
     */
    public function getUserId() {
        return $this->userId;
    }

    /**
     * @return bool
     */
    public function getFollowedBefore() {
        return $this->followedBefore;
    }
}