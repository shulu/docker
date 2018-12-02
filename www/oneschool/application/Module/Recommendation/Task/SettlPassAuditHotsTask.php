<?php
namespace Lychee\Module\Recommendation\Task;

use Doctrine\DBAL\Connection;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Recommendation\Post\GroupManager;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Lychee\Component\Task\Task;

class SettlPassAuditHotsTask implements Task {
    use ContainerAwareTrait;
    use ModuleAwareTrait;
    /**
     * @return string
     */
    public function getName() {
        return 'settle-pass-audit-hots';
    }

    /**
     * @return EntityManager
     */
    protected function em() {
        return $this->container->get('doctrine')->getManager();
    }
    /**
     * @return integer
     */
    public function getDefaultInterval() {
        return 10 * 60;
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger() {
        return $this->container->get('monolog.logger.task');
    }
    /**
     * @return Connection
     */
    private function getConnection() {
        return $this->em()->getConnection();
    }

    /**
     * @return \Predis\Client|\Redis
     */
    private function redis() {
        return $this->container()->get('snc_redis.recommendation_cache');
    }

    private function filterPassAuditPosts($groupId) {


        $gm = $this->container->get('lychee.module.recommendation.group_manager');
        $g = $gm->getGroupById($groupId);


        $this->getLogger()->info('开始筛选分组 [ '.$g->name().' ('.$groupId.') ] 里的审核后帖子...');
        $groupIds = [$groupId];
        $subGroupIds = GroupManager::getSubGroupIds($groupId);
        if ($subGroupIds) {
            $groupIds = array_merge($groupIds, $subGroupIds);
        }
        $maxLen = 0;
        foreach ($groupIds as $id) {
            $maxLen += $this->container->get('lychee.module.recommendation.group_posts')->getMaxLenByGroup($id);
        }
        $sql = 'SELECT gp.post_id 
				FROM rec_group_posts gp
				INNER JOIN post p ON p.id=gp.post_id
                LEFT JOIN post_audit pa ON pa.post_id = p.id
                WHERE p.deleted=0
                AND (pa.status IS NULL OR pa.status=1)
				AND group_id in (';
        $sql .= implode(',', array_fill(0, count($groupIds), '?'));
        $sql .= ')';

        $sqlParams = $groupIds;
        $stat = $this->getConnection()->executeQuery($sql, $sqlParams);
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        $pushPostIds = ArrayUtility::columns($rows, 'post_id');
        $pushPostIds  = array_unique($pushPostIds);

        $this->getLogger()->info('符合条件的记录数：'.count($pushPostIds));

        $this->getLogger()->info('...处理完毕');

        $this->putPostsToQueue($groupId, $pushPostIds, $maxLen);
    }

    private function putPostsToQueue($groupId, $pushPostIds, $maxLen) {
        if (empty($pushPostIds)) {
            return false;
        }
        $this->getLogger()->info('开始更新缓存...');
        $redis = $this->redis();
        $listkey = 'pass_audit_hot_tab_posts:'.$groupId;
        $maxIndex = $maxLen-1;
        shuffle($pushPostIds);
        while ($pushPostIds) {
            $postIds = array_splice($pushPostIds, 0, 200);
            $params = [];
            $params[] = $listkey;
            $params = array_merge($params, $postIds);
            call_user_func_array([$redis, 'lpush'], $params);
        }
        $redis->lTrim($listkey, 0, $maxIndex);
        $this->getLogger()->info('...处理完毕');
    }

    /**
     * @return void
     */
    public function run() {
        //这里是通过判断当前时间来"空转"结算程序。
        $hourInDay = idate('H');
        if (2 <= $hourInDay && $hourInDay <= 8) {
            $this->getLogger()->info('no neccessary to run settle hot task from 2.am to 8.am every day.');
            return;
        }

        $gm = $this->container->get('lychee.module.recommendation.group_manager');
        $groupIds = $gm->getGroupIdsToShow();
        foreach ($groupIds as $groupId) {
            try {
                $this->filterPassAuditPosts($groupId);
            } catch (\Exception $e) {
                $this->getLogger()->error($e->__toString());
            }
        }

        $this->container()->get('lychee.module.recommendation.last_modified_manager')->updateLastModified('hots');
    }


}