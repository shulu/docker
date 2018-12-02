<?php
namespace Lychee\Module\Topic\Following;

use Lychee\Module\Topic\Entity\TopicUserFollowing;

class FollowingResolver {

    private $statesByTopicIds;

    public function __construct($statesByTopicIds) {
        $this->statesByTopicIds = $statesByTopicIds;
    }

    public function isFollowing($topicId) {
        if (isset($this->statesByTopicIds[$topicId])) {
            return $this->statesByTopicIds[$topicId] > TopicUserFollowing::STATE_DELETED;
        } else {
            return false;
        }
    }

    public function isFavorite($topicId) {
        if (isset($this->statesByTopicIds[$topicId])) {
            return $this->statesByTopicIds[$topicId] == TopicUserFollowing::STATE_FAVORITE;
        } else {
            return false;
        }
    }
}