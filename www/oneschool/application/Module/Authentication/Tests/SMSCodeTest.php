<?php
namespace Lychee\Module\Authentication\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Authentication\PhoneVerifier;

class SMSCodeTest extends ModuleAwareTestCase {

    private function getDoctrine() {
        return $this->container()->get('doctrine');
    }

    public function test() {
        $verifier = new PhoneVerifier($this->getDoctrine());
//        $r = $verifier->sendCode(86, '18620606032');
        $r = $verifier->verify(86, '18620606032', '309102');
        var_dump($r);
    }
    
}