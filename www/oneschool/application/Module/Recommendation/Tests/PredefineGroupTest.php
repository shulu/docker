<?php

namespace Lychee\Module\Recommendation\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Recommendation\RecommendationType;

/**
 * @coversDefaultClass \Lychee\Module\Recommendation\Post\PredefineGroup
 */
class PredefineGroupTest extends ModuleAwareTestCase {


    /**
     * 验证分组做了关联后，主分组需要标记排除的次元id，来源于子分组包含的次元
     * ，
     * 在 \Lychee\Module\Recommendation\Post\GroupManager::getSubGroupIds
     * 做了关联后起效
     *
     * @covers ::randomListPostIdsInGroup
     */
    public function testMainGroupToExcludeSubGroupTopics() {

        $groups = \Lychee\Module\Recommendation\Post\PredefineGroup::groups();
        $subGroupIds= \Lychee\Module\Recommendation\Post\GroupManager::getSubGroupIds(\Lychee\Module\Recommendation\Post\PredefineGroup::ID_JINGXUAN);
        $topicIds = [];
        foreach ($subGroupIds as $subGroupId) {
            $topicIds = array_merge($groups[$subGroupId]->getTopicIds(), $topicIds);
        }

        $r = $groups[\Lychee\Module\Recommendation\Post\PredefineGroup::ID_JINGXUAN];
        $r = $r->resolver();
        $r = $r->getExcludeTopicIds();

        $this->assertTrue(empty(array_diff($r, $topicIds))
            &&empty(array_diff($topicIds, $r)));

    }

}
 