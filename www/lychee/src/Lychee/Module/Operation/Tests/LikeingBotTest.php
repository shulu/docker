<?php
namespace Lychee\Module\Operation\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Operation\LikingBot\LikingBot;

class LikeingBotTest extends ModuleAwareTestCase {
    public function test() {
        $bot = new LikingBot($this->container()->get('doctrine'),
            $this->container()->get('lychee.module.like'), $this->container()->get('logger'));
        $bot->run(60 * 5);
    }
}