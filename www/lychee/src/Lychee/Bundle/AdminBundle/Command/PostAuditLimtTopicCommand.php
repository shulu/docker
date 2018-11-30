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

class PostAuditLimtTopicCommand extends ContainerAwareCommand
{
    private $output;
    private $input;

    /**
     *
     */
    protected function configure() {
        $this->setName('lychee-admin:post-audit-limit-topic')
            ->setDescription('manage POST AUDIT LIMIT TOPIC.')
            ->addArgument('action', InputArgument::REQUIRED, "What do you want to do? Use 'list' to get commands.")
            ->addOption('topicId', null, InputOption::VALUE_REQUIRED, "topic id");
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
     * 将精选次元加入到审核列表中
     */
    final private  function generateFromRecommendableTopicAction() {
        $this->output->writeln('Generating From Recommendation Topics...');
        $sql = 'INSERT IGNORE INTO  post_audit_limit_topic (`topic_id`) SELECT topic_id FROM recommendable_topic';
        $this->doctrine()->getManager()->getConnection()->executeUpdate($sql);
        $this->output->writeln('Done.');
    }

    /**
     * 清空审核列表
     */
    final private  function clearAllAction() {
        $this->output->writeln('Clear All...');
        $sql = 'delete from post_audit_limit_topic';
        $this->doctrine()->getManager()->getConnection()->executeUpdate($sql);
        $this->output->writeln('Done.');
    }


    /**
     * 从审核列表删除指定次元
     */
    final private  function delAction() {
        $topicId = $this->input->getOption('topicId');
        if (empty($topicId)) {
            $this->output->writeln('缺少参数指定次元id, 如要删除123次元，需加参数： --topicId=123.');
            return;
        }
        $this->output->writeln('Delete Topic '.$topicId.'...');
        $sql = 'delete from post_audit_limit_topic where topic_id=?';
        $this->doctrine()->getManager()->getConnection()->executeUpdate($sql, [$topicId]);
        $this->output->writeln('Done.');
    }

    /**
     * 从审核列表新增指定次元
     */
    final private  function addAction() {
        $topicId = $this->input->getOption('topicId');
        if (empty($topicId)) {
            $this->output->writeln('缺少参数指定次元id, 如要新增123次元，需加参数： --topicId=123.');
            return;
        }
        $this->output->writeln('Add Topic '.$topicId.'...');
        $sql = 'insert into post_audit_limit_topic (topic_id) values (?)';
        $this->doctrine()->getManager()->getConnection()->executeUpdate($sql, [$topicId]);
        $this->output->writeln('Done.');
    }

    private function doctrine() {
        return  $this->getContainer()->get('doctrine');
    }


}