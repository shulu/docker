<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 5/27/15
 * Time: 3:55 PM
 */

namespace Lychee\Module\ContentManagement;


/**
 * Class InvalidSearchTypeException
 * @package Lychee\Module\ContentManagement
 */
class InvalidSearchTypeException extends \Exception {

    /**
     *
     */
    public function __construct() {
        parent::__construct('Invalid search type', 1);
    }
}