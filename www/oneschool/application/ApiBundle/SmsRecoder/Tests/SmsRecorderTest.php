<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 12/12/2016
 * Time: 11:04 PM
 */

namespace Lychee\Bundle\ApiBundle\SmsRecorder\Tests;


use Lychee\Bundle\ApiBundle\SmsRecoder\SmsRecorder;
use Lychee\Component\Test\ModuleAwareTestCase;

class SmsRecorderTest extends ModuleAwareTestCase {

    public function testSmsDuplicate() {
        /** @var SmsRecorder $recorder */
        $recorder = $this->container()->get('lychee_api.sms_recorder');
        $phone = '18512345678';
	    $ip = '123.123.123.123';
	    $result = $recorder->isDuplicateRecord($phone, $ip);
        var_dump($result);
//        $this->assertFalse($recorder->isDuplicateRecord($phone));
        !$result && $recorder->record($ip, '86', $phone, 'android', '5.1.1', '2.7.1', null);
        var_dump($recorder->isDuplicateRecord($phone, '111.111.111.111'));
//        $this->assertTrue($recorder->isDuplicateRecord($phone));
    }


    public function testTryRecordFalse() {
        $recorder = $this->container()->get('lychee_api.sms_recorder');
        $phone = mt_rand(10000000000, 99999999999);
        $ip = '123.123.123.123';
        $result = false;
        for ($i = 0; $i<2; $i++) {
            $result = $recorder->tryRecord($phone, $ip);
        }
        $this->assertFalse($result);
    }


    public function testTryRecordTrue() {
        $recorder = $this->container()->get('lychee_api.sms_recorder');
        $phone = mt_rand(10000000000, 99999999999);
        $ip = '123.123.123.123';
        $result = $recorder->tryRecord($phone, $ip);
        $this->assertTrue($result);
    }

}