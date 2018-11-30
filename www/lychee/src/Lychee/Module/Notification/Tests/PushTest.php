<?php

namespace Lychee\Module\Notification\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Notification\Push\PushEventType;
use Lychee\Module\Notification\Push\Pusher;

class PushTest extends ModuleAwareTestCase {
    public function test() {
        /** @var Pusher $pusher */
        $pusher = $this->container()->get('lychee.module.notification.pusher.jpush');

        for ($i = 0;$i < 5; ++$i) {
            $pusher->pushEvent(PushEventType::MESSAGE, 31722, 31728, '今晚食鸡肶，听晚食火腿，后晚食牛排，再然后乜都唔食。abcdefghi abcdefghi abcdefghi abcdefghi abcdefghi ');
        }
    }
}
 