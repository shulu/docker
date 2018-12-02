<?php
namespace Lychee\Bundle\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class ReservedWord extends Constraint {
    public $message = '官方字已被官方占领~';
    public $words = ['次元社', '次元娘', 'MIKO酱'];
    public $caseSensitive = false;
}