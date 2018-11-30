<?php

namespace Lychee\Module\Topic;


use Symfony\Component\EventDispatcher\Event;

class TopicEvent extends Event {
    const CREATE = 'lychee.topic.create';
    const UPDATE = 'lychee.topic.update';
    const DELETE = 'lychee.topic.delete';
    const HIDE = 'lychee.topic.hide';
    const UNHIDE = 'lychee.topic.unhide';

    /**
     * @var int
     */
    private $topicId;

    /**
     * @param int $topicId
     */
    public function __construct($topicId) {
        $this->topicId = $topicId;
    }

    /**
     * @return int
     */
    public function getTopicId() {
        return $this->topicId;
    }
}