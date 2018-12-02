<?php
namespace Lychee\Module\Account;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Lychee\Component\Foundation\CursorableIterator\CustomizedCursorableIterator;
use Lychee\Module\Account\AccountService;
use Lychee\Module\Post\PostService;
use Lychee\Module\Comment\CommentService;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManagerInterface;

class AccountCleaner implements ConsumerInterface {

    private $producer;
    private $accountService;
    private $commentService;
    private $postService;
    private $logger;
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @param Producer $producer
     * @param AccountService $accountService
     * @param PostService $postService
     * @param CommentService $commentService
     * @param RegistryInterface $registry
     * @param LoggerInterface $logger
     */
    public function __construct(
        $producer,
        $accountService,
        $postService,
        $commentService,
        $registry,
        $logger
    ) {
        $this->producer = $producer;
        $this->accountService = $accountService;
        $this->commentService = $commentService;
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
            if (!isset($data['userId'])) {
                return ConsumerInterface::MSG_ACK;
            }
            $userId = intval($data['userId']);
            $user = $this->accountService->fetchOne($userId);
            if ($user == null) {
                return ConsumerInterface::MSG_ACK;
            }

            $this->cleanUserPosts($userId);
            $this->cleanUserComment($userId);

            return ConsumerInterface::MSG_ACK;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('exception', array('exception' => $e, 'trace' => $e->getTraceAsString()));
            }
            return ConsumerInterface::MSG_ACK;
        }
    }

    public function cleanUser($userId) {
        $this->producer->setContentType('application/json');
        $data = array(
            'userId' => $userId,
        );
        $this->producer->publish(json_encode($data), 'clean_user_posts');
    }

    public function cleanUserPosts($userId) {
        $iterator = new CustomizedCursorableIterator(function($cursor, $step, &$nextCursor)use($userId){
            return $this->postService->fetchIdsByAuthorId($userId, $cursor, $step, $nextCursor);
        });
        $iterator->setStep(100);
        foreach ($iterator as $postIds) {
            foreach ($postIds as $postId) {
                $this->postService->delete($postId);
            }
        }
    }

    public function cleanUserComment($userId) {
        $iterator = new CustomizedCursorableIterator(function($cursor, $step, &$nextCursor)use($userId){
            return $this->commentService->fetchIdsByAuthorId($userId, $cursor, $step, $nextCursor);
        });
        $iterator->setStep(100);
        foreach ($iterator as $commentIds) {
            foreach ($commentIds as $commentId) {
                $this->commentService->delete($commentId);
            }
        }
    }

}