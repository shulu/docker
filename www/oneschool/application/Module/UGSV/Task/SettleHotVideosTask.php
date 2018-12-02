<?php
namespace Lychee\Module\UGSV\Task;
use Lychee\Module\Recommendation\Task\SettleTask;
use Lychee\Module\Like\Entity\PostLike;


class SettleHotVideosTask extends SettleTask {


    private $postScores = [];

    public function postAddScore($postId, $score) {
        if (empty($postId) || empty($score)) {
            return;
        }
        if (isset($this->postScores[$postId])) {
            $this->postScores[$postId] += $score;
        } else {
            $this->postScores[$postId] = $score;
        }
    }


    /**
     * @return string
     */
    public function getName() {
        return 'settle-hot-videos';
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
        return new \DateInterval('P7D');
    }

    /**
     * @return Connection
     */
    private function getConnection() {
        return $this->em()->getConnection();
    }

    private function settlePostLike() {

        $this->getLogger()->info('开始统计点赞数...');
        $this->postScores = [];
        list($minId, $maxId) = $this->getSettleIdRange(
            PostLike::class, 'id', 'updateTime'
        );

        $conn = $this->getConnection();
        $limit = 1000;
        $cursorId = $minId;
        $sql = <<<'SQL'
  SELECT l.id, l.post_id, p.topic_id, IF(v.id IS NULL, 0, 1) as vip FROM like_post l
    INNER JOIN post p ON l.post_id = p.id
    INNER JOIN ugsv_post up ON up.post_id = p.id
    LEFT JOIN user_vip v ON p.author_id = v.user_id
    LEFT JOIN post_audit pa ON pa.post_id = p.id
    WHERE l.id > ? AND l.id <= ? AND l.state = 0
    AND l.liker_id != p.author_id
    AND p.deleted = 0
    AND (pa.status IS NULL OR pa.status=1)
    ORDER BY l.id ASC
    LIMIT ?
SQL;
        while (true) {
            $statement = $conn->executeQuery($sql, array($cursorId, $maxId, $limit),
                array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($result as $item) {
                $postScore = isset($item['vip']) && $item['vip'] == 1 ? 1.5 : 1;
                $this->postAddScore($item['post_id'], $postScore);
            }

            if (count($result) < $limit) {
                break;
            } else {
                $cursorId = $result[count($result) - 1]['id'];
            }
        }

        $this->getLogger()->info('总记录数:'.count($this->postScores));
        $this->getLogger()->info('...执行完毕');
    }

    private function filterPosts() {
        $this->getLogger()->info('开始按点赞数过滤...');
        $this->getLogger()->info('处理前记录数:'.count($this->postScores));
        foreach ($this->postScores as $postId => $score) {
            if ($score<5) {
                unset($this->postScores[$postId]);
            }
        }
        $this->getLogger()->info('处理后记录数:'.count($this->postScores));
        $this->getLogger()->info('...执行完毕');
    }

    private function sortPosts() {
        $this->getLogger()->info('开始排序...');
        arsort($this->postScores);
        $this->getLogger()->info('...执行完毕');
    }

    /**
     * @return \Predis\Client|\Redis
     */
    private function redis() {
        return $this->container()->get('snc_redis.recommendation_video');
    }

    public function getListKey() {
        return 'hot_videos';
    }

    /**
     * 删除在队列里不存在的帖子
     * @param $postIds
     */
    private function removeDeletedQueuePosts($postIds) {
        if (empty($postIds)) {
            return false;
        }
        $conn = $this->getConnection();
        $sql = 'SELECT id from post where id in ('.implode(',', $postIds).') and deleted=1';
        $statement = $conn->executeQuery($sql);
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $redis = $this->redis();
        $listkey = $this->getListKey();
        foreach ($result as $item) {
            $redis->lRem($listkey, 0, $item['id']);
        }
        return true;
    }


    private function updatePostsQueue() {
        $this->getLogger()->info('开始更新数据源...');
        $redis = $this->redis();
        $postIds = array_keys($this->postScores);
        $listkey = $this->getListKey();
        $maxLen = 450;
        $pushPostIds = $postIds;
        if (isset($postIds[$maxLen])) {
            $pushPostIds = array_splice($postIds, 0, $maxLen);
        }

        // 每次追加新帖子到热门池，追加完后去重处理
        $maxIndex = $maxLen - 1;
        $oldPostIds = [];
        try {
            if (empty($pushPostIds[$maxIndex])) {
                $oldPostIds = $redis->lrange($listkey, 0, -1);
                $oldPostIds = array_diff($oldPostIds, $pushPostIds);
                $pushPostIds = array_merge($pushPostIds, $oldPostIds);
                $pushPostIds = array_slice($pushPostIds, 0, $maxLen);
            }

            if (empty($pushPostIds[$maxIndex])) {
                $maxLen = count($pushPostIds);
                $maxIndex = $maxLen - 1;
            }

        } catch (\Exception $e) {
            $this->getLogger()->info($e->__toString());
        }

        $pushPostIds = array_reverse($pushPostIds);

        try {
            $this->getLogger()->info('删除队列里的废弃帖子');
            $this->removeDeletedQueuePosts($oldPostIds);
        } catch (\Exception $e) {
            $this->getLogger()->info($e->__toString());
        }

        $this->getLogger()->info('记录数:'.count($pushPostIds));

        try {
            while ($pushPostIds) {
                $postIds = array_splice($pushPostIds, 0, 200);
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
        $this->settlePostLike();
        $this->filterPosts();
        $this->sortPosts();
        $this->updatePostsQueue();
    }
} 