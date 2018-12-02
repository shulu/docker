<?php
namespace Lychee\Module\IM\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\IM\GroupService;

class GroupTest extends ModuleAwareTestCase {

    public function test() {
        /** @var GroupService $group */
        $group = $this->container()->get('lychee.module.im.group');

//        for ($i = 1; $i < 20; ++ $i) {
//            $group->kickout(8, 31728, $i);
//        }
//        $group->join(80, 31728);
//        $group->join(83, 31728);
//        $r = $group->getGroupsByUserInTopic(31728, 25068, 0, 30, $nextCursor);
//        var_dump($r, $nextCursor);
//        $group->leaveGroupsOfTopic(31728, 25068);
//        sleep(2);
//        $r = $group->getGroupsByUserInTopic(31728, 25068, 0, 30, $nextCursor);
//        var_dump($r, $nextCursor);
        $g = $group->create(31728, ' 吃草 ', null, '描述鸡鸡 ');
        var_dump($g);
    }
    
}