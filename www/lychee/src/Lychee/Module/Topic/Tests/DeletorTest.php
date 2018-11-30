<?php
namespace Lychee\Module\Topic\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Topic\Deletion\Deletor;
use Lychee\Module\Topic\Deletion\DeferDeletor;

class DeletorTest extends ModuleAwareTestCase {

    /**
     * @return DeferDeletor
     */
    private function getDeletor() {
        return $this->container()->get('lychee.module.topic.deletor');
    }

    public function test() {
        $this->getDeletor()->delete(25068);
//        $this->getDeletor()->clearTopic(25068);
    }
    
}