<?php
namespace Lychee\Bundle\CoreBundle\Command;

use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Test\ModuleAwareTestCase;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Lychee\Module\Topic\Deletion\Deletor;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class DeleteTopicCommand extends ContainerAwareCommand {
    use ModuleAwareTrait;

    protected function configure() {
        $this
            ->setName('lychee:topic:delete')
            ->setDefinition(array())
            ->setDescription('delete speical topic')
            ->setHelp(<<<EOT
This command will delete speical topic.

EOT
            )->addArgument('topic_id', InputArgument::OPTIONAL, 'topic id')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $topicId = intval($input->getArgument('topic_id'));
        if ($topicId == 0) {
            return;
        }
        /** @var Deletor $deletor*/
        $deletor = $this->container()->get('lychee.module.topic.deletor');
        $deletor->delete($topicId);
    }
}