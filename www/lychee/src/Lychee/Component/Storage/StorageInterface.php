<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 3/12/15
 * Time: 1:59 PM
 */

namespace Lychee\Component\Storage;

/**
 * Interface StorageInterface
 * @package Lychee\Component\Storage
 */
interface StorageInterface {


    /**
     * @param $file string
     * @return boolean
     */
    public function put($file);

    /**
     * @param $key string
     * @return boolean
     */
    public function delete($key);
}