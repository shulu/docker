<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 5/30/16
 * Time: 4:52 PM
 */

namespace Lychee\Bundle\AdminBundle\Twig;


class Extension extends \Twig_Extension {

    public function getFilters() {
        return array(
            new \Twig_SimpleFilter('strpad', array($this, 'strpad')),
        );
    }

    public function getName() {
        return 'lychee_admin_extension';
    }

    public function strpad($str, $padStr) {
        return str_pad($str, 2, $padStr, STR_PAD_LEFT);
    }
}