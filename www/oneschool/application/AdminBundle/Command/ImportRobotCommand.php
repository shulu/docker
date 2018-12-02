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

class ImportRobotCommand extends ContainerAwareCommand
{
    private $output;
    private $input;

    /**
     *
     */
    protected function configure() {
        $this->setName('lychee-admin:robot:import')
            ->setDescription('导入机器人')
        ->addArgument('src', InputArgument::REQUIRED, "用户id的文件地址，换行符分割");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->output = $output;
        $this->input = $input;

        $path = $this->input->getArgument('src');

        $robotIds = file_get_contents($path);
        $robotIds = trim($robotIds);
        $robotIds = explode("\n", $robotIds);
        $robotIds = array_unique($robotIds);
        $total = count($robotIds);
        $this->output->writeln(sprintf('该次导入%s 个机器人', $total));

        $r = $this->robotService()->import($robotIds);

        $this->output->writeln(sprintf('导入了%s 个机器人', $r));

        $this->output->writeln('Done');
    }

    private function robotService() {
        return  $this->getContainer()->get('lychee.module.robot');
    }

}