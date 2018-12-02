<?php
namespace Lychee\Component\Task\Command;

use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tests\Command\HelpCommandTest;
use Lychee\Component\Task\TaskManager;


class RunTaskCommand extends ContainerAwareCommand {
    use ModuleAwareTrait;

    protected function configure() {
        $this->setName('lychee:task:run')
            ->addArgument('name', InputArgument::REQUIRED, 'The task name')
            ->setDescription('Run the specific task')
            ->setHelp(<<<Help
This command will run the task with specific name.
Help
);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $taskName = $input->getArgument('name');
        $tm = $this->getTaskManager();
        $logger = $tm->getLogger();
        if ($logger instanceof \MonoLog\Logger) {
            $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
            $handlers = $logger->getHandlers();
            array_push($handlers, new ConsoleHandler($output, true));
            $logger->setHandlers($handlers);
        }

        $tm->runTaskWithName($taskName);

        if ($logger instanceof \MonoLog\Logger) {
            $handlers = $logger->getHandlers();
            array_pop($handlers);
            $logger->setHandlers($handlers);
        }
    }

    /**
     * @return TaskManager
     */
    private function getTaskManager() {
        return $this->container()->get('lychee.task.manager');
    }

}