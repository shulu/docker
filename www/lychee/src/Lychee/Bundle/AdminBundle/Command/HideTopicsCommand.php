<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 9/14/15
 * Time: 11:40 AM
 */

namespace Lychee\Bundle\AdminBundle\Command;


use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HideTopicsCommand extends ContainerAwareCommand {
    use ModuleAwareTrait;

    protected function configure() {
        $this->setName('lychee-admin:hide-topics')
            ->setDescription('Hide topics.')
            ->addArgument('topics', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $topics = $input->getArgument('topics');
        $topicsArr = explode(',', $topics);
        if (is_array($topicsArr)) {
            foreach($topicsArr as $t) {
                $this->topic()->hide($t);
                $output->writeln($t);
            }
        }
    }
}
