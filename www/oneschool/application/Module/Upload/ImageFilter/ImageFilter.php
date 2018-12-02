<?php

namespace Lychee\Module\Upload\ImageFilter;


interface ImageFilter {
    /**
     * @param string $path
     * @param string $paramString
     *
     * @return resource
     */
    public function apply($path, $paramString);
}