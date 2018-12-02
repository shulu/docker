<?php
namespace Lychee\Module\Topic;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManager;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Lychee\Module\Topic\Following\TopicFollowingService;
use Lychee\Module\Notification\NotificationService;
use Lychee\Module\Topic\TopicService;
use Psr\Log\LoggerInterface;

class TopicAnnouncementService implements ConsumerInterface {

    /**
     * @var EntityManager
     */
    private $em;
    private $producer;
    private $topicService;
    private $topicFollowing;
    private $notificationService;
    private $logger;

    /**
     * TopicAnnouncementService constructor.
     * @param RegistryInterface $registry
     * @param Producer $producer
     * @param TopicService $topicService
     * @param TopicFollowingService $topicFollowing
     * @param NotificationService $notificationService
     * @param LoggerInterface $logger
     */
    public function __construct($registry, $producer, $topicService,
                                $topicFollowing, $notificationService, $logger) {
        $this->em = $registry->getManager();
        $this->producer = $producer;
        $this->topicService = $topicService;
        $this->topicFollowing = $topicFollowing;
        $this->notificationService = $notificationService;
        $this->logger = $logger;
    }

    public function announce($topicId, $postId, $certified = false) {
        $time = time();
        $availableLastTime = strtotime('last Sunday');
        if ($certified) {
            $sql = 'INSERT INTO topic_announcing(topic_id, last_announce_time, last_announce_post, last_announce_time2, last_announce_post2)'.
                ' VALUES(?, ?, ?, NULL, NULL) ON DUPLICATE KEY UPDATE'
                .' last_announce_time2 = IF((@canUpdate := (last_announce_time2 IS NULL OR last_announce_time2 < ?)), last_announce_time, last_announce_time2),'
                .' last_announce_post2 = IF(@canUpdate, last_announce_post, last_announce_post2),'
                .' last_announce_time = IF(@canUpdate, VALUES(last_announce_time), last_announce_time),'
                .' last_announce_post = IF(@canUpdate, VALUES(last_announce_post), last_announce_post)';
        } else {
            $sql = 'INSERT INTO topic_announcing(topic_id, last_announce_time, last_announce_post) VALUES(?, ?, ?)'
                .' ON DUPLICATE KEY UPDATE'
                .' last_announce_time = IF((@canUpdate := last_announce_time < ?), VALUES(last_announce_time), last_announce_time),'
                .' last_announce_post = IF(@canUpdate, VALUES(last_announce_post), last_announce_post)';
        }

        $affectedRows = $this->em->getConnection()->executeUpdate($sql,
            array($topicId, $time, $postId, $availableLastTime),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
        if ($affectedRows == 0) {
            return false;
        }

        $this->producer->setContentType('application/json');
        $data = array(
            'topicId' => $topicId,
            'postId' => $postId,
        );
        $this->producer->publish(json_encode($data), '');
        return true;
    }

    public function execute(AMQPMessage $message) {
        try {
            if ($this->em && $this->em->getConnection()->ping() == false) {
                $this->em->getConnection()->close();
                $this->em->getConnection()->connect();
            }

            $data = json_decode($message->body, true);
            if (!isset($data['topicId']) || !isset($data['postId'])) {
                return ConsumerInterface::MSG_ACK;
            }
            $topicId = intval($data['topicId']);
            $postId = intval($data['postId']);
            if ($topicId <= 0 || $postId <= 0) {
                return ConsumerInterface::MSG_ACK;
            }

            $topic = $this->topicService->fetchOne($topicId);
            if ($topic == null) {
                return ConsumerInterface::MSG_ACK;
            }

            $itor = $this->topicFollowing->getTopicFollowerIterator($topicId);
            $itor->setStep(500);
            $processed = 0;
            $lastProcessed = 0;
            $timeStart = microtime(true);
            echo "{$topic->title}({$topic->id}) count: {$topic->followerCount} begin\n";
            foreach ($itor as $followerIds) {
                $this->notificationService->notifyTopicAnnouncementEvent(
                    $followerIds, $topicId, $topic->managerId, $postId);
                $processed += count($followerIds);
                if ($processed - $lastProcessed >= 1000) {
                    $lastProcessed = $processed;
                    $progress = round($processed / $topic->followerCount * 100, 2);
                    $timeUsed = microtime(true) - $timeStart;
                    echo date('Y-m-d H:i:s') . " $progress%,  time used: $timeUsed s.\n";
                }
            }

	        $timeUsed = microtime(true) - $timeStart;
            echo "{$topic->title}({$topic->id}) count: {$topic->followerCount} done, time used:{$timeUsed} s \n";

            return ConsumerInterface::MSG_ACK;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('exception', array('exception' => $e, 'trace' => $e->getTraceAsString()));
            }
            return ConsumerInterface::MSG_ACK;
        }
    }

}