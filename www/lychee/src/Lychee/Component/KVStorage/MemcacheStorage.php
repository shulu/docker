<?php
namespace Lychee\Component\KVStorage;

use Lsw\MemcacheBundle\Cache\MemcacheInterface;

class MemcacheStorage implements Reader, Writer {
    /**
     * @var MemcacheInterface
     */
    private $memcache;

    /**
     * @var string
     */
    private $keyPrefix;

    /**
     * @var int
     */
    private $ttl;

    /**
     * @param MemcacheInterface $memcache
     * @param string $keyPrefix
     * @param int $ttl
     */
    public function __construct($memcache, $keyPrefix, $ttl = 0) {
        $this->memcache = $memcache;
        $this->keyPrefix = $keyPrefix;
        $this->ttl = 0;
    }

    public function get($key) {
        $result = $this->memcache->get($this->keyPrefix . $key);
        if ($result === false) {
            return null;
        } else {
            return $result;
        }
    }

    public function getMulti($keys) {
        $prefixedKeys = array_map(function($key){
            return $this->keyPrefix . $key;
        }, $keys);
        $result = $this->memcache->get($prefixedKeys);
        $unprefixedKeysAndValues = array();
        $prefixLength = strlen($this->keyPrefix);
        foreach ($result as $key => $value) {
            $unprefixedKey = substr($key, $prefixLength);
            $unprefixedKeysAndValues[$unprefixedKey] = $value;
        }
        return $unprefixedKeysAndValues;
    }

    public function set($key, $value) {
        $this->memcache->set($this->keyPrefix . $key, $value, MEMCACHE_COMPRESSED , $this->ttl);
    }

    public function setMulti($keysAndValues) {
        foreach ($keysAndValues as $key => $value) {
            $this->memcache->set($this->keyPrefix . $key, $value, MEMCACHE_COMPRESSED, $this->ttl);
        }
    }

    /**
     * @param string $key
     */
    public function delete($key) {
        $this->memcache->delete($this->keyPrefix . $key);
    }

    /**
     * @param array $keys
     */
    public function deleteMulti($keys) {
        foreach ($keys as $key) {
            $this->memcache->delete($this->keyPrefix . $key);
        }
    }


} 