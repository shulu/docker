<?php
namespace Lychee\Module\Notification\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Notification\Entity\OfficialNotification;

class OfficialTest extends ModuleAwareTestCase {

    public function test() {
        $ns = $this->container()->get('lychee.module.notification');
        $ns->notifyBecomeCoreMemberEvent(31728, 25068, 31721);
    }
    
}