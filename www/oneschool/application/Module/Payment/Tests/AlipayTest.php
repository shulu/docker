<?php
namespace Lychee\Module\Payment\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Payment\ThridParty\Alipay\AlipayRequester;

class AlipayTest extends ModuleAwareTestCase {

    public function testSign() {
        $params = array(
            'a' => '123',
        );
        $key = 'file://'.__DIR__.'/../ThridParty/Alipay/private.pem';
        $requester = new AlipayRequester();
        echo $requester->queryOrderRequest();
    }

}