<?php

namespace Lychee\Module\Notification\Push;


interface Pusher {
    /**
     * @param int    $type PushEventType
     * @param int    $fromUserId
     * @param int    $toUserId
     * @param string $message
     */
    public function pushEvent($type, $fromUserId, $toUserId, $message = null);

    /**
     * @param int $targetType PushPromotionType
     * @param int $targetId
     * @param string $message
     */
    public function pushPromotion($targetType, $targetId, $message);

    /**
     * @param int $fromUserId
     * @param int $toUserId
     * @param string  $message
     * @param int|null $groupId
     */
    public function pushMessage($fromUserId, $toUserId, $message, $groupId = null);

    /**
     * @param string $message
     */
    public function pushSystem($message);
}