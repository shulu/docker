<?php
namespace Lychee\Module\Topic\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Topic\TopicAnnouncementService;

class TopicAnnouncementTest extends ModuleAwareTestCase {

    /**
     * @return TopicAnnouncementService
     */
    private function getService() {
        return $this->container()->get('lychee.module.topic.announce');
    }

    public function test() {
        $service = $this->getService();
        $r = $service->announce(25068, 41015538412545, true);
        var_dump($r);
    }

}