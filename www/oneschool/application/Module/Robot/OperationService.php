<?php
namespace Lychee\Module\Robot;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Lychee\Component\Foundation\ArrayUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OperationService {
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var ContainerInterface
     */
    private $serviceContainer;

    private $workerTableName;
    private $taskTableName;

    private $workerPool = [];

    /**
     * @var callable
     */
    private $processTaskMethod;

    // 等待处理的任务状态
    const TASK_WAITING_STATE=1;
    // 处理中的任务状态
    const TASK_PROCESSING_STATE=2;
    // 已处理完的任务状态
    const TASK_FINISHED_STATE=3;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct($registry, $serviceContainer,
                                $workerTableName, $taskTableName,
                                $processTaskService, $processTaskMethodName) {
        $this->entityManager = $registry->getManager();
        $this->serviceContainer = $serviceContainer;
        $this->workerTableName = $workerTableName;
        $this->taskTableName = $taskTableName;
        $this->processTaskMethod = function ($workerId, $task)
        use ($processTaskService, $processTaskMethodName) {
            $processTaskService->$processTaskMethodName($workerId, $task);
        };
    }


    /**
     * 是否机器人
     *
     * @param $userId
     * @return bool
     */
    public function isWorker($userId)
    {
        $userId = intval($userId);
        $sql = 'SELECT 1 FROM '.$this->workerTableName.' WHERE id='.$userId;
        $r = $this->entityManager->getConnection()
            ->executeQuery($sql)
            ->fetch(\PDO::FETCH_ASSOC);
        if ($r) {
            return true;
        }
        return false;
    }


    private function getLogger()
    {
        return $this->serviceContainer->get('logger');
    }


    public function getWorkerTableName()
    {
        return $this->workerTableName;
    }

    public function getTaskTableName()
    {
        return $this->taskTableName;
    }

    /**
     * 新增用于执行某项业务的机器人
     *
     * @param $table
     * @return bool
     */
    public function inviteWorkers()
    {
        $cursor = 0;
        while (true) {

            usleep(200000);

            try {

                $sql = "SELECT id FROM robot WHERE id>? ORDER BY id ASC LIMIT 500";
                $statement = $this->entityManager->getConnection()->executeQuery($sql, [$cursor]);
                $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
                $robotIds = [];
                foreach ($result as $item) {
                    $robotIds[] = $cursor = $item['id'];
                }
                if (empty($robotIds)) {
                    return false;
                }

                $sql = 'SELECT id FROM '.$this->workerTableName;
                $sql .= ' WHERE id IN ('.implode(',', $robotIds).')';
                $statement = $this->entityManager->getConnection()->executeQuery($sql);
                $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
                $actionRobotIds = ArrayUtility::columns($result, 'id');
                $newActionRobotIds = array_diff($robotIds, $actionRobotIds);
                if (empty($newActionRobotIds)) {
                    continue;
                }

                $sql = 'INSERT INTO '.$this->workerTableName;
                $sql .= '(id, action_time) VALUES';

                $snippet = [];
                foreach ($newActionRobotIds as $robotId) {
                    $snippet[] = '('.$robotId.', '.time().')';
                }
                $sql .= implode(',', $snippet);
                $this->entityManager->getConnection()->executeUpdate($sql);

            } catch (\Exception $e) {
                $this->getLogger()->error($e->getMessage());
            }
        }

    }

    /**
     * 删除执行某项业务的机器人
     *
     * @param $table
     * @return bool
     */
    public function fireWorkers()
    {
        $cursor = 0;
        while (true) {

            usleep(200000);

            try {

                $sql = 'SELECT id FROM '.$this->workerTableName;
                $sql .= ' WHERE id>? ORDER BY id ASC LIMIT 500';
                $statement = $this->entityManager->getConnection()->executeQuery($sql, [$cursor]);
                $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
                $actionRobotIds = [];
                foreach ($result as $item) {
                    $actionRobotIds[] = $cursor = $item['id'];
                }
                if (empty($actionRobotIds)) {
                    return false;
                }

                $sql = 'SELECT id FROM robot';
                $sql .= ' WHERE id IN ('.implode(',', $actionRobotIds).')';
                $statement = $this->entityManager->getConnection()->executeQuery($sql);
                $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
                $robotIds = ArrayUtility::columns($result, 'id');
                $deletedActionRobotIds = array_diff($actionRobotIds, $robotIds);
                if (empty($deletedActionRobotIds)) {
                    continue;
                }

                $sql = 'DELETE FROM '.$this->workerTableName.' WHERE id IN ('.implode(',', $deletedActionRobotIds).')';
                $this->entityManager->getConnection()->executeUpdate($sql);

            } catch (\Exception $e) {
                $this->getLogger()->error($e->getMessage());
            }
        }

    }

    /**
     * 同步机器人表
     */
    public function sysncWorkers()
    {
        $this->inviteWorkers();
        $this->fireWorkers();
    }

    /**
     * 累计机器人操作时间，次数
     *
     * @param $table
     * @param $robotId
     * @return bool
     */
    public function logWorkerTimes($robotId)
    {
        $robotId = intval($robotId);
        $sql = 'UPDATE '.$this->workerTableName;
        $sql .= ' SET total=total+1, action_time='.time();
        $sql .= ' WHERE id='.$robotId;
        try {
            $effectedCount = $this->entityManager->getConnection()->executeUpdate($sql);
        } catch (\Exception $e) {
            return false;
        }
        if ($effectedCount>0) {
            return true;
        }
        return false;
    }

    /**
     * 新增机器人任务
     *
     * @param $task
     * @return mixed
     */
    public function dispatchTask($task)
    {
        $task->updateTime = $task->createTime = time();
        $task->state = self::TASK_WAITING_STATE;
        $this->entityManager->persist($task);
        $this->entityManager->flush();
        return $task;
    }

    /**
     * 根据任务状态查询任务列表
     *
     * @param $state  int 任务状态
     * @param $cursor int 上一页最后一个任务id
     * @param $count  int 最大记录数
     * @return array
     */
    private function getTasksByState($state, $cursor, $count)
    {
        $state = intval($state);
        $cursor = intval($cursor);
        $count = intval($count);
        $sql = 'select * from '.$this->taskTableName;
        $sql .= ' where id>'.$cursor.' and state='.$state;
        $sql .= ' order by id asc limit '.$count;
        $result = $this->entityManager->getConnection()
            ->executeQuery($sql)
            ->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * 获取未处理的任务列表
     *
     * @param $cursor
     * @param $count
     * @return array
     */
    public function getWaitingTasks($cursor, $count)
    {
        return $this->getTasksByState(self::TASK_WAITING_STATE, $cursor, $count);
    }

    /**
     * 查询机器人
     * @param $count
     * @return array
     */
    public function fetchWorkerIds($count)
    {
        $count = intval($count);
        $sql = 'select id from '.$this->workerTableName;
        $sql .= ' order by total asc, action_time asc';
        $sql .= ' limit '.$count;
        $r = $this->entityManager->getConnection()
            ->executeQuery($sql)
            ->fetchAll(\PDO::FETCH_ASSOC);
        $result = [];
        foreach ($r as $item) {
            $result[] = $item['id'];
        }
        return $result;
    }

    /**
     * 将任务状态变更为处理中
     *
     * @param $taskId
     * @return bool
     */
    public function processingTask($taskId)
    {
        $taskId = intval($taskId);
        $sql = 'UPDATE '.$this->taskTableName
            .' SET state='.self::TASK_PROCESSING_STATE
            .', update_time='.time()
            .' WHERE state='.self::TASK_WAITING_STATE
            .' AND id='.$taskId;
        try {
            $effectedCount = $this->entityManager->getConnection()->executeUpdate($sql);
        } catch (\Exception $e) {
            return false;
        }

        if ($effectedCount>0) {
            return true;
        }

        return false;
    }

    /**
     * 任务状态从进行中变更为未处理，并更新剩余的点赞次数
     *
     * @param $taskId
     * @return bool
     */
    public function pauseTask($taskId, $total)
    {
        $total = intval($total);
        $taskId = intval($taskId);
        $sql = 'UPDATE '.$this->taskTableName
            . ' SET total='.$total
            .', state='.self::TASK_WAITING_STATE
            .', update_time='.time()
            .' WHERE id='.$taskId.' and state='.self::TASK_PROCESSING_STATE;
        try {
            $effectedCount = $this->entityManager->getConnection()->executeUpdate($sql);
        } catch (\Exception $e) {
            return false;
        }
        if ($effectedCount>0) {
            return true;
        }
        return false;
    }

    /**
     * 将任务状态变更为已处理
     *
     * @param $taskId
     * @return bool
     */
    public function finishedTask($taskId)
    {
        $taskId = intval($taskId);
        $sql = 'UPDATE '.$this->taskTableName
            . ' SET state='.self::TASK_FINISHED_STATE
            .', update_time='.time()
            .' WHERE id='.$taskId;
        try {
            $effectedCount = $this->entityManager->getConnection()->executeUpdate($sql);
        } catch (\Exception $e) {
            return false;
        }

        if ($effectedCount>0) {
            return true;
        }

        return false;
    }

    /**
     * 扩充机器人池
     *
     * @param $total
     * @return array
     */
    public function addWorkerPool($total)
    {
        $this->workerPool = array_merge($this->workerPool, $this->fetchWorkerIds($total));
        return $this->workerPool;
    }

    /**
     * 批量处理等待中的任务
     *
     * @param int $step 每批任务数量
     * @return array
     */
    public function processWaitingTasks($step=500)
    {
        $res = [];
        $cursor = 0;
        // 分批查询未完成的任务
        while (true) {

            $tasks = $this->getWaitingTasks($cursor, $step);
            if (empty($tasks)) {
                return $res;
            }
            $total = 0;
            // 遍历任务计算需要分配多少个机器人来点赞
            foreach ($tasks as $task) {
                $total += $task['total'];
                $cursor = $task['id'];
            }

            $this->addWorkerPool($total);

            // 分配点赞任务
            foreach ($tasks as $task) {
                $res[$task['id']] = $this->processWaitingTask($task);
                usleep(200000);
            }
        }
        return $res;
    }

    /**
     * 处理单个等待中的任务
     * @return array
     */
    public function processWaitingTask($task)
    {
        $res = [
            'result' => false
        ];

        if (empty($this->processingTask($task['id']))) {
            $this->getLogger()->error(sprintf("任务 (%s) 正在处理中...",
                $task['id']));
            return $res;
        }

        $res = $this->processTask($task);
        $leftTotal = $res['leftTotal'];
        if ($leftTotal>0) {
            $this->pauseTask($task['id'], $leftTotal);
        } else {
            $this->finishedTask($task['id']);
        }
        return $res;
    }

    /**
     * 处理单个任务
     *
     * @param $workerId
     * @param $task
     */
    public function processTask($task)
    {
        $ret = [
            'leftTotal' => $task['total'],
            'result' => false,
        ];

        if (empty($this->workerPool)) {
            $this->addWorkerPool($task['total']);
        }

        if (empty($this->workerPool)) {
            $this->getLogger()->error(sprintf("没有可以使用的机器人了，无法处理任务 (%s)",
                $task['id']));
            return $ret;
        }

        $leftTotal = $task['total'];
        $ret['result'] = true;
        for ($i=0; $i<$task['total']; $i++) {
            $workerId = $this->workerPool[0];
            try {
                call_user_func_array($this->processTaskMethod, [$workerId, $task]);
                $this->logWorkerTimes($workerId);
                $leftTotal--;
                array_shift($this->workerPool);
            } catch (\Exception $e) {
                $this->getLogger()->error(sprintf('任务（%s）处理失败，原因：%s',
                    $task['id'], $e->getMessage()));
                $ret['result'] = false;
                continue;
            }
        }

        $ret['leftTotal'] = $leftTotal;
        return $ret;
    }

}