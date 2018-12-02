<?php
namespace Lychee\Module\Recommendation;

use Lychee\Component\Foundation\CursorableIterator\AbstractCursorableIterator;
use Lychee\Component\Foundation\CursorableIterator\CursorableIterator;
use Lsw\MemcacheBundle\Cache\MemcacheInterface;

class MemcacheCursorableIterator extends AbstractCursorableIterator {

    /**
     * @var MemcacheInterface
     */
    private $memcache;

    /**
     * @var string
     */
    private $key;

    /**
     * @var array
     */
    private $cacheResult;

    /**
     * @var CursorableIterator
     */
    private $backCursorableIterator;

    /**
     * @param MemcacheInterface $memcache
     * @param string $key
     * @param CursorableIterator $backCursorableIterator
     */
    public function __construct($memcache, $key, $backCursorableIterator) {
        $this->memcache = $memcache;
        $this->key = $key;
        $this->backCursorableIterator = $backCursorableIterator;
    }

    /**
     * @param mixed $cursor
     * @param int   $step
     * @param mixed $nextCursor
     *
     * @return mixed
     */
    protected function getResult($cursor, $step, &$nextCursor) {
        if ($step <= 0) {
            return array();
        }

        if ($this->cacheResult === null) {
            $this->cacheResult = $this->memcache->get($this->key);
        }

        if ($cursor === 0) {
            if ($this->cacheResult != false && count($this->cacheResult['result']) <= $step) {
                $nextCursor = $this->cacheResult['nextCursor'];
                return $this->cacheResult['result'];
            } else {
                $this->backCursorableIterator->setCursor($cursor);
                $this->backCursorableIterator->setStep($step);
                $nextCursor = $this->backCursorableIterator->getNextCursor();
                $result = $this->backCursorableIterator->current();

                $this->cacheResult = array(
                    'result' => $result,
                    'nextCursor' => $nextCursor
                );
                $this->memcache->set($this->key, $this->cacheResult, 0, 86400);
                return $result;
            }
        } else {
            $this->backCursorableIterator->setCursor($cursor);
            $this->backCursorableIterator->setStep($step);
            $nextCursor = $this->backCursorableIterator->getNextCursor();
            $result = $this->backCursorableIterator->current();

            if ($this->cacheResult != false &&
                $this->cacheResult['nextCursor'] == $cursor
            ) {
                $oldResult = $this->cacheResult['result'];
                $this->cacheResult['result'] = array_merge($oldResult, $result);
                $this->memcache->set($this->key, $this->cacheResult, 0, 86400);
            }

            return $result;
        }
    }

} 