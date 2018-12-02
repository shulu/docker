<?php
namespace Lychee\Component\Task\Command;

use Lychee\Component\Task\Cron\Cron;
use Lychee\Component\Task\Cron\CronManager;
use Lychee\Component\Process\PidFile;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Lychee\Component\Task\TaskManager;
use Lychee\Component\Task\Task;
use Symfony\Component\Process\Process;

class ScheduleTaskCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName('lychee:task:schedule')
            ->setDescription('Control all the enabled tasks')
            ->setHelp(<<<Help
This command will control the task scheduler.

- start     update the crontab, start check the tasks in specified interval.
- stop      update the crontab, stop checking the tasks.

Help
            )
            ->addArgument('action', InputArgument::REQUIRED, 'action to perform')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $action = $input->getArgument('action') . 'Action';

        if (is_callable(array($this, $action))) {
            $this->$action($input, $output);
        } else {
            $output->writeln('can not resolve this action '. $action);
        }
    }

    private function getScheduleActionCommand() {
        $phpFinder = new PhpExecutableFinder;
        if (!$phpPath = $phpFinder->find()) {
            throw new \RuntimeException('The php executable could not be found, add it to your PATH environment variable and try again');
        }

        $commandName = $this->getName();
        $consolePath = $this->getContainer()->getParameter('kernel.root_dir').'/console';

        $env = $this->getContainer()->get('kernel')->getEnvironment();
        $noDebug='';
        if ('prod'==$env) {
            $noDebug='--no-debug';
        }

        return $phpPath . ' ' . $consolePath . ' '. $commandName . ' schedule -e='.$env.' '.$noDebug;
    }

    private function getRunTaskCommand($taskName) {
        $phpFinder = new PhpExecutableFinder;
        if (!$phpPath = $phpFinder->find()) {
            throw new \RuntimeException('The php executable could not be found, add it to your PATH environment variable and try again');
        }

        $consolePath = $this->getContainer()->getParameter('kernel.root_dir').'/console';
        $commandName = 'lychee:task:run';
        $taskName = escapeshellarg($taskName);

        return $phpPath . ' ' . $consolePath . ' '. $commandName . ' ' . $taskName . ' -e=prod --no-debug';
    }

    private function isScheduleCommand($command) {
        $commandName = $this->getName();
        $consolePath = $this->getContainer()->getParameter('kernel.root_dir').'/console';
        $checkCommand = $consolePath . ' '. $commandName . ' schedule';
        if (strrpos($command, $checkCommand) !== false) {
            return true;
        } else {
            return false;
        }
    }

    private function getTaskVarDirPath() {
        $rootDir = $this->getContainer()->getParameter('kernel.root_dir');
        $pathPieces = array($rootDir, '..', '..', 'var', 'task');
        $varDirPath = implode(DIRECTORY_SEPARATOR, $pathPieces);
        if (file_exists($varDirPath) === false) {
            mkdir($varDirPath, 0777, true);
        }
        return $varDirPath;
    }

    private function getTaskPidFilePath() {
        $varDir = $this->getTaskVarDirPath();
        return $varDir . DIRECTORY_SEPARATOR . 'task.pid';
    }

    private function getTaskLogFilePath() {
        $varDir = $this->getTaskVarDirPath();
        return $varDir . DIRECTORY_SEPARATOR . 'task.log';
    }

    private function startCron() {
        $this->stopCron();

        $cron = new Cron();
        $cron->setCommand($this->getScheduleActionCommand());
        $cron->setMinute('*/1');
        $cron->setLogFile('/dev/null');

        $cronManager = new CronManager();
        $cronManager->add($cron);
    }

    private function stopCron() {
        $cronManager = new CronManager();
        foreach ($cronManager->get() as $index => $cron) {
            /** @var Cron $cron */
            if ($this->isScheduleCommand($cron->getCommand())) {
                $cronManager->remove($index);
            }
        }
    }

    /**
     * @return TaskManager
     */
    private function getTaskManager() {
        return $this->getContainer()->get('lychee.task.manager');
    }

    public function testAction(InputInterface $input, OutputInterface $output) {
        var_dump($this->getTaskManager()->getTasks());
        $testTask = $this->getTaskManager()->getTasks()[0];
        var_dump($testTask->run());
    }

    public function startAction(InputInterface $input, OutputInterface $output) {
        $output->writeln('start task serivice');
        $this->startCron();
    }

    public function stopAction(InputInterface $input, OutputInterface $output) {
        $output->writeln('stopping task serivice...');
        $this->stopCron();

        $pidFilePath = $this->getTaskPidFilePath();
        $pidFile = new PidFile($pidFilePath);
        if ($pidFile->getPid() !== null) {
            $output->writeln('waiting task finish');

            if ($pidFile->acquireLock(true)) {
                $pidFile->releaseLock();
            } else {
                $output->writeln('fail to acquire lock. maybe a retry?');
            }
        }

        $output->writeln('done.');
    }

    public function scheduleAction(InputInterface $input, OutputInterface $output) {
        $pidFilePath = $this->getTaskPidFilePath();
        $pidFile = new PidFile($pidFilePath);

        if ($pidFile->acquireLock()) {
            $pid = posix_getpid();
            $pidFile->setPid($pid);

            $taskManager = $this->getTaskManager();
            $taskManager->scheduleTasks(function($task) use ($output) {
                /** @var Task $task */
                $runCommand = $this->getRunTaskCommand($task->getName());
                $process = new Process(
                    $runCommand, getcwd(), null, null, null
                );
                $process->run(function($type, $buffer) use ($output) {
                    $output->write($buffer);
                });
                if (!$process->isSuccessful()) {
                    throw new \RuntimeException(
                        "An error occurred when running task [{$task->getName()}]"
                    );
                }
            });

            $pidFile->releaseLock();
        } else {
            $output->writeln('command already running.');
        }
    }
}