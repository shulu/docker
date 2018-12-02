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

class RecommendationPostscCommand extends ContainerAwareCommand
{
    private $output;
    private $input;

    /**
     *
     */
    protected function configure() {
        $this->setName('lychee-admin:recommendation-post')
            ->setDescription('manage Recommendation POST.')
            ->addArgument('action', InputArgument::REQUIRED, "What do you want to do? Use 'list' to get commands.")
            ->addOption('groupId', null, InputOption::VALUE_REQUIRED, "group id");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->output = $output;
        $this->input = $input;

        $action = $input->getArgument('action');
        $class = new \ReflectionClass($this);
        $methods = $class->getMethods(\ReflectionMethod::IS_FINAL);
        $actions = array();

        foreach ($methods as $method) {

            $methodName = substr($method->getName(), 0, strpos($method->getName(), 'Action'));
            $actions[$methodName] = array(
                'doc'=>$method->getDocComment()
            );
        }
        if ('list' === $action) {
            foreach ($actions as $action => $item) {
                $output->writeln($action);
            }
        } elseif (isset($actions[$action])) {
            $method = $action . 'Action';
            $this->$method();
        } else {
            $output->writeln('Unknown argument: ' . $action);
            $output->writeln("Use 'list' to get available arguments.");
        }
    }

    /**
     * 统计指定分组下的帖子数量
     */
    final private  function countAction() {
        $groupId = $this->input->getOption('groupId');
        if (empty($groupId)) {
            return $this->output->writeln('缺少参数 --groupId.');
        }
        $this->output->writeln('Counting Group ('.$groupId.') Recommendation Posts...');
        $sql = "select count(1) n, group_id from rec_group_posts where group_id in (".$groupId.") group by group_id";
        $stat = $this->getConnection()->executeQuery($sql);
        $r = $stat->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($r as $item) {
            $this->output->writeln('Group '.$item['group_id'] . ':'.$item['n']);
        }

    }

    private function getConnection() {
        return  $this->getContainer()->get('doctrine')->getManager()->getConnection();
    }


}