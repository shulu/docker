<?php
namespace Lychee\Component\Task\Command;

use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tests\Command\HelpCommandTest;
use Lychee\Component\Task\TaskManager;

class SetTaskFireTimeCommand extends ContainerAwareCommand {
    use ModuleAwareTrait;

    protected function configure() {
        $this->setName('lychee:task:set-fire-time')
            ->addArgument('name', InputArgument::REQUIRED, 'The task name')
            ->addArgument('time', InputArgument::REQUIRED, 'The fire time')
            ->setDescription('Set the specific task next run time')
            ->setHelp(<<<Help
This command will update the task next run time with specific name.
Help
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $taskName = $input->getArgument('name');
        $taskManager = $this->getTaskManager();
        $task = $taskManager->findTask($taskName);
        if ($task === null) {
            $output->writeln('<error>Task not found.</error>');
            return;
        }

        $taskState = $this->getTaskManager()->getTaskState($task);
        $fireTimeString = $input->getArgument('time');
        $fireTime = new \DateTime($fireTimeString);
        if ($fireTime === false) {
            $output->writeln('<error>Invalid time string.</error>');
            return;
        }

        $now = new \DateTime();
        if ($fireTime < $now) {
            $tomorrow = $now->add(new \DateInterval('P1D'));
            $year = $tomorrow->format('Y');
            $month = $tomorrow->format('m');
            $day = $tomorrow->format('d');
            $fireTime->setDate($year, $month, $day);
        }

        $taskState->nextRunTime = $fireTime;
        $taskManager->updateTaskState($taskState);
        $output->writeln(sprintf(
            '<info>Ok.</info> the task with name "<info>%s</info>" will run at time <info>%s</info>',
            $task->getName(), $fireTime->format('Y-m-d H:i:s')
        ));
    }

    /**
     * @return TaskManager
     */
    private function getTaskManager() {
        return $this->getContainer()->get('lychee.task.manager');
    }
}