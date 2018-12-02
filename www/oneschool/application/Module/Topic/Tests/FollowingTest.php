<?php
namespace Lychee\Moudle\Topic\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Topic\Following\TopicFollowingService;

class FollowingTest extends ModuleAwareTestCase {

    /**
     * @return TopicFollowingService
     */
    public function getFollowingService() {
        return $this->container()->get('lychee.module.topic.following');
    }

    public function test() {
        $fs = $this->getFollowingService();
//        for ($i = 25065; $i < 25100; ++$i) {
//            $fs->follow(31728, $i, $followBefore);
//            var_dump($followBefore);
//        }
//        $fs->setFavorite(31728, 25068);
//        $fs->setFavorite(31728, 25098);
//        $fs->setFavorite(31728, 25070);
//        $fs->setFavorite(31728, 25088);

//        $itor = $fs->getUserFolloweeIterator(31728);
//        $itor->setStep(2);
//        foreach ($itor as $topicIds) {
//            var_dump($topicIds);
//        }

//        $itor = $fs->getTopicFollowerIterator(25068);
//        foreach ($itor as $topicIds) {
//            var_dump($topicIds);
//        }

//        $counter = $fs->getUsersFollowingCounter(array(31728, 31722));
//        var_dump($counter->getCount(31728));
//        var_dump($counter->getCount(31722));
//        var_dump($counter->getCount(31726));

//        $topicIds = array(25065, 25064, 25099, 25100, 25068);
//        $resolver = $fs->getUserFollowingResolver(31728, $topicIds);
//        foreach ($topicIds as $topicId) {
//            var_dump($topicId);
//            var_dump($resolver->isFollowing($topicId));
//            var_dump($resolver->isFavorite($topicId));
//        }
//        var_dump(25200);
//        var_dump($resolver->isFollowing(25200));
//        var_dump($resolver->isFavorite(25200));

    }

    public function test1() {
        $fs = $this->getFollowingService();
//        $fs->follow(396190, 32042);
//        $fs->follow(396192, 32042);
//        $fs->follow(396193, 32042);
//        $fs->follow(396194, 32042);
//        $fs->follow(396195, 32042);
        $r = $fs->filterTopicFollower(25068, array(24000, 31728, 31729, 31722));
        var_dump($r);
    }
    
}