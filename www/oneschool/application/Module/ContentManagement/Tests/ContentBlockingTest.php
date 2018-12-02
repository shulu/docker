<?php
namespace Lychee\Module\ContentManagement\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\ContentManagement\ContentBlockingService;

class ContentBlockingTest extends ModuleAwareTestCase {


    public function test() {
        $cbs = new ContentBlockingService($this->container()->get('doctrine'));
        $topics = array(25065, 25066, 25067);
        $cbs->updateBlockingTopicIdsByChannel('baidu', '1.4.5', $topics);
        $result = $cbs->getBlockingTopicIdsByChannel('baidu', '1.4.5');
        $this->assertEquals($topics, $result, 'blocking topics must equal.');
        $result2 = $cbs->listBlockingTopics(0, 10, $n);
        var_dump($result2);
    }
    
}