<?php

namespace Lychee\Module\Post\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;

/**
 * @group \Lychee\Module\Post\Task\SettleLatelyLikeTask
 */
class SettleLatelyLikeTaskTest extends ModuleAwareTestCase {


    public function getConnection() {
        $conn  = $this->container()->get('doctrine')->getManager()->getConnection();
        return $conn;
    }

    private function task() {
        return $this->container()->get('lychee.task.settle_post_lately_likes');
    }
    /**
     * 验证点赞数统计
     *
     * @covers ::run
     *
     */
    public function testRun() {
        $this->task()->run();

        $sql = "select * from like_post_period_count order by last_id desc limit 1";
        $lppc = $this->getConnection()
            ->executeQuery($sql)
            ->fetch(\PDO::FETCH_ASSOC);
        $postId = $lppc['post_id'];

        // 符合最近7天点赞数

        $sql = "select min(id) min_id, max(id) max_id, count(1) n  from like_post where state = 0 and post_id = ? and update_time >= ?";
        $r = $this->getConnection()
            ->executeQuery($sql , [$postId, date('Y-m-d H:i:s', strtotime('-1 weeks'))])
            ->fetch(\PDO::FETCH_ASSOC);

        $this->assertEquals($r['min_id'], $lppc['first_id']);
        $this->assertEquals($r['max_id'], $lppc['last_id']);
        $this->assertEquals($r['n'], $lppc['count']);
    }
}
 