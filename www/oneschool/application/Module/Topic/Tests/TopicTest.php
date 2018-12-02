<?php
namespace Lychee\Module\Topic\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;

class TopicTest extends ModuleAwareTestCase {

    public function testUpdateManager() {
        $this->topic()->updateManager(25071, 31722);
    }
    
}