<?php
namespace Lychee\Bundle\ApiBundle\Error;

use Lychee\Bundle\ApiBundle\Error\Error;

class CommentError {
    const CODE_CommentNotExist = 40001;
    const CODE_NotYourOwnComment = 40002;
    const CODE_CanNotReplyYourself = 40003;
    const CODE_ContentInvalid = 40004;
    const CODE_AnnotationTooLong = 40005;
    const CODE_AnnotationError = 40006;

    static public function CommentNotExist($commentId) {
        $_message = "comment with id {$commentId} not exist";
        $_display = null;
        return new Error(self::CODE_CommentNotExist, $_message, $_display);
    }

    static public function NotYourOwnComment() {
        $_message = "Not Your Own Comment";
        $_display = null;
        return new Error(self::CODE_NotYourOwnComment, $_message, $_display);
    }

    static public function CanNotReplyYourself() {
        $_message = "Can Not Reply Yourself";
        $_display = null;
        return new Error(self::CODE_CanNotReplyYourself, $_message, $_display);
    }

    static public function ContentInvalid() {
        $_message = "Content Invalid";
        $_display = null;
        return new Error(self::CODE_ContentInvalid, $_message, $_display);
    }

    static public function AnnotationTooLong($limit) {
        $_message = "annotation is too long, it should have {$limit} characters or less.";
        $_display = null;
        return new Error(self::CODE_AnnotationTooLong, $_message, $_display);
    }

    static public function AnnotationError() {
        $_message = "annotation must with json format";
        $_display = null;
        return new Error(self::CODE_AnnotationError, $_message, $_display);
    }
}