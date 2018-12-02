<?php

namespace Lychee\Module\Post;

use Symfony\Component\EventDispatcher\Event;

class PostEvent extends Event {

    const CREATE = 'lychee.post.create';
    const DELETE = 'lychee.post.delete';
    const UNDELETE = 'lychee.post.undelete';
    const UPDATE = 'lychee.post.update';

    /**
     * @var int
     */
    private $postId;

    /**
     * @param int $postId
     */
    public function __construct($postId) {
        $this->postId = $postId;
    }

    /**
     * @return int
     */
    public function getPostId() {
        return $this->postId;
    }
}