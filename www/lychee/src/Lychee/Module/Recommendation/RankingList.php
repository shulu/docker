<?php
namespace Lychee\Module\Recommendation;

use Lychee\Component\Foundation\CursorableIterator\CustomizedCursorableIterator;
use Lychee\Component\Foundation\CursorableIterator\CursorableIterator;
use Lychee\Component\Foundation\ArrayUtility;

class RankingList {
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

            $result = $this->redis->zRevRange(
                $this->key, $cursor,
                $cursor + $step - 1 + count($exclusion),
                'WITHSCORES'
            );
            $scoresByIds = $result;

            if (count($exclusion) === 0) {
                $nextCursor = count($scoresByIds) < $step ? 0 : $cursor + $step;
                return $scoresByIds;
            } else {
                if (count($scoresByIds) <= $step) {
                    $nextCursor = 0;
                    return array_diff_key($scoresByIds, array_flip($exclusion));
                } else {
                    $exclusionMap = array_flip($exclusion);
                    $realResult = array();
                    $index = 0;
                    foreach ($scoresByIds as $id => $score) {
                        if (!isset($exclusionMap[$id])) {
                            $realResult[$id] = $score;
                            if (count($realResult) == $step) {
                                break;
                            }
                        }
                        $index += 1;
                    }
                    if (count($realResult) < $step) {
                        $nextCursor = 0;
                    } else {
                        $nextCursor = $cursor + $index + 1;
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
    public function update($scoresByIds) {
        $this->redis->multi();
        $this->redis->del($this->key);
        $command = $this->redis->createCommand('zAdd', array($this->key, $scoresByIds));
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