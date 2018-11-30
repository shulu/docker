<?php
namespace Lychee\Module\Schedule\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Schedule\ScheduleService;
use Symfony\Component\Validator\Constraints\Date;

class ScheduleTest extends ModuleAwareTestCase {

    public function test() {
        /** @var ScheduleService $service */
        $service = $this->container()->get('lychee.module.schedule');
//        $schedule = $service->create(1, 'test schedule', null, null, null, null, new \DateTime(), new \DateTime());
//        var_dump($schedule);
//
//        $service->join(31728, $schedule->id);
//
//        $joiners = $service->getJoinerIds($schedule->id, 0, 100);
//
//        var_dump($joiners);
//        $itor = $service->getJoinerIdIterator(1);
//        $itor->setStep(90);
//
//        foreach ($itor as $joinerIds) {
//            var_dump($joinerIds);
//        }
        $from = new \DateTime('2015-12-9 00:00:00');
        $to = new \DateTime('2015-12-14 00:00:00');
        $sids = $service->fetchIdsByStartTimeBetween($from, $to);
        var_dump($sids);
    }
    
}