<?php
namespace Lychee\Bundle\CoreBundle\Validator\Constraints;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class TopicName extends ByteLengthPattern {
    const PATTERN = '/^[^\p{C}\p{Z}@#\-\/\*]+$/u';
    public $message = '{{ minLength }}-{{ maxLength }}个字节，不能含控制字符';
    public $minLength = 2;
    public $maxLength = 60;
}