<?php
namespace Lychee\Module\Topic\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Topic\Following\ApplyService;

class ApplyTest extends ModuleAwareTestCase {

    /**
     * @return ApplyService
     */
    private function getApplyService() {
        return new ApplyService($this->container()->get('doctrine'),
            $this->container()->get('lychee.module.topic.following'));
    }

    public function test() {
        $service = $this->getApplyService();
        $service->apply(31722, 25056, '吃完饭55');
        $r = $service->fetchApplicationsByTopic(25056, 0, 1, $nextCursor);
        var_dump($nextCursor);
        var_dump($r);
    }
    
}