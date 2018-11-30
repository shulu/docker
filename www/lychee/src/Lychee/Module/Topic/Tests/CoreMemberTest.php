<?php
namespace Lychee\Module\Topic\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Topic\CoreMember\TopicCoreMemberService;

class CoreMemberTest extends ModuleAwareTestCase {

    private function getCoreMemberService() {
        return new TopicCoreMemberService($this->container()->get('doctrine'));
    }

    public function test() {
        $service = $this->getCoreMemberService();
//        $service->updateOrder(25068, array(31722, 31724, 31723, 31725));
        $service->addCoreMember(25068, 31722, '果吹');
        $r = $service->getCoreMembers(25068);
        var_dump($r);
        $r = $service->getTitleResolver(array(
            array(25068, 31722), array(25068, 31723), array(25068, 31723), array(25068, 31725)));
        var_dump($r->resolve(25068, 31722));
        var_dump($r->resolve(25068, 31723));
        var_dump($r->resolve(25068, 31724));
        var_dump($r->resolve(25068, 31725));
        var_dump($r->resolve(25069, 31722));
        
        var_dump($service->isCoreMember(25068, 31723));
    }
    
}