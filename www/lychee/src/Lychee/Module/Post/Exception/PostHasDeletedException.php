<?php
namespace Lychee\Module\Post\Exception;

class PostHasDeletedException extends \Exception {
    public function __construct($message = "", $code = 0, \Exception $previous = null) {
        if (empty($message)) {
            $message = 'Post Has Deleted.';
        }
        parent::__construct($message, $code, $previous);
    }
} 