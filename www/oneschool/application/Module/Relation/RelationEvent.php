<?php
namespace Lychee\Module\Relation;

use Symfony\Component\EventDispatcher\Event;

class RelationEvent extends Event {

    const FOLLOW = 'lychee.relation.follow';
    const UNFOLLOW = 'lychee.relation.unfollow';

    /**
     * @var int
     */
    private $followerId;

    /**
     * @var array
     */
    private $followeeIds;

    /**
     * @param int $followerId
     * @param array $followeeIds
     */
    public function __construct($followerId, $followeeIds) {
        $this->followerId = $followerId;
        $this->followeeIds = $followeeIds;
    }

    /**
     * @return array
     */
    public function getFolloweeIds() {
        return $this->followeeIds;
    }

    /**
     * @return int
     */
    public function getFollowerId() {
        return $this->followerId;
    }
} 