<?php
namespace Lychee\Module\Recommendation\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;

class LastModifiedTest extends ModuleAwareTestCase {
    function test() {
        $s = $this->container()->get('lychee.module.recommendation.last_modified_manager');
        $d = $s->getLastModified('hots');
        var_dump($d);
        $s->updateLastModified('hots');
    }
}