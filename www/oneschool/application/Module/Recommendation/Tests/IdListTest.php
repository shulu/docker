<?php

namespace Lychee\Module\Recommendation\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Recommendation\IdList;
use Lychee\Module\Recommendation\RecommendationType;

class IdListTest extends ModuleAwareTestCase {

    /**
     * @return \Predis\Client|\Redis
     */
    private function redis() {
        return $this->container()->get('snc_redis.recommendation');
    }

//    public function testAdd() {
//        $idList = new IdList($this->redis(), 'test');
//        $idList->update(array(11, 12, 13, 14, 15));
//
//        $iterator = $idList->getIterator();
//        var_dump( $iterator->setCursor(4)->setStep(4)->current() );
//        var_dump( $iterator->getNextCursor() );
//
//        $idList->removeIds(array(14, 15, 16, 17));
//
//        $iterator = $idList->getIterator();
//        var_dump( $iterator->setCursor(0)->setStep(4)->current() );
//        var_dump( $iterator->getNextCursor() );
//    }

    public function testExclusion() {
        $idList = new IdList($this->redis(), 'test');
        $idList->update(array(11, 12, 13, 14, 15, 16, 17, 18, 19, 20));
        $it = $idList->getIterator(array(13, 18, 19));
        var_dump($it->setCursor(7)->setStep(3)->current());
        var_dump($it->getNextCursor());
        $this->redis()->del('test');
    }

    public function testDelete() {
//        $this->post()->delete($this->post()->fetchOne(9116930156545));
//        $this->recommendation()->getHotestIdList(RecommendationType::POST)->removeIds(array(9116930156545));
    }

}
 