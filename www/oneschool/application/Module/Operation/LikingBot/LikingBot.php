<?php
namespace Lychee\Module\Operation\LikingBot;

use Lychee\Component\Foundation\ArrayUtility;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Lychee\Module\Like\LikeService;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

class LikingBot {

    /** @var EntityManagerInterface  */
    private $em;
    private $likeService;
    private $logger;

    /**
     * LikingBot constructor.
     * @param RegistryInterface $registry
     * @param LikeService $likeService
     * @param LoggerInterface $logger
     */
    public function __construct($registry, $likeService, $logger) {
        $this->em = $registry->getManager();
        $this->likeService = $likeService;
        $this->logger = $logger;
    }

    public function getLikeCountMultiple() {
        return 1;
    }

    public function getPostDayCount() {
        return 3;
    }

    private function createLogTableIfNeed() {
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS ciyocon_oss.bot_likes (
  id BIGINT NOT NULL AUTO_INCREMENT,
  liker_id BIGINT NOT NULL,
  post_id BIGINT NOT NULL,
  `time` INT NOT NULL,
  PRIMARY KEY (id)
)
SQL;
        $this->em->getConnection()->executeUpdate($sql);
    }

    public function run($runInterval) {
        $this->createLogTableIfNeed();

        $runInterval = max(5, $runInterval);
        $now = new \DateTimeImmutable();
        $timeRange = new TimeRange($now, \DateInterval::createFromDateString('-'.$runInterval.' seconds'));

        $likeCountToPerform = $this->getLikeCountToPerformAtTimeRange(
            $timeRange, $this->getLikeCountMultiple(), 0.05);
        if ($likeCountToPerform <= 0) {
            return;
        }

        if ($this->logger) {
            $this->logger->info('start pick user');
        }
        $userIds = $this->randomPickUsers($likeCountToPerform);
        if ($this->logger) {
            $timeRange = new TimeRange($now, \DateInterval::createFromDateString('-'.$this->getPostDayCount().' days'));
            $this->logger->info('start pick posts at '.$timeRange);
        }
        $postIds = $this->randomPickPostsAtDays($now, $this->getPostDayCount(), $likeCountToPerform);

        $performCount = min(count($userIds), count($postIds), $likeCountToPerform);
        if ($this->logger) {
            $this->logger->info("like count to perform: $performCount");
//            $this->logger->info('post: '.implode(', ', $postIds));
        }
        for ($i = 0; $i < $performCount; $i++) {
            $userId = $userIds[$i];
            $postId = $postIds[$i];
//            echo "$userId like $postId \n";
            $this->likeService->likePost($userId, $postId, $likedBefore);
            if (!$likedBefore) {
                $this->writeLog($userId, $postId);
            }
        }
    }

    public function makeLikeToPost($postId, $likeCount, $callEvent = true) {
        $userIds = $this->randomPickUsers($likeCount);
        if (empty($userIds)) {
            return;
        }
        if ($this->logger) {
            $this->logger->info('likers: '.implode(', ', $userIds));
        }
        foreach ($userIds as $userId) {
            $this->likeService->likePost($userId, $postId, $likedBefore, $callEvent);
            if (!$likedBefore) {
                $this->writeLog($userId, $postId);
            }
        }
    }

    private function writeLog($likerId, $postId) {
        $sql = 'INSERT INTO ciyocon_oss.bot_likes(liker_id, post_id, `time`) VALUES(?, ?, ?)';
        $this->em->getConnection()->executeUpdate($sql, array($likerId, $postId, time()), array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
    }

    public function calcActualLikeCountAtTimeRange(TimeRange $timeRange) {
        $allLike = $this->countAllLikeAtTimeRange($timeRange);
        $likeByBot = $this->countLikePerformByBotAtTimeRange($timeRange);
        return max(0, $allLike - $likeByBot);
    }

    public function countAllLikeAtTimeRange(TimeRange $timeRange) {
        return $this->countTableAtTimeRange('like_post', 'id', 'update_time', $timeRange, false);
    }

    public function countLikePerformByBotAtTimeRange(TimeRange $timeRange) {
        return $this->countTableAtTimeRange('ciyocon_oss.bot_likes', 'id', '`time`', $timeRange, true);
    }

    private function countTableAtTimeRange($table, $idField, $timeField, TimeRange $timeRange, $useTimeStamp = false) {
        list($minId, $maxId) = $this->getIdRangeAtTimeRange($table, $idField, $timeField, $timeRange, $useTimeStamp);
        $countSql = "SELECT COUNT($idField) FROM $table WHERE $idField >= $minId AND $idField <= $maxId";
        $stat = $this->em->getConnection()->executeQuery($countSql);
        $row = $stat->fetch(\PDO::FETCH_NUM);
        if ($row == false) {
            return 0;
        } else {
            return intval($row[0]);
        }
    }

    private function getIdRangeAtTimeRange($table, $idField, $timeField, TimeRange $timeRange, $useTimeStamp = false) {
        $conn = $this->em->getConnection();
        $from = $useTimeStamp ? $timeRange->from()->getTimestamp() : $timeRange->from()->format('\'Y-m-d H:i:s\'');
        $to = $useTimeStamp ? $timeRange->to()->getTimestamp() : $timeRange->to()->format('\'Y-m-d H:i:s\'');

        $maxIdSql = "SELECT $idField FROM $table WHERE $timeField < $to AND $timeField >= $from ORDER BY $idField DESC LIMIT 1";
        $stat = $conn->executeQuery($maxIdSql);
        $row = $stat->fetch(\PDO::FETCH_NUM);
        if ($row == false) {
            return array(0, 0);
        }
        $maxId = $row[0];

        $minIdSql = "SELECT $idField FROM $table WHERE $idField > (SELECT $idField FROM $table WHERE $idField < $maxId AND $timeField < $from ORDER BY $idField DESC LIMIT 1) ORDER BY $idField ASC LIMIT 1";
        $stat = $conn->executeQuery($minIdSql);
        $row = $stat->fetch(\PDO::FETCH_NUM);
        if ($row == false) {
            $minId = $maxId;
        } else {
            $minId = $row[0];
        }

        return array($minId, $maxId);
    }

    /**
     * @param TimeRange $timeRange
     * @param $multiple
     * @param $jitter
     * @return mixed
     */
    private function getLikeCountToPerformAtTimeRange($timeRange, $multiple, $jitter) {
        $timeRangeLastDay = $timeRange->shift(\DateInterval::createFromDateString('-1 day'));
        $actualCount = $this->calcActualLikeCountAtTimeRange($timeRangeLastDay);
        $expectCount = $actualCount * $multiple;
        $jitterLimit = round($expectCount * $jitter);
        $expectJitter = rand(0, 2 * $jitterLimit) - $jitterLimit;
        $expectCountWithJitter = $expectCount + $expectJitter;
        return max(0, $expectCountWithJitter);
    }

    private function randomPickUsers($count) {
        list($minId, $maxId) = $this->getUserIdRange();

        $userIds = array();
        while (count($userIds) < $count) {
            $userIdsStep = $this->randomPickUsersStep($minId, $maxId, 200);
            $userIds = array_merge($userIds, $userIdsStep);
        }

        shuffle($userIds);
        return array_slice($userIds, 0, $count);
    }

    private function getUserIdRange() {
        $minIdSql = 'SELECT MIN(id) FROM user';
        $stat = $this->em->getConnection()->executeQuery($minIdSql);
        $row = $stat->fetch(\PDO::FETCH_NUM);
        if ($row == false) {
            return array(0, 0);
        }
        $minId = $row[0];

        $maxIdSql = 'SELECT MAX(id) FROM user';
        $stat = $this->em->getConnection()->executeQuery($maxIdSql);
        $row = $stat->fetch(\PDO::FETCH_NUM);
        if ($row == false) {
            return array(0, 0);
        }
        $maxId = $row[0];
        return array(min($minId, $maxId), max($minId, $maxId));
    }

    private function randomPickUsersStep($minId, $maxId, $count) {
        $randIds = array();
        for ($i = 0; $i < $count; ++$i) {
            $randIds[] = rand($minId, $maxId);
        }
        if (empty($randIds)) {
            return array();
        }

        $sql = 'SELECT DISTINCT u.id FROM `user` u INNER JOIN auth_token t ON u.id = t.user_id WHERE u.id IN('
            .implode(',', array_unique($randIds)).') AND (t.id IS NULL OR (t.create_time + t.ttl < '.time().'))';
        $stat = $this->em->getConnection()->executeQuery($sql);
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        return ArrayUtility::columns($rows, 'id');
    }

    private function randomPickPostsAtDays($from, $dayCount, $count) {
        assert($dayCount > 0);

        $postIds = array();
        $pickPerDay = ceil($count / $dayCount);
        $dayInterval = \DateInterval::createFromDateString('-1 days');

        $dayRange = new TimeRange($from, $dayInterval);
        $i = 0;
        do {
            $postIdsInDay = $this->randomPickPostsAtTimeRange($dayRange, $pickPerDay);
            $postIds = array_merge($postIds, $postIdsInDay);
            $dayRange = $dayRange->shift($dayInterval);
            $i += 1;
        } while ($i < $dayCount);

        shuffle($postIds);
        return array_values(array_slice($postIds, 0, $count));
    }

    private function randomPickPostsAtTimeRange(TimeRange $timeRange, $count) {
        list($minId, $maxId) = $this->getIdRangeAtTimeRange('post', 'id', 'create_time', $timeRange, false);
        if ($this->logger) {
            $this->logger->info("pick post in time range {$timeRange}, id [$minId, $maxId]");
        }
        $sql = "SELECT p.id FROM post p INNER JOIN recommendable_topic rt ON p.topic_id = rt.topic_id INNER JOIN `user` u ON p.author_id = u.id WHERE p.id >= $minId AND p.id <= $maxId AND p.deleted = 0 AND u.level >= 5";
        $stat = $this->em->getConnection()->executeQuery($sql);
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        $postIds = ArrayUtility::columns($rows, 'id');

        $result = array();
        $max = count($postIds) - 1;
        for ($i = 0; $i < $count; ++$i) {
            $result[] = $postIds[rand(0, $max)];
        }
        if ($this->logger) {
//            $this->logger->info('picked post id: '. implode(', ', $result));
        }
        return $result;
    }

}

class TimeRange {

    private $_from;
    private $_to;

    /**
     * TimeRange constructor.
     * @param \DateTimeImmutable $from
     * @param \DateTimeImmutable|\DateInterval $to
     */
    public function __construct($from, $to) {
        if ($to instanceof \DateInterval) {
            $to = $from->add($to);
        }

        if ($from <= $to) {
            $this->_from = $from;
            $this->_to = $to;
        } else {
            $this->_from = $to;
            $this->_to = $from;
        }
    }

    /**
     * @return \DateTimeImmutable
     */
    public function from() {
        return $this->_from;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function to() {
        return $this->_to;
    }

    /**
     * @param \DateInterval $timeInterval
     * @return TimeRange
     */
    public function shift($timeInterval) {
        $newFrom = $this->_from->add($timeInterval);
        $newTo = $this->_to->add($timeInterval);
        return new TimeRange($newFrom, $newTo);
    }

    public function __toString() {
        return "[{$this->_from->format('Y-m-d H:i:s')}, {$this->_to->format('Y-m-d H:i:s')}]";
    }
}