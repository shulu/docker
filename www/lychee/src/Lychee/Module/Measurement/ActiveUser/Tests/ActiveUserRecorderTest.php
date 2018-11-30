<?php
namespace Lychee\Module\Measurement\ActiveUser\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Measurement\ActiveUser\ActiveUserRecorder;
use Lychee\Module\Measurement\ActiveUser\RecordCommand;

class ActiveUserRecorderTest extends ModuleAwareTestCase {

    /**
     * @return \Predis\Client
     */
    private function getRedis() {
        return $this->container->get('snc_redis.test');
    }

    public function test() {
        $key = 'active_user_' . date('Ymd');
        $command = new RecordCommand();
        $command->setArguments(array($key, 51728));
        $r = $this->getRedis()->executeCommand($command);
        var_dump($r);
    }
    
}