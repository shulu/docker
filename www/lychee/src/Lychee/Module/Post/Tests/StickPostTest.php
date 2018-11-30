<?php

namespace Lychee\Module\Post\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;

/**
 * @group Post
 */
class StickPostTest extends ModuleAwareTestCase {
    public function test() {
        $this->post()->stickPost(4520555642881);
        $this->post()->stickPost(4510913084417);
        $result = $this->post()->fetchStickyPostIds(25065);
        $this->assertEquals(array(4510913084417, 4520555642881), $result);
        $this->post()->unstickPost(4510913084417);
        $this->post()->unstickPost(4520555642881);
    }
}
 