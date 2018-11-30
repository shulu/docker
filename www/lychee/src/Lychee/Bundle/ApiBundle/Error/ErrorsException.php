<?php
namespace Lychee\Bundle\ApiBundle\Error;

class ErrorsException extends \Exception {

    /**
     * @var array
     */
    protected $errors;

    public function __construct($errors) {
        if (is_array($errors)) {
            $this->errors = $errors;
        } else {
            $this->errors = array($errors);
        }

        parent::__construct("many errors");
    }

    /**
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }
} 