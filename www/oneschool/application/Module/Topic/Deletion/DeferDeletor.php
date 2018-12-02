<?php
namespace Lychee\Module\Topic\Deletion;

use Lychee\Component\Foundation\CursorableIterator\CustomizedCursorableIterator;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Lychee\Module\Topic\TopicService;
use Lychee\Module\Post\PostService;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Lychee\Module\Topic\Following\TopicFollowingService;

class DeferDeletor implements ConsumerInterface, Deletor {

    private $producer;
    private $topicService;
    private $topicFollowingService;
    private $postService;
    private $indexer;
    private $logger;
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @param Producer $producer
     * @param TopicService $topicService
     * @param TopicFollowingService $topicFollowingService
     * @param PostService $postService
     * @param RegistryInterface $registry
     * @param LoggerInterface $logger
     */
    public function __construct(
        $producer,
        $topicService,
        $topicFollowingService,
        $postService,
        $logger,
        $registry
    ) {
        $this->producer = $producer;
        $this->topicService = $topicService;
        $this->topicFollowingService = $topicFollowingService;
        $this->postService = $postService;
        $this->logger = $logger;
        $this->em = $registry->getManager();
    }

    public function execute(AMQPMessage $message) {
        try {
            if ($this->em && $this->em->getConnection()->ping() == false) {
                $this->em->getConnection()->close();
                $this->em->getConnection()->connect();
            }

            $data = json_decode($message->body, true);
            if (!isset($data['topicId'])) {
                return ConsumerInterface::MSG_ACK;
            }
            $topicId = intval($data['topicId']);
            $topic = $this->topicService->fetchOne($topicId);
            if ($topic == null) {
                return ConsumerInterface::MSG_ACK;
            }

            $this->clearTopic($topicId);

            return ConsumerInterface::MSG_ACK;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('exception', array('exception' => $e, 'trace' => $e->getTraceAsString()));
            }
            return ConsumerInterface::MSG_ACK;
        }
    }

    /**
     * @param int $topicId
     *
     */
    public function delete($topicId) {
        $this->topicService->maskAsDeleted($topicId);

        $this->producer->setContentType('application/json');
        $data = array(
            'topicId' => $topicId,
        );
        $this->producer->publish(json_encode($data), 'topic_deletion');
    }

    public function clearTopic($topicId) {
        $this->unfollowAllFollowers($topicId);
        $this->deleteAllPosts($topicId);
    }

    private function unfollowAllFollowers($topicId) {
        $iterator = $this->topicFollowingService->getTopicFollowerIterator($topicId);
        $iterator->setStep(100);
        foreach ($iterator as $userIds) {
            foreach ($userIds as $userId) {
                $this->topicFollowingService->unfollow($userId, $topicId);
            }
        }
    }

    private function deleteAllPosts($topicId) {
        $iterator = new CustomizedCursorableIterator(function($cursor, $step, &$nextCursor)use($topicId){
            return $this->postService->fetchIdsByTopicId($topicId, $cursor, $step, $nextCursor);
        });
        $iterator->setStep(100);
        foreach ($iterator as $postIds) {
            foreach ($postIds as $postId) {
                $this->postService->delete($postId);
            }
        }
    }
}