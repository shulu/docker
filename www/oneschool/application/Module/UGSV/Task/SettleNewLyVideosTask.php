<?php
namespace Lychee\Module\UGSV\Task;
use Lychee\Module\Recommendation\Task\SettleTask;
use Lychee\Bundle\CoreBundle\Entity\Post;


class SettleNewLyVideosTask extends SettleTask {


    private $postIds = [];

    /**
     * @return string
     */
    public function getName() {
        return 'settle-newly-videos';
    }

    /**
     * @return integer
     */
    public function getDefaultInterval() {
        return 10 * 60;
    }

    /**
     * @return \DateInterval
     */
    public function getSettleInterval() {
        return new \DateInterval('P2D');
    }

    /**
     * @return Connection
     */
    private function getConnection() {
        return $this->em()->getConnection();
    }


    private function settlePost() {
        $this->getLogger()->info('开始按发布时间查询...');
        list($minId, $maxId) = $this->getSettleIdRange(
            Post::class, 'id', 'createTime'
        );

        $this->postIds = [];

        $conn = $this->getConnection();

        $cursorId = $maxId;
        $limit = 1000;
        while (true) {
            $statement = $conn->executeQuery('
              SELECT p.id
              FROM post p
              INNER JOIN ugsv_post up ON p.id = up.post_id
              LEFT JOIN post_audit pa ON pa.post_id = p.id
              WHERE p.id > ? AND p.id <= ? AND p.deleted = 0
              AND (pa.status IS NULL OR pa.status=1)
              ORDER BY p.id DESC
              LIMIT '.$limit, array($minId, $cursorId));
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

           foreach ($result as $item) {
               $this->postIds[] = $item['id'];
           }

           if (empty($result[$limit-1])) {
               break;
           }

           $cursorId = $result[$limit - 1]['id'];
        }

        $this->getLogger()->info('...执行完毕');
    }

    private function filterPosts() {
        $this->getLogger()->info('开始将热门短视频排除在外...');
        $this->getLogger()->info('处理前记录数: '. count($this->postIds));
        $redis = $this->redis();
        $hottestPostIds = $redis->lrange('hottest', 0, -1);
        $this->postIds = array_diff($this->postIds, $hottestPostIds);
        $this->getLogger()->info('处理后记录数: '. count($this->postIds));
        $this->getLogger()->info('...执行完毕');
    }

    /**
     * @return \Predis\Client|\Redis
     */
    private function redis() {
        return $this->container()->get('snc_redis.recommendation_video');
    }

    public function getListKey() {
        return 'newly_videos';
    }

    private function updatePostsQueue() {
        $this->getLogger()->info('开始更新最新短视频列表...');
        $redis = $this->redis();
        $listkey = $this->getListKey();
        $pushPostIds = $this->postIds;
        $maxIndex = count($pushPostIds)-1;
        $pushPostIds = array_reverse($pushPostIds);

        $this->getLogger()->info('记录数: '.count($pushPostIds));
        try {
            $chunkPostIds  = array_chunk($pushPostIds, 500);
            foreach ($chunkPostIds as $postIds) {
                $params = [];
                $params[] = $listkey;
                $params = array_merge($params, $postIds);
                call_user_func_array([$redis, 'lpush'], $params);
            }
            $redis->lTrim($listkey, 0, $maxIndex);
        } catch (\Exception $e) {
            $this->getLogger()->info($e->__toString());
        }
        $this->getLogger()->info('...执行完毕');
    }

    /**
     * @return void
     */
    public function run() {
        $this->settlePost();
        $this->filterPosts();
        $this->updatePostsQueue();
    }
} 