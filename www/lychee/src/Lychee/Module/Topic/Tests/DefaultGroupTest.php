<?php
namespace Lychee\Module\Topic\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Topic\TopicDefaultGroupService;

class DefaultGroupTest extends ModuleAwareTestCase{
    /**
     * @return TopicDefaultGroupService
     */
    private function getDefaultGroupService() {
        return $this->container()->get('lychee.module.topic.default_group');
    }

    public function test() {
        $service = $this->getDefaultGroupService();
        $r = $service->getDefaultGroup(31728);
        var_dump($r);
        $service->updateDefaultGroup(31728, 1);
        $r = $service->getDefaultGroup(31728);
        var_dump($r);
        $service->updateDefaultGroup(31728, 2);
        $r = $service->getDefaultGroup(31728);
        var_dump($r);
        $service->updateDefaultGroup(31728, 0);
        $r = $service->getDefaultGroup(31728);
        var_dump($r);
    }
}