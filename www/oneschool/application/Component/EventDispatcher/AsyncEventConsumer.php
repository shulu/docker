<?php
namespace Lychee\Component\EventDispatcher;

use Doctrine\DBAL\Exception\DriverException;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Lychee\Module\Notification\Push\PushService;

class AsyncEventConsumer implements ConsumerInterface {

    /**
     * @var EntityManager
     */
    private $entityManager;
    private $eventDispatcher;
    private $logger;
    private $pusher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param LoggerInterface $logger
     * @param Registry $registry
     * @param PushService $pusher
     */
    public function __construct($eventDispatcher, $logger, $registry, $pusher) {
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
        $this->entityManager = $registry ? $registry->getManager() : null;
        $this->pusher = $pusher;
    }

    public function execute(AMQPMessage $msg) {
        try {
            $info = json_decode($msg->body, true);
            if (!isset($info['name']) || !isset($info['event'])) {
                return ConsumerInterface::MSG_ACK;
            }

            if ($this->entityManager && $this->entityManager->getConnection()->ping() == false) {
                $this->logger->info('Mysql disconnected. try to reconnect.\n');
                $this->entityManager->getConnection()->close();
                $this->entityManager->getConnection()->connect();
            }

	        $timeStart = microtime(true);

            $eventName = $info['name'];
            $event = unserialize($info['event']);
            $this->eventDispatcher->dispatch($eventName, $event);

	        $timeUsed = microtime(true) - $timeStart;
	        echo "{$eventName} time used:{$timeUsed} s \n";
	        if ($timeUsed >= 3) {
		        echo ">>> " . json_encode($info, JSON_UNESCAPED_UNICODE) . "\n";
	        }

            return ConsumerInterface::MSG_ACK;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('exception', array('exception' => $e, 'trace' => $e->getTraceAsString()));
            }
            if ($this->pusher) {
                try {
                    $this->pusher->reportError($e->getMessage());
                } catch (\Exception $e) {
                    if ($this->logger) {
                        $this->logger->error('exception', array('exception' => $e, 'trace' => $e->getTraceAsString()));
                    }
                }
            }
            return ConsumerInterface::MSG_ACK;
        } finally {
            $this->entityManager->clear();
        }
    }

}