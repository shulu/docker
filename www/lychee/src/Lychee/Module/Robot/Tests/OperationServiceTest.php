<?php

namespace Lychee\Module\Robot\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Like\Entity\RobotLikePostTask;

/**
 * @group \Lychee\Module\Robot\OperationService
 */
class OperationServiceTest extends ModuleAwareTestCase {

    private function getService()
    {
        return $this->container()->get('lychee.module.robot.like_post');
    }

    private function getLikeService()
    {
        return $this->container()->get('lychee.module.like');
    }

    private function getPostService()
    {
        return $this->container()->get('lychee.module.post');
    }

    private function getDBConnection()
    {
        return $this->container()->get('doctrine')->getManager()->getConnection();
    }

    /**
     * @covers ::inviteWorkers
     */
    public function testInviteWorkers()
    {
        $this->getService()->inviteWorkers();

        $sql = 'select count(1) n from robot_post_liker';
        $stm = $this->getDBConnection()->executeQuery($sql);
        $count = $stm->fetch(\PDO::FETCH_ASSOC);
        $actionCount = $count['n'];

        $this->assertGreaterThan(0, $actionCount);

        $sql = 'select count(1) n from robot';
        $stm = $this->getDBConnection()->executeQuery($sql);
        $count = $stm->fetch(\PDO::FETCH_ASSOC);
        $count = $count['n'];

        $this->assertEquals($actionCount, $count);
    }

    /**
     * @covers ::fireWorkers
     */
    public function testFireWorkers()
    {
        $this->getService()->fireWorkers();

        $sql = 'select count(1) n from robot_post_liker';
        $stm = $this->getDBConnection()->executeQuery($sql);
        $count = $stm->fetch(\PDO::FETCH_ASSOC);
        $actionCount = $count['n'];

        $this->assertGreaterThan(0, $actionCount);

        $sql = 'select count(1) n from robot';
        $stm = $this->getDBConnection()->executeQuery($sql);
        $count = $stm->fetch(\PDO::FETCH_ASSOC);
        $count = $count['n'];

        $this->assertEquals($actionCount, $count);
    }

    /**
     * @covers ::dispatchTask
     */
    public function testDispatchTask()
    {
        $task = new RobotLikePostTask();
        $task->postId = 125357892431873;
        $task->total = 1;
        $this->getService()->dispatchTask($task);

        $sql = 'select 1 from robot_like_post_task where id ='.$task->id;
        $r = $this->getDBConnection()
            ->executeQuery($sql)
            ->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotEmpty($r);
    }

    /**
     * @covers ::processWaitingTasks
     */
    public function testProcessWaitingTasks()
    {
        // 新增任务
        $service = $this->getService();
        $postIds = $this->getPostService()->fetchIdsByCursor(0, 33);
        $taskIds = [];
        foreach ($postIds as $postId) {
            $task = new RobotLikePostTask();
            $task->total = 1;
            $task->postId = $postId;
            $service->dispatchTask($task);
            $taskIds[] = $task->id;
        }

        $sql = 'delete from like_post where post_id in ('.implode(',', $postIds).')';
        $this->getDBConnection()->executeUpdate($sql);

        // 执行等待中的任务
        $r = $service->processWaitingTasks(10);

        $failTaskIds = [];
        $successTaskIds = [];
        foreach ($taskIds as $key => $taskId) {
            if (empty($r[$taskId]['result'])) {
                $failTaskIds[$taskId] = $taskId;
                continue;
            }
            $successTaskIds[$taskId] = $taskId;
        }

        $this->assertNotEmpty($successTaskIds);

        $sql = 'select * from robot_like_post_task where id in ('.implode(',', $taskIds).')';
        $r = $this->getDBConnection()->executeQuery($sql)->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($r as $item) {
            if (isset($successTaskIds[$item['id']])) {
                $this->assertEquals(3, $item['state']);
            } else {
                $this->assertEquals(1, $item['state']);
            }
        }

    }


}
