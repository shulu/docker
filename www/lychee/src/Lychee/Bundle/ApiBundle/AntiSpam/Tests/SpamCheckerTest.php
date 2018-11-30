<?php
namespace Lychee\Bundle\ApiBundle\AntiSpam\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Bundle\ApiBundle\AntiSpam\SpamChecker;
use Lychee\Bundle\ApiBundle\AntiSpam\SpammerRecorder;

class SpamCheckerTest extends ModuleAwareTestCase {

    public function test() {
//        /** @var SpamChecker $checker */
//        $checker = $this->container()->get('lychee_api.spam_checker');
//        $isSpam = $checker->check(31728, SpamChecker::ACTION_POST, $reduplicate);
//        var_dump($isSpam, $reduplicate);

        /** @var SpammerRecorder $recorder */
        $recorder = $this->container()->get('lychee_api.spammer_recorder');
        $recorder->record(31728);

        $r = $recorder->getSpammers(6, 1, $nextCursor);
        var_dump($r);
        var_dump($nextCursor);
    }
    
}