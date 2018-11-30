<?php
namespace Lychee\Module\Topic;

use Doctrine\ORM\EntityManagerInterface;
use Lychee\Module\Topic\Entity\TopicVisitorCounting;
use Lychee\Module\Topic\Entity\TopicVisitorLog;
use Symfony\Bridge\Doctrine\RegistryInterface;

class TopicVisitorRecorder {

    private $redis;
    /**
     * @var EntityManagerInterface
     */
    private $em;

    private $container;

    /**
     * @param \Predis\Client|\Redis $redis
     * @param RegistryInterface $registry
     */
    public function __construct($redis, $registry, $container) {
        $this->redis = $redis;
        $this->em = $registry->getManager();
        $this->container = $container;
    }

    private function topicKey($topicId) {
        return 'latest_visitor:'.$topicId;
    }

    /**
     * @param int $topicId
     * @param int $visitorId
     */
    public function topicAddVisitor($topicId, $visitorId) {
        $key = $this->topicKey($topicId);
        $this->redis->multi();
        $this->redis->lRem($key, 0, $visitorId);
        $this->redis->lPush($key, $visitorId);
        $this->redis->lTrim($key, 0, 20);
        $this->redis->exec();

        $log = new TopicVisitorLog();
        $log->topicId = $topicId;
        $log->userId = $visitorId;
        $log->createTime = new \DateTime('now');
        $this->em->persist($log);
        $this->em->flush();

        $data = array(
            'topicId' => $topicId,
            'userId' => $visitorId,
            'time' => time()
        );
        $this->container->get('lychee.dynamic_dispatcher_async')->dispatch('topic_visit', $data);
    }

    /**
     * @param int $topicId
     *
     * @return int[]
     */
    public function getTopicLatestVisitors($topicId) {
        $key = $this->topicKey($topicId);
        return $this->redis->lRange($key, 0, 9);
    }

    /**
     * 订阅次元访问事件，累计访问次数
     *
     * @param $eventBody
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function increaseCounterOnVisit($eventBody) {
        $sql = <<<'SQL'
        UPDATE topic_visitor_counting SET count=count+1 WHERE topic_id=? and user_id=?
SQL;
        $topicId = $eventBody['topicId'];
        $userId = $eventBody['userId'];
        $r = $this->em->getConnection()->executeUpdate($sql, [$topicId, $userId]);
        if ($r) {
            return true;
        }

        $sql = <<<'SQL'
INSERT INTO topic_visitor_counting (topic_id, user_id, count) VALUES (?, ?, 1)
ON DUPLICATE KEY UPDATE count=count+1
SQL;
        $this->em->getConnection()->executeUpdate($sql, [$topicId, $userId]);
        return true;
    }

}