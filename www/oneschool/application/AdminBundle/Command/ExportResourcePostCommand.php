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

class ExportResourcePostCommand extends ContainerAwareCommand {
    use ModuleAwareTrait;

    protected function configure() {
        $this->setName('lychee-admin:export-resource-post')
            ->setDescription('Export resource posts.')
            ->addArgument('keyword', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        /**
         * @var $searcher \Lychee\Module\Search\AbstractSearcher
         */
        $searcher = $this->getContainer()->get('lychee.module.search.postSearcher');
        $keyword = $input->getArgument('keyword');
        $resourcePosts = [];
        $offset = 0;
        $limit = 500;
        gc_enable();
        while ($result = $searcher->search($keyword, $offset, $limit)) {
            $posts = $this->post()->fetch($result);
            /**
             * @var $post \Lychee\Bundle\CoreBundle\Entity\Post
             */
            foreach ($posts as $post) {
                $content = $post->content;
                if (preg_match('/(http[s]{0,1}:\/\/\S+)/i', $content)) {
                    $resourcePosts[] = [$post->id, $content];
                }
            }
            $offset += count($posts);
            $output->writeln(sprintf("Offset: %s", $offset));
            $this->getContainer()->get('Doctrine')->getManager()->clear();
            gc_collect_cycles();
        }
        $fp = fopen('export.csv', 'w');
        foreach ($resourcePosts as $rp) {
            fputcsv($fp, $rp);
        }
        fclose($fp);
    }
}
