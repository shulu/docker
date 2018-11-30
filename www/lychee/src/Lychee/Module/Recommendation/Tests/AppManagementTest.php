<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 4/8/15
 * Time: 12:17 PM
 */

namespace Lychee\Module\Recommendation\Tests;


use Lychee\Component\Test\ModuleAwareTestCase;

class AppManagementTest extends ModuleAwareTestCase {

    public function testFetch() {
        $ids = range(1, 100);
        $result = $this->game()->fetch($ids);
        $this->assertTrue(is_array($result));
    }

    public function testAppCursor() {
        $result = $this->game()->fetchByCursor(0, 1, $nextCursor);
        var_dump($result);
        var_dump($nextCursor);
    }
}