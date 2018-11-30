<?php
namespace Lychee\Module\Notification\Tests;

use OldSound\RabbitMqBundle\RabbitMq\Producer;

use Lychee\Component\Test\ModuleAwareTestCase;

class AsyncPushTest extends ModuleAwareTestCase {
    public function test() {
        /** @var Producer $producer */
        $producer = $this->container()->get('old_sound_rabbit_mq.ciyo.push_producer');
        $producer->setContentType('application/json');
        $msg = '今晚食鸡肶，听晚食火腿，后晚食牛排，再然后乜都唔食。abcdefghi abcdefghi abcdefghi abcdefghi abcdefghi 后面的汉字是为了测试截断是否正确';
        $data = array("to" => array(31728, 57029), "msg" => $msg, "type" => "system", "pushBefore" => time() + 60);
        for ($i = 0; $i < 2; $i++) {
            $producer->publish(json_encode($data));
        }
    }
}