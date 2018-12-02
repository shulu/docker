<?php
namespace Lychee\Module\Recommendation\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;

class CategoryHotTest extends ModuleAwareTestCase {


    public function test() {
        $topicIds = array();

//        $nextCursor = 0;
//        do {
//            $cursor = $nextCursor;
//            $r = $this->recommendation()->listTopicIdsInCategoryByHotOrder(301, $cursor, 20, $nextCursor);
//            $topicIds = array_merge($topicIds, $r);
//        } while ($nextCursor !== 0);
//
//        $nextCursor = 0;
//        do {
//            $cursor = $nextCursor;
//            $r = $this->recommendation()->listTopicIdsInCategoryWithoutHot(301, $cursor, 20, $nextCursor);
//            $topicIds = array_merge($topicIds, $r);
//        } while ($nextCursor !== 0);

        var_dump(count($topicIds));
        var_dump(count(array_unique($topicIds)));
    }

}