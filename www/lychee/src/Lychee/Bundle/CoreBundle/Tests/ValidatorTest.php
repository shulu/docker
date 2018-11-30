<?php
namespace Lychee\Bundle\CoreBundle\Tests;

use Lychee\Bundle\CoreBundle\Validator\Constraints\ReservedWord;
use Lychee\Bundle\CoreBundle\Validator\Constraints\Password;
use Lychee\Component\Test\ModuleAwareTestCase;

class ValidatorTest extends ModuleAwareTestCase {

    public function test() {
//        $errors = $this->container()->get('validator')->validate('次元/|/[a]社aa我日mikO酱', [new ReservedWord()]);
//        var_dump($errors);
    }


    public function testPassword() {
        $validateList = [new Password()];
        $validator = $this->container()->get('validator');
        $errors = $validator->validate('123', $validateList);
        $this->assertNotEmpty($errors, '少于8位的密码通过了');

        $errors = $validator->validate('a1234567890111111', $validateList);
        $this->assertNotEmpty($errors, '多于16位的密码通过了');

        $errors = $validator->validate('123456789011', $validateList);
        $this->assertNotEmpty($errors, '纯数字的密码通过了');

        $errors = $validator->validate('aaaaaaaaaaaa', $validateList);
        $this->assertNotEmpty($errors, '纯字母的密码通过了');

        $errors = $validator->validate('a12345678', $validateList);
        $this->assertEmpty($errors, '数字与字母混合的密码没通过');

        $errors = $validator->validate('12345678a', $validateList);
        $this->assertEmpty($errors, '数字与字母混合的密码没通过');


        $errors = $validator->validate('1234a5678', $validateList);
        $this->assertEmpty($errors, '数字与字母混合的密码没通过');

    }

}