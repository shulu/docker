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
use Lychee\Component\KVStorage\MemcacheStorage;
use Lychee\Component\KVStorage\CachedDoctrineStorage;

class ClearPostCacheCommand extends ContainerAwareCommand
{
    private $output;
    private $input;

    /**
     *
     */
    protected function configure() {
        $this->setName('lychee-admin:clear-post-cache')
            ->setDescription('清除帖子缓存')
            ->addArgument('postId', InputArgument::REQUIRED, "帖子id");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->output = $output;
        $this->input = $input;

        $memcache = $this->getContainer()->get('memcache.default');

        $postId = $input->getArgument('postId');

        $cacheStorage = new MemcacheStorage($memcache, 'post:', 86400);
        $cacheStorage->delete($postId);

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