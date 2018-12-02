<?php
namespace Lychee\Bundle\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class ByteLengthPattern extends Constraint {
    const PATTERN = '/^.*$/';
    public $message = '{{ minLength }}-{{ maxLength }}个字符';
    public $minLength = 0;
    public $maxLength = 100;
} 