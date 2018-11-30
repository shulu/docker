<?php
namespace Lychee\Bundle\ApiBundle\Error\CodeGenerator;

class ErrorSection {
    /**
     * @var int
     */
    protected $code;

    /**
     * @var array
     */
    protected $errors = array();

    /**
     * @var string
     */
    protected $class;

    /**
     * @return int
     */
    public function getCode() {
       return $this->code;
    }

    /**
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * @return string
     */
    public function getClass() {
        return $this->class;
    }
} 