<?php
namespace Lychee\Module\Comment;

use Symfony\Component\EventDispatcher\Event;

class CommentEvent extends Event {

    const CREATE = 'lychee.comment.create';
    const DELETE = 'lychee.comment.delete';
    const UNDELETE = 'lychee.comment.undelete';

    private $commentId;

    public function __construct($commentId) {
        $this->commentId = $commentId;
    }

    public function getCommentId() {
        return $this->commentId;
    }

} 