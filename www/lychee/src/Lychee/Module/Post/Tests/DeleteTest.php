<?php
namespace Lychee\Module\Post\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;


class DeleteTest extends ModuleAwareTestCase {

    public function test() {
        $this->post()->undelete(28034367534081);
    }
    
}