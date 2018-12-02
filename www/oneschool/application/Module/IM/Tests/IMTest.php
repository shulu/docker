<?php
namespace Lychee\Module\IM\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\IM\IMService;
use Lychee\Module\IM\Message;

class IMTest extends ModuleAwareTestCase {

    public function test() {

        $m1 = new Message();
        $m1->from = 31728;
        $m1->to = 31722;
        $m1->time = time();
        $m1->type = 0;
        $m1->body = 'IM Test';

        $m2 = new Message();
        $m2->from = 31722;
        $m2->to = 31728;
        $m2->time = time();
        $m2->type = 0;
        $m2->body = 'IM Test2';

        $messages = array($m1, $m2);
        /** @var IMService $im */
        $im = $this->container()->get('lychee.module.im');
        $im->dispatchFollowing(31728, 31722, time());
    }
    
}