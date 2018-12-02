<?php
namespace Lychee\Module\Relation\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;

class BlackListTest extends ModuleAwareTestCase {

    public function test() {
        $ids = $this->relation()->userBlackListFilterNoBlocking(array(31759, 31722, 31728), 262797);
        var_dump($ids);
    }
    
}