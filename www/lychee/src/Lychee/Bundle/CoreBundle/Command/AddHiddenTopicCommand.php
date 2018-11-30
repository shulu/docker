<?php
namespace Lychee\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Lychee\Module\Topic\TopicService;

class AddHiddenTopicCommand extends ContainerAwareCommand {
    use ModuleAwareTrait;

    protected function configure() {
        $this
            ->setName('lychee:utilily:add-hidden-topic')
            ->setDefinition(array())
            ->setDescription('add the hidden topic')
            ->setHelp(<<<EOT
This command will add hidden topic.

EOT
            )
            ->addArgument('topicId', InputArgument::REQUIRED, 'topic id')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $topicId = $input->getArgument('topicId');

        /** @var TopicService $topicService */
        $topicService = $this->container()->get('lychee.module.topic');
        $topicService->hide($topicId);
    }
}