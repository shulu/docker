<?php

namespace Lychee\Module\Recommendation\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;

class TopicTestTest extends ModuleAwareTestCase {
    public function test() {
        $result = $this->recommendation()->getTopicIdsByTestScore(9, 9, 9, 9, 9, 9);
        var_dump($result);
    }
}
 