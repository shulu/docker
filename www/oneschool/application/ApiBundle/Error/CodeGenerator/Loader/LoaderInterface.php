<?php

namespace Lychee\Bundle\ApiBundle\Error\CodeGenerator\Loader;

interface LoaderInterface {
    /**
     * @param string $rootPath
     *
     * @return \Iterator
     */
    public function load($rootPath);
} 