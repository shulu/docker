<?php
namespace Lychee\Module\Recommendation;

use Lsw\MemcacheBundle\Cache\MemcacheInterface;

class LastModifiedManager {

    /**
     * @var MemcacheInterface
     */
    private $memcache;

    /**
     * @param MemcacheInterface $memcache
     */
    public function __construct($memcache) {
        $this->memcache = $memcache;
    }

    public function updateLastModified($key) {
        $this->setLastModified($key, new \DateTime());
    }

    /**
     * @param string $key
     * @param \DateTime $dt
     * @param int $timeout
     */
    public function setLastModified($key, $dt, $timeout = 86400) {
        $utcTime = clone $dt;
        $utcTime->setTimezone(new \DateTimeZone('UTC'));
        $timeStr = $utcTime->format(\DateTime::W3C);
        $lmKey = 'last_modified_.'.$key;
        $this->memcache->set($lmKey, $timeStr, 0, $timeout);
//        $cache = $this->memcache->get($lmKey, $flags, $cas);
//
//        if ($cache === false) {
//            $this->memcache->set($lmKey, $timeStr, 0, $timeout);
//        } else {
//            $i = 0;
//            $done = false;
//            while (!$done && $i < 10) {
//                $done = $this->memcache->cas($lmKey, $timeStr, $flags, $timeout, $cas);
//                $i += 1;
//            }
//        }
    }

    /**
     * @param string $key
     * @param string $timezoneName
     * @return \DateTime|null
     */
    public function getLastModified($key, $timezoneName = 'UTC') {
        $dateTimeString = $this->memcache->get('last_modified_.'.$key);
        if ($dateTimeString != false) {
            $lastModifiedTime = new \DateTime($dateTimeString);
            if ($timezoneName != 'UTC') {
                $lastModifiedTime->setTimezone(new \DateTimeZone($timezoneName));
            }
            return $lastModifiedTime;
        } else {
            return null;
        }
    }

}