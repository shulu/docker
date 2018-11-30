<?php

namespace Lychee\Module\Notification\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Notification\EventSubscriber;
use Lychee\Module\Notification\NotificationService;
use Lychee\Module\Post\PostEvent;

class EventSubscriberTest extends ModuleAwareTestCase {

    public function test() {
        /** @var EventSubscriber $service */
        $subscriber = $this->container()->get('lychee.module.notification.event_subscriber');
        $event = new PostEvent(75259841677313);
        $subscriber->onPostCreate($event);
    }
}
 