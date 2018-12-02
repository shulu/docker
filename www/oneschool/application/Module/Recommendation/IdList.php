<?php
namespace Lychee\Module\Recommendation;

use Lychee\Component\Foundation\CursorableIterator\CustomizedCursorableIterator;
use Lychee\Component\Foundation\CursorableIterator\CursorableIterator;
use Lychee\Component\Foundation\ArrayUtility;

class IdList {

    /**
     * @var \Predis\Client|\Redis
     */
    private $redis;

    /**
     * @var string
     */
    private $key;

    /**
     * @param \Predis\Client|\Redis $redis
     * @param string $key
     */
    public function __construct($redis, $key) {
        $this->redis = $redis;
        $this->key = $key;
    }

    /**
     * @param array $exclusion
     * @return CursorableIterator
     */
    public function getIterator($exclusion = array()) {
        $function = function ($cursor, $step, &$nextCursor) use ($exclusion) {
            if ($step < 1) {
                return array();
            }

            $result = $this->redis->zRange($this->key, $cursor, $cursor + $step - 1 + count($exclusion));

            if (count($exclusion) === 0) {
                $nextCursor = count($result) < $step ? 0 : $cursor + $step;
                return $result;
            } else {
                if (count($result) <= $step) {
                    $nextCursor = 0;
                    return ArrayUtility::diffValue($result, $exclusion);
                } else {
                    $exclusionMap = array_flip($exclusion);
                    $realResult = array();
                    foreach ($result as $i => $item) {
                        if (!isset($exclusionMap[$item])) {
                            $realResult[] = $item;
                            if (count($realResult) == $step) {
                                break;
                            }
                        }
                    }
                    if (count($realResult) < $step) {
                        $nextCursor = 0;
                    } else {
                        $nextCursor = $cursor + $step + 1;
                    }
                    return $realResult;
                }
            }
        };

        return new CustomizedCursorableIterator($function);
    }

    /**
     * @param array $ids
     */
    public function update($ids) {
        $this->redis->multi();
        $this->redis->del($this->key);

        $index = 1;
        $idsWithIndex = array();
        foreach ($ids as $id) {
            $idsWithIndex[$id] = $index;
            $index += 1;
        }
        $command = $this->redis->createCommand('zAdd', array($this->key, $idsWithIndex));
        $this->redis->executeCommand($command);
        $this->redis->exec();
    }

    /**
     * @param array $ids
     */
    public function removeIds($ids) {
        $command = $this->redis->createCommand('zRem', array($this->key, $ids));
        $this->redis->executeCommand($command);
    }

    public function clear() {
        $this->redis->del($this->key);
    }
}