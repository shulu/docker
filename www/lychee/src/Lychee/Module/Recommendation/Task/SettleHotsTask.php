<?php
namespace Lychee\Module\Recommendation\Task;

use Doctrine\DBAL\Connection;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Foundation\CursorableIterator\ArrayCursorableIterator;
use Lychee\Module\Like\Entity\PostLike;
use Lychee\Module\Post\Entity\PostExposureRecord;
use Lychee\Module\Recommendation\Post\PredefineGroup;
use Lychee\Module\Recommendation\Post\SettleProcessor;
use Lychee\Module\Recommendation\Settle\SettleContext;
use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Module\Recommendation\RecommendationType;

class SettleHotsTask extends SettleTask {
    /**
     * @return string
     */
    public function getName() {
        return 'settle-hots';
    }

    /**
     * @return integer
     */
    public function getDefaultInterval() {
        return 20 * 60;
    }

    /**
     * @return \DateInterval
     */
    public function getSettleInterval() {
//        return new \DateInterval('PT24H');
        return new \DateInterval('P7D');
    }

//    /**
//     * @return \DateTime
//     */
//    protected function getSettleUpperTime() {
//        $upperTime = new \DateTime('2016-10-07');
//        $upperTime->sub(new \DateInterval('PT1S'));
//        return $upperTime;
//    }

    /**
     * @return Connection
     */
    private function getConnection() {
        return $this->em()->getConnection();
    }

    /**
     * @return void
     */
    public function run() {
        //每日凌晨2点至早上8点, 不执行实际的结算。
        //这里是通过判断当前时间来"空转"结算程序。
        $hourInDay = idate('H');
        if (2 <= $hourInDay && $hourInDay <= 8) {
            $this->getLogger()->info('no neccessary to run settle hot task from 2.am to 8.am every day.');
            return;
        }

        $context = new SettleContext();

        $this->settlePostLike($context);
        $this->settlePost($context);

        $this->saveTopicScores($context);
        $this->savePostScores($context);

//        $this->settlePostExposure($context);

        $this->updateHotTopics($context);
        $this->updateHotPosts($context);

        $this->container()->get('lychee.module.recommendation.last_modified_manager')->updateLastModified('hots');
    }

    private function settlePostLike(SettleContext $context) {
        list($minId, $maxId) = $this->getSettleIdRange(
            PostLike::class, 'id', 'updateTime'
        );

        $conn = $this->getConnection();

        $cursorId = $minId;
        $sql = <<<'SQL'
SELECT t1.id, t1.post_id, t1.topic_id, t1.vip, t1.type FROM (
  SELECT p.type, l.id, l.post_id, p.topic_id, IF(v.id IS NULL, 0, 1) as vip FROM like_post l
    INNER JOIN post p ON l.post_id = p.id
    LEFT JOIN user_vip v ON p.author_id = v.user_id
    WHERE l.id > ? AND l.id <= ? AND l.state = 0
    AND l.liker_id != p.author_id
    AND p.deleted = 0
    ORDER BY l.id ASC
    LIMIT 1000
) t1 INNER JOIN recommendable_topic t2 ON t1.topic_id = t2.topic_id
SQL;
        while (true) {
            $statement = $conn->executeQuery($sql, array($cursorId, $maxId));
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($result as $item) {
                $postScore = isset($item['vip']) && $item['vip'] == 1 ? 1.5 : 1;

                switch ($item['type']) {
                    case Post::TYPE_SHORT_VIDEO:
                        $postScore = $postScore * 1.5;
                        break;
                }
                $context->postAddScore($item['post_id'], $postScore);
                $context->topicAddScore($item['topic_id'], 1);
            }

            if (count($result) < 100) {
                break;
            } else {
                $cursorId = $result[count($result) - 1]['id'];
            }
        }

        $this->getLogger()->info(PostLike::class . ' settle done');
    }

    private function settlePost(SettleContext $context) {
        list($minId, $maxId) = $this->getSettleIdRange(
            Post::class, 'id', 'createTime'
        );

        $conn = $this->getConnection();

        $cursorId = $minId;
        while (true) {
            $statement = $conn->executeQuery('
              SELECT p.id, p.topic_id
              FROM post p
              INNER JOIN recommendable_topic r ON p.topic_id = r.topic_id
              WHERE p.id > ? AND p.id <= ? AND p.deleted = 0
              ORDER BY p.id ASC
              LIMIT 100
            ', array($cursorId, $maxId));
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($result as $item) {
                $context->topicAddScore($item['topic_id'], 4);
            }

            if (count($result) < 100) {
                break;
            } else {
                $cursorId = $result[count($result) - 1]['id'];
            }
        }

        $this->getLogger()->info(Post::class . ' settle done');
    }

    private function settlePostExposure(SettleContext $context) {
        list($minId, $maxId) = $this->getSettleIdRange(
            PostExposureRecord::class, 'id', 'time'
        );
        $conn = $this->getConnection();
        $st = microtime(true);
        $step = 100000;
        for ($i = $minId - 1; $i <= $maxId; $i += $step) {
            $this->settlePostExposureStep($context, $conn, $i, min($i + $step, $maxId));
        }
        $et = microtime(true);
        gc_collect_cycles();
        $te = $et - $st;
        $mem = memory_get_peak_usage(true) / 1024 / 1024;
        $postCount = count($context->getPostExposures());
        $this->getLogger()->info("mem_peak: {$mem}m, time_elapsed: {$te}s post_count:{$postCount} step:{$step}");
    }

    private function settlePostExposureStep(SettleContext $context, Connection $conn, $fromId, $toId) {
        $stat = $conn->executeQuery('
              SELECT e.post_id, count(e.id) as exposure
              FROM post_exposure_records e
              INNER JOIN recommendable_topic r ON e.topic_id = r.topic_id
              WHERE e.id > ? AND e.id <= ?
              GROUP BY e.post_id
            ', array($fromId, $toId));
        $result = $stat->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($result as $item) {
            $context->postAddExposure($item['post_id'], $item['exposure']);
        }
    }

    private function saveTopicScores(SettleContext $context) {
        $conn = $this->getConnection();

        $topicScores = $context->getTopicScores();
        $topicIds = array_keys($topicScores);
        if (empty($topicIds)) {
            return;
        }
        $stat = $conn->executeQuery('SELECT category_id, topic_id FROM topic_category_rel WHERE topic_id IN ('. implode(',', $topicIds) . ') ORDER BY category_id ASC, topic_id ASC');
        $scores = array();
        while (($row = $stat->fetch(\PDO::FETCH_NUM)) != false) {
            if (isset($topicScores[$row[1]])) {
                $scores[] = array($row[0], $topicScores[$row[1]], $row[1]);
            }
        }

        $scores = $this->makeSureEachCategoryUpTo($scores, 200);

        usort($scores, function($a, $b){
            if ($a[0] - $b[0] != 0) {
                return $a[0] - $b[0];
            } else if ($a[1] - $b[1] != 0) {
                return $a[1] - $b[1];
            } else {
                return $a[2] - $b[2];
            }
        });

        try {
            $conn->beginTransaction();
            $conn->executeUpdate('DELETE FROM topic_category_score WHERE 1');
            $offset = 0;
            do {
                $scoresSlice = array_slice($scores, $offset, 200, true);
                if (count($scoresSlice) == 0) {
                    break;
                }
                $values = array();
                foreach ($scoresSlice as $row) {
                    $values[] = '('.$row[0].','.$row[1].','.(isset($row[3]) ? $row[3] : 0).','.$row[2].')';
                }

                $sql = 'INSERT INTO topic_category_score(category_id, score, `order`, topic_id) VALUES'. implode(',', $values);
                $conn->executeUpdate($sql);

                $offset += 200;
            } while (true);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
        }
    }

    private function makeSureEachCategoryUpTo($rows, $count) {
        $rowsByCategories = array();
        foreach ($rows as $row) {
            if (isset($rowsByCategories[$row[0]])) {
                $rowsByCategories[$row[0]][] = $row[2];
            } else {
                $rowsByCategories[$row[0]] = array($row[2]);
            }
        }

        foreach ($rowsByCategories as $categoryId => $topicIds) {
            if (count($topicIds) >= $count) {
                continue;
            }

            $moreCount = $count - count($topicIds);
            $sql = "SELECT t.topic_id FROM recommendable_topic t
                LEFT JOIN topic_category_rel r ON t.topic_id = r.topic_id
                WHERE r.category_id = $categoryId AND t.topic_id NOT IN ("
                .implode(',', $topicIds)
                .") ORDER BY rand() LIMIT $moreCount";
            $stat = $this->getConnection()->executeQuery($sql);
            $moreRows = $stat->fetchAll(\PDO::FETCH_ASSOC);
            $order = 1;
            foreach ($moreRows as $moreRow) {
                $rows[] = array($categoryId, 0, $moreRow['topic_id'], $order);
                $order += 1;
            }
        }

        return $rows;
    }

    private function savePostScores(SettleContext $context) {
        $settleProcessor = $this->container()->get('lychee.module.recommendation.group_posts_settle_processor');

//        $postScores = array_filter($this->postScores, function($s){return $s > 2;});
        $postScores = $context->getPostScores();
        $postIds = array_keys($postScores);
        $this->getLogger()->info('post count to process:'.count($postIds));
        $postIds = $this->filterNonResourcePosts($postIds);
        $this->getLogger()->info('after filterNonResourcePosts post count to process:'.count($postIds));

        $result = $settleProcessor->process($postIds);
        $groupPostsService = $this->container()->get('lychee.module.recommendation.group_posts');
        $sortByScoreDesc = function($a, $b) use ($postScores) {
            $scoreDiff = $postScores[$b] - $postScores[$a];
            if ($scoreDiff == 0) {
                return $b - $a;
            } else {
                return $scoreDiff;
            }
        };
        $sortByIdAsc = function($a, $b) {
            return $a - $b;
        };
        gc_enable();
        foreach ($result->getIterator() as $groupId => $groupPostIds) {
            if ($groupId != PredefineGroup::ID_VIDEO) {
                $groupPostIds = array_filter($groupPostIds, function($pid) use ($postScores) {
                    return isset($postScores[$pid]) && $postScores[$pid] > 5;
                });
            }
            $postIdsNoInGroup = $groupPostsService->filterNoInGroupPosts($groupId, $groupPostIds);
            if (empty($postIdsNoInGroup)) {
                continue;
            }
            usort($postIdsNoInGroup, $sortByScoreDesc);
            $headPostIds = array_slice($postIdsNoInGroup, 0, 10);
            usort($headPostIds, $sortByIdAsc);
            $groupPostsService->addPostIdsToGroup($groupId, $headPostIds);
            gc_collect_cycles();
        }
    }

    private function filterNonResourcePosts($postIds) {
        if (count($postIds) == 0) {
            return array();
        }

        $slicePostIdItor = new ArrayCursorableIterator($postIds);
        $slicePostIdItor->setStep(500);
        $result = array();
        foreach ($slicePostIdItor as $slicePostIds) {
            if (empty($slicePostIds)) {
                continue;
            }

            $sql = 'SELECT id FROM post WHERE id IN('.implode(',', $slicePostIds).') AND type != '.Post::TYPE_RESOURCE;
            $stat = $this->getConnection()->executeQuery($sql);
            $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
            $resultSlice = ArrayUtility::columns($rows, 'id');
            $result = array_merge($result, $resultSlice);
        }
        return $result;
    }

    private function updateHotTopics(SettleContext $context) {
        $topicScores = $context->getTopicScores();
        uasort($topicScores, function($a, $b){return $b - $a;});
        $topicIds = array_keys(array_slice($topicScores, 0, 200, true));
        if (empty($topicIds)) {
            return;
        }
        $topicList = $this->recommendation()->getHotestIdList(RecommendationType::TOPIC);
        $topicList->update($topicIds);
    }

    private function updateHotPosts(SettleContext $context) {
        $postScores = $context->getPostScores();
//        $postExposures = $context->getPostExposures();
//
//        $postNewScores = array();
//        $rows = array();
//        foreach ($postScores as $postId => $score) {
//            $exposure = isset($postExposures[$postId]) ? $postExposures[$postId] : 1;
//            $postNewScores[$postId] = $score / $exposure;
//            $rows[] = [$postId, $score, $exposure];
//        }
//        $this->savePostScoreExposures($rows);

        $postNewScores = $postScores;

        uasort($postNewScores, function($a, $b){
            if ($a < $b) {
                return 1;
            } else if ($a > $b) {
                return -1;
            } else {
                return 0;
            }
        });
        $this->getLogger()->info("posts with (score / exposure) count:". count($postNewScores));

        $postIds = array_keys(array_slice($postNewScores, 0, 200, true));
        if (empty($postIds)) {
            return;
        }
        $postList = $this->recommendation()->getHotestIdList(RecommendationType::POST);
        $postList->update($postIds);
    }

    private function savePostScoreExposures($allRows) {
        $conn = $this->getConnection();
        $conn->executeUpdate('DELETE FROM ciyocon_oss.post_score_exposures WHERE 1');
        $itor = new ArrayCursorableIterator($allRows);
        $itor->setStep(500);
        $sqlPrefix = 'INSERT INTO ciyocon_oss.post_score_exposures(post_id, score, exposure) VALUES';
        foreach ($itor as $rowsSlice) {
            $rowSqls = array_map(function($row){return '('.implode(',', $row).')';}, $rowsSlice);
            $sql = $sqlPrefix . implode(',', $rowSqls);
            $conn->executeUpdate($sql);
        }
    }
}