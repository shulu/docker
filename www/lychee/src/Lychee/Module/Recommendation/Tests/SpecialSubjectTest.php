<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 4/8/15
 * Time: 12:17 PM
 */

namespace Lychee\Module\Recommendation\Tests;


use Lychee\Component\Test\ModuleAwareTestCase;

class SpecialSubjectTest extends ModuleAwareTestCase {

    public function testFetchByPage() {
        $page = 1;
        $result = $this->specialSubject()->fetchByPage($page);
//        print_r($result);
    }

    public function testFetchPrevious() {
        $id = 5;
        $result = $this->specialSubject()->fetchPrevious($id);
        $this->assertEquals('6', $result->getId());
//        echo PHP_EOL . get_class($result) . PHP_EOL;
    }

    public function testFetchNext() {
        $id = 5;
        $result = $this->specialSubject()->fetchNext($id);
        $this->assertEquals('4', $result->getId());
//        echo PHP_EOL . get_class($result) . PHP_EOL;
    }

    public function testFetchByCursor() {
        $result = $this->specialSubject()->fetchByCursor(0, 2, $nextCursor);
        var_dump($result);
        $result = $this->specialSubject()->fetchByCursor($nextCursor, 2, $nextCursor);
        var_dump($result);
    }

    public function testFetch() {
        $ids = range(1, 100);
        $result = $this->specialSubject()->fetch($ids);
        $this->assertTrue(is_array($result));
    }
}