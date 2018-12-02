<?php
namespace Lychee\Bundle\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class NotSensitiveWord extends Constraint {

    public $message = 'can not contain sensitive words.';
    public $service = 'lychee_core.validator.not_sensitive_word';

    public function validatedBy() {
        return $this->service;
    }

}