<?php
namespace Lychee\Component\KVStorage;

interface Writer {
    /**
     * @param string $key
     * @param string $value
     */
    public function set($key, $value);

    /**
     * @param array $keysAndValues
     */
    public function setMulti($keysAndValues);

    /**
     * @param string $key
     */
    public function delete($key);

    /**
     * @param array $keys
     */
    public function deleteMulti($keys);
} 