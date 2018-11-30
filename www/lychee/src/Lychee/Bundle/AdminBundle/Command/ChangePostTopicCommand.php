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

class ChangePostTopicCommand extends ContainerAwareCommand
{
    private $output;
    private $input;

    /**
     *
     */
    protected function configure() {
        $this->setName('lychee-admin:change-post-topic')
            ->setDescription('更改帖子次元')
            ->addArgument('postId', InputArgument::REQUIRED, "帖子id")
            ->addArgument('topicId', InputArgument::REQUIRED, "次元id");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->output = $output;
        $this->input = $input;

        $postId = $input->getArgument('postId');
        $topicId = $input->getArgument('topicId');


        $post = $this->post()->fetchOne($postId);
        if (null === $post) {
            $this->output->writeln('帖子不存在');
            return;
        }

        if ($post->topicId==$topicId) {
            $this->output->writeln('帖子已经属于该次元');
            return;
        }

        $topic = $this->topic()->fetchOne($topicId);
        if (null === $topic) {
            $this->output->writeln('帖子不存在');
            return;
        }

        $this->post()->updateTopic($postId, $topicId);
        $this->postSticky()->unstickPost($postId);

        $this->output->writeln('Done');

    }

    private function postSticky() {
        return  $this->getContainer()->get('lychee.module.post.sticky');
    }

    private function post() {
        return  $this->getContainer()->get('lychee.module.post');
    }

    private function topic() {
        return  $this->getContainer()->get('lychee.module.topic');
    }

}