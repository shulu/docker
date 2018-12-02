<?php
namespace Lychee\Bundle\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class Password extends ByteLengthPattern {
    const PATTERN = '/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]+$/';
    public $message = '{{ minLength }}-{{ maxLength }}个半角字符，由字母、数字组成';
    public $minLength = 8;
    public $maxLength = 16;
}