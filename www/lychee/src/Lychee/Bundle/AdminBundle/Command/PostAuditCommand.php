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

class PostAuditCommand extends ContainerAwareCommand
{
    private $output;
    private $input;

    /**
     *
     */
    protected function configure() {
        $this->setName('lychee-admin:post-audit')
            ->setDescription('manage POST AUDIT CONFIG.')
            ->addArgument('action', InputArgument::REQUIRED, "What do you want to do? Use 'list' to get commands.")
            ->addArgument('postId', InputArgument::REQUIRED, "post id");
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

    final private  function untreatedAction() {
        $postId = $this->input->getArgument('postId');
        $this->post()->changeAuditStatus([$postId], \Lychee\Module\Post\Entity\PostAudit::UNTREATED_STATUS);
        $this->output->writeln('Done.');
    }

    private function post() {
        return  $this->getContainer()->get('lychee.module.post');
    }


}