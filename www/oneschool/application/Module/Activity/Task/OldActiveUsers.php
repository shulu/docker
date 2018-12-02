<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 15-3-4
 * Time: 下午3:46
 */

namespace Lychee\Module\Activity\Task;


use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\KVStorage\MemcacheStorage;
use Lychee\Component\Task\Task;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class OldActiveUsers implements Task {
    use ModuleAwareTrait;
    use ContainerAwareTrait;

    public function getName() {
        return 'old_active_users';
    }

    public function getDefaultInterval() {
        return 4 * 3600;
    }

    public function run() {
        $now = getdate();
        if ($now['hours'] >= 2 && $now['hours'] < 6) {
            $period = 7;
            $count = 100;
            $userIds = $this->activity()->getActiveUsers($period, $count);
            $resultSet = [];
            foreach ($userIds as $userId) {
                $result = [];
                $result['userId'] = $userId;
                $user = $this->account()->fetchOne($userId);
                $result['nickname'] = $user->nickname;
                $result['createTime'] = $user->createTime;
                $result['gender'] = $user->gender;
                $profile = $this->account()->fetchOneUserProfile($userId);
                $result['skills'] = $profile->skills;
                $result['signInMethods'] = $this->account()->signInMethod($userId);
                $result['followees'] = $this->relation()->countUserFollowees($userId);
                $result['followers']  = $this->relation()->countUserFollowers($userId);
                $result['topics'] = $this->topicFollowing()->getUsersFollowingCounter(array($userId))->getCount($userId);
                $userCounting = $this->account()->fetchOneCounting($userId);
                $result['posts'] = $userCounting->postCount;
                $result['imageComments'] = $userCounting->imageCommentCount;
                $resultSet[] = $result;
            }
            $memcache = new MemcacheStorage($this->container()->get('memcache.default'), '');
            $memcache->set('old_active_users', $resultSet);
        }
    }
}