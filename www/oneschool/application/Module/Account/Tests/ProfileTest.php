<?php

namespace Lychee\Module\Account\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;

class ProfileTest extends ModuleAwareTestCase {

    public function test() {
        $r = $this->account()->fetch(array(31728));
        var_dump($r);
    }

}
