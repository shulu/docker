<?php
namespace Lychee\Bundle\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ByteLengthPatternValidator extends ConstraintValidator {
    public function validate($value, Constraint $constraint) {
        /** @var ByteLengthPattern $constraint */
        if (is_string($value)) {
            $byteLength = mb_strlen($value, 'latin1');
            if ($constraint->minLength <= $byteLength && $byteLength <= $constraint->maxLength) {
                if (preg_match($constraint::PATTERN, $value) === 1) {
                    return;
                }
            }
        }

        $this->context->addViolation($constraint->message, array(
            '{{ value }}' => $value,
            '{{ minLength }}' => $constraint->minLength,
            '{{ maxLength }}' => $constraint->maxLength,
        ));
    }
} 