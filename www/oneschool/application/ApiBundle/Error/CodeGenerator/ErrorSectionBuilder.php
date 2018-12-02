<?php
namespace Lychee\Bundle\ApiBundle\Error\CodeGenerator;

class ErrorSectionBuilder extends ErrorSection {
    public function setCode($code) {
        $this->code = $code;
    }

    public function addError($error) {
        $this->errors[] = $error;
    }

    public function setClass($class) {
        $this->class = $class;
    }
} 