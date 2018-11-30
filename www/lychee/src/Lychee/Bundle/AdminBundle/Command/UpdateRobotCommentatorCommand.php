<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14-7-9
 * Time: 上午11:50
 */

namespace Lychee\Bundle\AdminBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class UpdateRobotCommentatorCommand extends ContainerAwareCommand
{
    private $output;
    private $input;

    /**
     *
     */
    protected function configure() {
        $this->setName('lychee-admin:robot:update-commentator')
            ->setDescription('更新自动评论中使用的机器人');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->output = $output;
        $this->input = $input;

        $service = $this->getContainer()->get('lychee.module.robot.comment');
        $service->sysncWorkers();

        $this->output->writeln('Done');
    }
}