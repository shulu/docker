<?php

namespace Lychee\Module\Post\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;

class FetchLatestTest extends ModuleAwareTestCase {
    public function test() {
        $result = $this->post()->fetchLatestIdsGroupByTopicId(array(25065, 25066), 4);
        var_dump($result);
        var_dump(call_user_func_array('array_merge', $result));
    }
}
 