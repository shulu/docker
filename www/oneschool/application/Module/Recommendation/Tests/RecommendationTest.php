<?php

namespace Lychee\Module\Recommendation\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Recommendation\RecommendationType;

class RecommendationTest extends ModuleAwareTestCase {

    public function testFetchAppsByCursor() {
        $cursor = 0;
        $count = 5;
        $result = $this->recommendation()->fetchAppsByCursor($cursor, $count);
        $this->assertTrue(is_array($result));
        var_dump($result);
    }

}
 