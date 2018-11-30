<?php
namespace Lychee\Bundle\CoreBundle\Command;

use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Notification\EventSubscriber;
use Lychee\Module\Post\PostEvent;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestPushCommand extends ContainerAwareCommand {
    use ModuleAwareTrait;

    protected function configure() {
        $this
            ->setName('lychee:test:push_followee_post')
            ->setDefinition(array())
            ->setDescription('just a test')
            ->setHelp(<<<EOT
This command just a test.

EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        /** @var EventSubscriber $service */
        $subscriber = $this->container()->get('lychee.module.notification.event_subscriber');
        $event = new PostEvent(75259841677313);
        $subscriber->onPostCreate($event);
    }
}