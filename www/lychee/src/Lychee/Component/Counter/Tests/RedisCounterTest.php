<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 3/12/15
 * Time: 3:50 PM
 */

namespace Lychee\Component\Counter\Tests;


use Lychee\Component\Test\ModuleAwareTestCase;

/**
 * @coversDefaultClass \Lychee\Component\Counter\RedisCounter
 */
class RedisCounterTest extends ModuleAwareTestCase {


    public function getCounter()
    {
        return  $this->container()->get('lychee.component.counter.robot');
    }

    /**
     * 累计
     *
     * @covers ::incr()
     *
     */
    public function testIncr()
    {
        $counter = $this->getCounter();
        $r = $counter->incr('aaa', 1);
        $this->assertGreaterThanOrEqual(1, $r);
    }

    /**
     * 超扣
     *
     * @covers ::decrMustEnough()
     *
     */
    public function testDecrNotEnough()
    {
        $counter = $this->getCounter();
        $key = 'bbb';
        $redis = $counter->getRedis();

        $init = 2;

        $redis->set($key, $init);
        $r = $counter->decrMustEnough($key, 10);
        $this->assertFalse($r);

        $r = $redis->get($key);
        $this->assertEquals($init, $r);
    }

    /**
     * 临界值减运算
     *
     * @covers ::decrMustEnough()
     *
     */
    public function testDecrJustEnough()
    {
        $counter = $this->getCounter();
        $key = 'ccc';
        $redis = $counter->getRedis();
        $init = 2;
        $redis->set($key, $init);
        $r = $counter->decrMustEnough($key, $init);
        $this->assertTrue(0===$r);
        $r = $redis->get($key);
        $this->assertTrue(is_numeric($r));
        $r = intval($r);
        $this->assertTrue(0===$r);
    }

    /**
     * 正常减
     *
     * @covers ::decrMustEnough()
     *
     */
    public function testDecrMoreEnough()
    {
        $counter = $this->getCounter();
        $key = 'bbb';
        $redis = $counter->getRedis();

        $init = 21;
        $decrNum = 10;

        $redis->set($key, $init);
        $r = $counter->decrMustEnough($key, $decrNum);
        $this->assertTrue(is_numeric($r));

        $r = $redis->get($key);
        $this->assertEquals($init-$decrNum, $r);
    }


    /**
     * 先加后按步长递减
     *
     * @covers ::incrAndDecrByStep()
     *
     */
    public function testIncrAndDecrByStep()
    {
        $counter = $this->getCounter();
        $key = 'ccc'.time();
        $redis = $counter->getRedis();
        $init = 21;
        $redis->set($key, $init);

        $incrBy = 1;
        $decrStep = 10;

        $r = $counter->incrAndDecrByStep($key, $incrBy, $decrStep);
        $decrCount = intval(($init+$incrBy)/$decrStep);
        $decrTotal = $decrCount*$decrStep;
        $this->assertEquals($init+$incrBy, $r['totalAfterIncr']);
        $this->assertEquals($decrCount, $r['decrCount']);
        $this->assertEquals($decrTotal, $r['decrTotal']);

    }

}