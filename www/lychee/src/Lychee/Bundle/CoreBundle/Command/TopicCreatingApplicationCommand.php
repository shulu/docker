<?php
namespace Lychee\Bundle\CoreBundle\Command;

use Lychee\Module\Notification\NotificationService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Lychee\Module\Topic\TopicService;

class TopicCreatingApplicationCommand extends ContainerAwareCommand {
    use ModuleAwareTrait;

    protected function configure() {
        $this
            ->setName('lychee:utilily:handle-topic-create-application')
            ->setDefinition(array())
            ->setDescription('handle topic creating application')
            ->setHelp(<<<EOT
This command will handle topic creating application.

EOT
            )
            ->addArgument('action', InputArgument::REQUIRED, 'reject or confirm')
            ->addArgument('applicationId', InputArgument::REQUIRED, 'application id')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $action = $input->getArgument('action');
        if (!in_array($action, array('confirm', 'reject'))) {
            throw new \InvalidArgumentException('action');
        }
        $applicationId = intval($input->getArgument('applicationId'));
        if ($applicationId == 0) {
            throw new \InvalidArgumentException('applicationId');
        }

        /** @var TopicService $topicService */
        $topicService = $this->container()->get('lychee.module.topic');
        /** @var NotificationService $notificationService */
        $notificationService = $this->container()->get('lychee.module.notification');
        $application = $topicService->getCreatingApplication($applicationId);
        if ($action == 'confirm') {
            $topic = $topicService->confirmCreatingApplication($applicationId);
            $notificationService->notifyTopicCreateSuccessEvent($application->creatorId, $topic->id);
        } else {
            $topicService->rejectCreatingApplication($applicationId);
            $notificationService->notifyTopicCreateFailureEvent($application->creatorId, $application->title);
        }
    }
}