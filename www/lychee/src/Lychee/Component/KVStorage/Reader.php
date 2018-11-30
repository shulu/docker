<?php

namespace Lychee\Component\KVStorage;

interface Reader {
    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function get($key);

    /**
     * @param array $keys
     *
     * @return array 返回形如[key1: value1, key2: value2, key3: value3]格式的数据，注意是一个关联数组
     */
    public function getMulti($keys);
} 