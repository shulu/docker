<?php
namespace Lychee\Module\Like;

use Symfony\Component\EventDispatcher\Event;

class LikeEvent extends Event {

    const LIKE = 'lychee.like.like';
    const UNLIKE = 'lychee.like.unlike';

    /**
     * @var int
     */
    private $type;

    /**
     * @var int
     */
    private $likerId;

    /**
     * @var int
     */
    private $targetId;

    /**
     * @var bool
     */
    private $likedBefore;

    /**
     * @param int $type
     * @param int $likerId
     * @param int $targetId
     * @param bool $likedBefore
     */
    public function __construct($type, $likerId, $targetId, $likedBefore) {
        $this->type = $type;
        $this->likerId = $likerId;
        $this->targetId = $targetId;
        $this->likedBefore = $likedBefore;
    }

    /**
     * @return int
     */
    public function getLikeType() {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getLikerId() {
        return $this->likerId;
    }

    /**
     * @return int
     */
    public function getTargetId() {
        return $this->targetId;
    }

    /**
     * @return bool
     */
    public function isLikedBefore() {
        return $this->likedBefore;
    }
}