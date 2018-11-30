<?php
namespace Lychee\Module\Notification\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="notification_counting")
 */
class NotificationCounting {
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="user_id", type="bigint")
     */
    public $userId;

    /**
     * @var int
     *
     * @ORM\Column(name="likes_unread", type="integer", options={"default": "0"})
     */
    public $likesUnread;

    /**
     * @var int
     *
     * @ORM\Column(name="events_unread", type="integer", options={"default": "0"})
     */
    public $eventsUnread;

    /**
     * @var int
     *
     * @ORM\Column(name="comments_unread", type="integer", options={"default": "0"})
     */
    public $commentsUnread;

    /**
     * @var int
     *
     * @ORM\Column(name="topics_unread", type="integer", options={"default": "0"})
     */
    public $topicsUnread;

    /**
     * @var int
     *
     * @ORM\Column(name="mentions_unread", type="integer", options={"default": "0"})
     */
    public $mentionsUnread;

    /**
     * @var int
     *
     * @ORM\Column(name="announcements_unread", type="integer", options={"default": "0"})
     */
    public $announcementsUnread;

    /**
     * @var int
     *
     * @ORM\Column(name="no_topic_unread", type="integer", options={"default": "0"})
     */
    public $noTopicUnread;

    /**
     * @var int
     *
     * @ORM\Column(name="official_cursor", type="bigint", options={"default": "0"})
     */
    public $officialCursor;
} 