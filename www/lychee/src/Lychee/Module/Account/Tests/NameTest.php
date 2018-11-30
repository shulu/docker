<?php
namespace Lychee\Module\Account\Tests;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Account\NicknameGenerator;

class NameTest extends ModuleAwareTestCase {

    public function test() {
        $u = $this->account()->createWithPhone(18620606041, 88);
        var_dump($u);
//        $nameGenerator = new NicknameGenerator();
//        for ($i = 0; $i < 100; ++$i) {
//            $name = $nameGenerator->generate();
//            print_r($name."\n");
//            assert(strlen($name) <= 36);
//        }
    }
    
}