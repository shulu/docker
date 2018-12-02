<?php
namespace Lychee\Module\Account\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Account\DeviceBlocker;


class DeviceTest extends ModuleAwareTestCase {

    /**
     * @return DeviceBlocker
     */
    private function deviceBlocker() {
        return $this->container()->get('lychee.module.account.device_blocker');
    }

    public function test() {
        $blocker = $this->deviceBlocker();

        $blocker->updateUserDeviceId(31728, 'iphone', 'wojiushideviceid');
        list($platform, $deviceId) = $blocker->getUserDeviceId(31728);
        $this->assertEquals('iphone', $platform);
        $this->assertEquals('wojiushideviceid', $deviceId);

        $this->assertFalse($blocker->isDeviceBlocked($platform, $deviceId));
        $blocker->blockUserDevice(31728);
        $this->assertTrue($blocker->isDeviceBlocked($platform, $deviceId));
        $blocker->unblockDevice($platform, $deviceId);
        $this->assertFalse($blocker->isDeviceBlocked($platform, $deviceId));
    }
    
}