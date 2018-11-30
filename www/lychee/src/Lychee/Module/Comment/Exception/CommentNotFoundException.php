<?php
namespace Lychee\Module\Comment\Exception;

class CommentNotFoundException extends \Exception {
    public function __construct($message = "", $code = 0, \Exception $previous = null) {
        if (empty($message)) {
            $message = 'Post Not Found.';
        }
        parent::__construct($message, $code, $previous);
    }
} 