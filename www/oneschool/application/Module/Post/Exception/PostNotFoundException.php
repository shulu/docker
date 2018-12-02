<?php
namespace Lychee\Module\Post\Exception;

class PostNotFoundException extends \Exception {
    public function __construct($message = "", $code = 0, \Exception $previous = null) {
        if (empty($message)) {
            $message = 'Post Not Found.';
        }
        parent::__construct($message, $code, $previous);
    }
} 