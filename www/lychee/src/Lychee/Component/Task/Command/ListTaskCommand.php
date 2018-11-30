<?php
namespace Lychee\Component\Task\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Lychee\Component\Task\TaskManager;
use Lychee\Component\Task\Task;

class ListTaskCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName('lychee:task:list')
            ->setDescription('List all tasks infomation')
            ->setHelp(<<<Help
This command will list all tasks infomation.
Help
            );
    }

    /**
     * @return TaskManager
     */
    private function getTaskManager() {
        return $this->getContainer()->get('lychee.task.manager');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $tasks = $this->getTaskManager()->getTasks();
        foreach ($tasks as $task) {
            /** @var Task $task */
            $output->writeln("{$task->getName()}");
        }
    }

} 