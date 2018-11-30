<?php
namespace Lychee\Module\Topic\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Topic\TopicCategoryService;

class CategoryTest extends ModuleAwareTestCase {

    /**
     * @return TopicCategoryService
     */
    private function getCategoryService() {
        return new TopicCategoryService($this->container()->get('doctrine'), null);
    }

    public function test() {
//        $cs = $this->getCategoryService();
//
//        $cs->addCategory('动漫');
//        $cs->addCategory('游戏');
//        $cs->addCategory('小说');
//        $cs->addCategory('Cos');
//        $cs->addCategory('抱图');
//        $cs->addCategory('人物');
//        $cs->addCategory('兴趣');
//        $cs->addCategory('展会');
//        $cs->addCategory('生活');
//        $cs->addCategory('地区');
//        $cs->addCategory('社团');
//        $cs->addCategory('逗比');
//        $cs->addCategory('其他');
//
//        $cs->categoryAddTopic('动漫', 31722);
//        $cs->categoryAddTopic('动漫', 31723);
//        $cs->categoryAddTopic('动漫', 31724);
//        $r = $cs->topicIdsInCategory('动漫', 2, 2, $nextCursor);
//        $cs->categoryRemoveTopic('动漫', 31722);
//        $cs->categoryRemoveTopic('动漫', 31723);
//        $cs->categoryRemoveTopic('动漫', 31724);
//        var_dump($r);
//        var_dump($nextCursor);

        $cs = $this->getCategoryService();
        $result = $cs->categoriesByTopicIds(array());
        var_dump($result);
    }
    
}