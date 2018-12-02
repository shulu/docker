<?php
namespace Lychee\Bundle\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ReservedWordValidator extends ConstraintValidator {

    /**
     * Checks if the passed value is valid.
     *
     * @param mixed      $value The value that should be validated
     * @param ReservedWord $constraint The constraint for the validation
     *
     * @api
     */
    public function validate($value, Constraint $constraint) {
        $quotedWords = array_map(function($word){return preg_quote($word, '/');}, $constraint->words);
        $pattern = '/'.implode('|', $quotedWords).'/';
        if ($constraint->caseSensitive == false) {
            $pattern .= 'i';
        }
        if (preg_match($pattern, $value) == false) {
            return;
        }

        $this->context->addViolation($constraint->message);
    }
}