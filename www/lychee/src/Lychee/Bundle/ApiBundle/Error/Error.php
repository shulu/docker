<?php
namespace Lychee\Bundle\ApiBundle\Error;

class Error {
    /**
     * @var int
     */
    private $code;

    /**
     * @var string
     */
    private $message;

    /**
     * @var string|null
     */
    private $displayMessage;

    /**
     * @var mixed
     */
    private $extra;

    /**
     * @param int $code
     * @param string $message
     * @param string|null $displayMessage
     */
    public function __construct($code, $message, $displayMessage) {
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
     * @return string|null
     */
    public function getDisplayMessage() {
        return $this->displayMessage;
    }

    /**
     * @return mixed
     */
    public function getExtra() {
        return $this->extra;
    }

    /**
     * @param $extra
     * @return Error
     */
    public function setExtra($extra) {
        $this->extra = $extra;
        return $this;
    }
}