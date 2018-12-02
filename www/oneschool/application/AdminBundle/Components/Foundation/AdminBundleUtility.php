<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14-8-26
 * Time: 下午2:38
 */

namespace Lychee\Bundle\AdminBundle\Components\Foundation;


class AdminBundleUtility {
    static public function getRoleName($controller) {
        $classInfo = new \ReflectionClass($controller);
        $controllerName = str_replace('Controller', '', $classInfo->getShortName());

        return 'ROLE_ADMIN_' . strtoupper(self::setCamelCaseToUnderline($controllerName));
    }

    static public function setCamelCaseToUnderline($camelCaseName) {
        return preg_replace_callback("/[A-Z]/", function ($matches) {
            return '_' . strtolower($matches[0]);
        }, lcfirst($camelCaseName));
    }
} 