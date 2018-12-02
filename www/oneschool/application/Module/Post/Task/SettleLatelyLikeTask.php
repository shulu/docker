<?php
namespace Lychee\Module\Post\Task;
use Lychee\Module\Recommendation\Task\SettleTask;
use Lychee\Module\Like\Entity\PostLike;


class SettleLatelyLikeTask extends SettleTask {

    private $postLikes = [];

    /**
     * @return string
     */
    public function getName() {
        return 'settle-post-lately-likes';
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
        list($minId, $maxId) = $this->getSettleIdRange(
            PostLike::class, 'id', 'updateTime'
        );


        $conn = $this->getConnection();

        $sql = <<<'SQL'
  SELECT max(last_id) last_like_id  from like_post_period_count
SQL;
        $statement = $conn->executeQuery($sql);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        if (!empty($row['last_like_id'])) {
            $minId = $row['last_like_id'];
        }

        $limit = 1000;
        $cursorId = 1;
        // 精选次元才有强需求，所以目前只统计精选次元帖子的点赞数
        $sql = <<<'SQL'
  SELECT min(l.id) first_like_id, max(l.id) last_like_id, l.post_id, count(1) like_count FROM like_post l
    INNER JOIN post p ON l.post_id = p.id
    INNER JOIN recommendable_topic rt ON rt.topic_id = p.topic_id
    WHERE l.id > ? AND l.id <= ? AND l.state = 0
    AND l.post_id > ?
    AND l.liker_id != p.author_id
    AND p.deleted = 0
    GROUP BY l.post_id
    ORDER BY l.post_id ASC
    LIMIT ?
SQL;
        while (true) {
            $statement = $conn->executeQuery($sql, array($minId, $maxId, $cursorId, $limit),
                array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

            $this->postLikes = array_merge($this->postLikes, $result);
            $last = end($result);
            $cursorId = $last['post_id'];

            if (empty($result[$limit-1])) {
                break;
            }
        }

        $this->getLogger()->info('统计点赞数统计完毕');
    }

    private function updatePostLatelyLikes() {

        $this->getLogger()->info('点赞数统计表开始更新...');

        $conn = $this->getConnection();
        $sqlTpl = <<<'SQL'
INSERT INTO like_post_period_count (post_id, first_id, last_id, count) VALUES %s 
ON DUPLICATE KEY UPDATE 
count=if(last_id<values(last_id), count+VALUES(count), count), 
last_id=if(last_id<values(last_id), VALUES(last_id), last_id) 
SQL;
        $postLikes = $this->postLikes;
        $total = count($postLikes);
        $maxLikeIds = [];
        foreach ($postLikes as $key => $item) {
            $maxLikeIds[$key] = $item['last_like_id'];
        }
        array_multisort($maxLikeIds, SORT_ASC, $postLikes);

        $this->getLogger()->info('更新记录数: '.$total);

        while ($postLikes) {
            $list = array_splice($postLikes, 0, 10);
            $sql = vsprintf($sqlTpl, [implode(',', array_fill(0, count($list), '(?,?,?,?)'))]);

            $values = [];
            foreach ($list as $item) {
                $values = array_merge($values, [
                    $item['post_id'],
                    $item['first_like_id'],
                    $item['last_like_id'],
                    $item['like_count']
                ]);
            }
            $conn->executeUpdate($sql, $values);
        }

        $this->getLogger()->info('...更新完毕');
    }

    /**
     * @return void
     */
    public function run() {
        // 每天3点期间进行清表动作，停止统计，避免产生脏数据
        $hourInDay = idate('H');
        if (3 == $hourInDay) {
            $this->getLogger()->info('统计表正在维护，停止操作');
            return;
        }

        $this->settlePostLike();
        $this->updatePostLatelyLikes();
    }
} 