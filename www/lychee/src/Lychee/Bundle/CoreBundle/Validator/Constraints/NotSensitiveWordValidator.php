<?php
namespace Lychee\Bundle\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Lychee\Component\Security\SensitiveWordChecker\SensitiveWordChecker;
use Lychee\Bundle\CoreBundle\Validator\Constraints\NotSensitiveWord;

class NotSensitiveWordValidator extends ConstraintValidator {

    /**
     * @var SensitiveWordChecker
     */
    private $sensitiveWordChecker;

    /**
     * @param SensitiveWordChecker $sensitiveWordChecker
     */
    public function __construct($sensitiveWordChecker) {
        $this->sensitiveWordChecker = $sensitiveWordChecker;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param mixed      $value The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     *
     * @api
     */
    public function validate($value, Constraint $constraint) {
        /** @var NotSensitiveWord $constraint */
        if ($this->sensitiveWordChecker->containSensitiveWords($value) === false) {
            return;
        }

        $this->context->addViolation($constraint->message);
    }
}