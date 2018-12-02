<?php
namespace Lychee\Module\Payment\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Payment\ThridParty\Wechat\WechatRequester;

class WechatTest extends ModuleAwareTestCase {

    public function test() {
        $r = new WechatRequester();
        $p = $r->unifiedorder('a', 'b', 123, '0.01', new \DateTime(), (new \DateTime())->modify('1 day'), 'url', '127.0.0.1');
        var_dump($p);
    }

}