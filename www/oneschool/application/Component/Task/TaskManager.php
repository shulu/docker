<?php
namespace Lychee\Component\Task;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class TaskManager {

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $tasks;

    /**
     * @param Registry $doctrine
     * @param LoggerInterface $logger
     */
    public function __construct($doctrine, $logger) {
        $this->entityManager = $doctrine->getManager();
        $this->logger = $logger;
        $this->tasks = array();
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger() {
        return $this->logger;
    }

    /**
     * @param Task $task
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public function addTask($task) {
        if (($task instanceof Task) === false) {
            throw new \InvalidArgumentException("task must implement Task interface");
        }
        if ($task->getDefaultInterval() < 300) {
            throw new \LogicException("task interval must lager than 300 seconds");
        }
        $this->tasks[] = $task;
    }

    /**
     * @return array
     */
    public function getTasks() {
        return $this->tasks;
    }

    public function runTaskWithName($name) {
        $task = $this->findTask($name);
        if ($task === null) {
            throw new \InvalidArgumentException("task {$name} nonexist");
        }
        $this->runTask($task);
    }

    /**
     * @param Task $task
     */
    public function runTask($task) {
        try {
            $this->logger->info("[{$task->getName()}] Start");
            $st = microtime(true);
            $task->run();
            $et = microtime(true);
            $memoryByte = memory_get_peak_usage(true);
            $memoryKb = $memoryByte / 1024;
            $memoryMb = $memoryKb / 1024;
            $this->logger->info("[{$task->getName()}] End", array(
                'memory_peak' => "{$memoryKb}kb({$memoryMb}mb)",
                'time_elapsed' => ($et - $st) . 's',
            ));
        } catch (\Exception $e) {
            $this->logger->info("[{$task->getName()}] Exception", array(
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ));
        }
    }

    /**
     * @param string $name
     *
     * @return null|Task
     */
    public function findTask($name) {
        foreach ($this->tasks as $task) {
            if ($task->getName() === $name) {
                return $task;
            }
        }
        return null;
    }

    /**
     * @param callback $runFunction
     */
    public function scheduleTasks($runFunction = null) {
        gc_enable();
        $checkTime = new \DateTime();
        foreach ($this->tasks as $task) {
            /** @var Task $task */
            $taskState = $this->getTaskState($task);
            if ($this->taskCanRun($taskState, $checkTime) === false) {
                continue;
            }

            if ($runFunction) {
                $runFunction($task);
            } else {
                $this->runTask($task);
            }

            try {
                $taskState->lastCheckTime = $checkTime;
                $taskState->nextRunTime = null;
                $this->updateTaskState($taskState);
            } catch (\Exception $e) {
                $this->logger->emergency(
                    "Exception occurred when saving [{$task->getName()}] task state",
                    array(
                        'exception' => $e, 'check_time' => $checkTime
                    )
                );
            }
            gc_collect_cycles();
        }
    }

    /**
     * @param TaskState $taskState
     * @param \DateTime $checkTime
     * @return boolean
     */
    private function taskCanRun($taskState, $checkTime) {
        if ($taskState->disabled == true) {
            return false;
        } else if ($taskState->nextRunTime !== null) {
            if ($taskState->nextRunTime->getTimestamp() > $checkTime->getTimestamp()) {
                return false;
            }
        } else if ($taskState->lastCheckTime !== null) {
            $nextRunTime = $taskState->lastCheckTime->getTimestamp()
                + $taskState->runInterval;
            if ($nextRunTime > ($checkTime->getTimestamp() + 5)) {
                //允许5秒的误差
                return false;
            }
        }
        return true;
    }

    /**
     * @param Task $task
     *
     * @return null|TaskState
     */
    public function getTaskState($task) {
        $state = $this->entityManager->getRepository('Lychee\Component\Task\TaskState')
            ->findOneBy(array('taskName' => $task->getName()));
        if ($state === null) {
            $state = new TaskState();
            $state->taskName = $task->getName();
            $state->runInterval = $task->getDefaultInterval();
            $this->entityManager->persist($state);
        }

        return $state;
    }

    public function updateTaskState($taskState) {
        $this->entityManager->flush($taskState);
    }
}