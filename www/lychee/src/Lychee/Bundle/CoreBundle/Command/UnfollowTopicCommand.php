<?php
namespace Lychee\Bundle\CoreBundle\Command;

use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Test\ModuleAwareTestCase;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class UnfollowTopicCommand extends ContainerAwareCommand {
    use ModuleAwareTrait;

    protected function configure() {
        $this
            ->setName('lychee:migration:unfollow_topic')
            ->setDefinition(array())
            ->setDescription('unfollow speical topic')
            ->setHelp(<<<EOT
This command will unfollow speical topic.

EOT
            )->addArgument('topic_id', InputArgument::OPTIONAL, 'topic id')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $topicId = intval($input->getArgument('topic_id'));
        if ($topicId == 0) {
            return;
        }
        $itor = $this->topicFollowing()->getTopicFollowerIterator($topicId);
        $itor->setStep(10);
        foreach ($itor as $followerIds) {
            foreach ($followerIds as $followerId) {
                $this->topicFollowing()->unfollow($followerId, $topicId);
            }
        }

    }
}