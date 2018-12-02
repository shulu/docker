<?php
namespace Lychee\Component\KVStorage;

class ReadThroughStorage implements Reader {

    /**
     * @var array
     */
    private $storages;

    /**
     * @var boolean
     */
    private $writeBack;

    /**
     * @param array $storages
     * @param boolean $writeBack
     */
    public function __construct($storages, $writeBack = false) {
        assert(count($storages) > 0);
        $this->storages = $storages;
        $this->writeBack = $writeBack;
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function get($key) {
        $storages = $this->storages;
        return $this->getFromStorages($key, $storages);
    }

    /**
     * @param array $keys
     *
     * @return array
     */
    public function getMulti($keys) {
        $storages = $this->storages;
        return $this->getMultiFromStorages($keys, $storages);
    }

    private function getMultiFromStorages($keys, &$storages) {
        /** @var Reader $storage */
        $storage = array_shift($storages);
        $result = $storage->getMulti($keys);
        if (count($result) === count($keys) || count($storages) === 0) {
            return $result;
        } else {
            $remainingKeys = array_diff($keys, array_keys($result));
            $remainingResult = $this->getMultiFromStorages($remainingKeys, $storages);
            if ($this->writeBack && $storage instanceof Writer) {
                /** @var Writer $storage */
                $storage->setMulti($remainingResult);
            }
            return array_merge($result, $remainingResult);
        }
    }

    private function getFromStorages($key, &$storages) {
        /** @var Reader $storage */
        $storage = array_shift($storages);
        $result = $storage->get($key);
        if ($result !== null || count($storages) === 0) {
            return $result;
        } else {
            $resultFromOthers = $this->getFromStorages($key, $storages);
            if ($resultFromOthers !== null && $this->writeBack && $storage instanceof Writer) {
                /** @var Writer $storage */
                $storage->set($key, $resultFromOthers);
            }
            return $resultFromOthers;
        }
    }
} 