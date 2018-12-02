<?php
namespace Lychee\Bundle\ApiBundle\Error\CodeGenerator;

class Error {
    /**
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $code;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var string
     */
    protected $displayMessage;

    public function __construct($name, $code, $message, $displayMessage = null) {
        $this->name = $name;
        $this->code = $code;
        $this->message = $message;
        $this->displayMessage = $displayMessage;
    }

    /**
     * @return int
     */
    public function getCode() {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return null|string
     */
    public function getDisplayMessage() {
        return $this->displayMessage;
    }

}