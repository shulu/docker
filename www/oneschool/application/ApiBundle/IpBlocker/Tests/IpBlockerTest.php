<?php
namespace Lychee\Bundle\ApiBundle\IpBlocker\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;

class IpBlockerTest extends ModuleAwareTestCase {

    public function test() {
        $blocker = $this->container()->get('lychee_api.ip_blocker');
        $time = time() + 212800;
        $r = $blocker->checkAndUpdate('127.0.0.1', 'sms', $time);
        var_dump($r);
    }

}