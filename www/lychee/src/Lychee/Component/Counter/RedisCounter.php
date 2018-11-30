<?php
namespace Lychee\Component\Counter;

use Predis\Client;

class RedisCounter {

    /**
     * @var Client
     */
    private $redis;

    public function __construct($redis)
    {
        $this->redis = $redis;
    }

    /**
     * 加, == redis::incrby
     * @param string $key
     * @param int $num
     * @return int
     */
    public function incr($key, $num=1)
    {
        return $this->redis->incrby($key, $num);
    }

    /**
     * 减, == redis::decrby
     * @param string $key
     * @param int $num
     * @return int|mixed
     */
    public function decr($key, $num=1)
    {
        return $this->redis->decrby($key, $num);
    }

    /**
     * 基数不够减即返回false
     * @param string $key
     * @param int $num
     * @return int|mixed
     */
    public function decrMustEnough($key, $num)
    {
        $cmd = new DecrMustEnoughRedisCommand();
        $cmd->setArguments([$key, $num]);
        return $this->redis->executeCommand($cmd);
    }

    /**
     * 先加后按步长递减
     *
     * @param string $key    缓存key
     * @param int $incrBy    增量
     * @param int $decrStep  递减的步长
     *
     * @return array $ret
     *               $ret['totalAfterIncr']  累加之后的总数
     *               $ret['decrCount']       按步长递减的次数
     *               $ret['decrTotal']       减量
     *
     */
    public function incrAndDecrByStep($key, $incrBy, $decrStep)
    {
        $ret = [];
        $ret['decrCount'] = 0;
        $ret['decrTotal'] = 0;
        $ret['totalAfterIncr'] = $total = $this->incr($key, $incrBy);
        if ($total<$decrStep) {
            return $ret;
        }
        $count = intval($total/$decrStep);
        $decrNum = $count*$decrStep;
        if (false===$this->decrMustEnough($key, $decrNum)) {
            return $ret;
        }
        $ret['decrCount'] = $count;
        $ret['decrTotal'] = $decrNum;
        return $ret;
    }


    /**
     * 清零
     * @param $key
     */
    public function reset($key)
    {
        $this->redis->set($key, 0);
    }

    /**
     * @return Client
     */
    public function getRedis()
    {
        return $this->redis;
    }
}