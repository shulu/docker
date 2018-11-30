<?php

namespace Lychee\Module\UGSV\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;

/**
 * @group \Lychee\Module\UGSV\Task\SettleHotVideosTask
 */
class SettleHotVideosTaskTest extends ModuleAwareTestCase {

    /**
     * @return \Predis\Client|\Redis
     */
    private function redis() {
        return $this->container()->get('snc_redis.recommendation_video');
    }
    private function task() {
        return $this->container()->get('lychee.task.settle_hot_videos');
    }
    /**
     * 验证不存在重复数据
     *
     * @covers ::run
     *
     */
    public function testRunUnique() {
        $redis = $this->redis();

        $task= $this->task();
        $task->run();

        $r = $redis->lrange($task->getListKey(), 0, -1);
        $this->assertEquals(count($r), count(array_unique($r)));
    }

    /**
     * 验证列表视频长度长度
     *
     * @covers ::run
     *
     */
    public function testRunLen() {
        $redis = $this->redis();

        $task= $this->task();
        $task->run();

        $r = $redis->llen($task->getListKey());
        $this->assertLessThanOrEqual(450, $r);
    }

}
 