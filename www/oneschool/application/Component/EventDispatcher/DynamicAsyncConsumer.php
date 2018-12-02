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

class DynamicAsyncConsumer implements ConsumerInterface {

    private $logger;
    private $service;
    private $method;

    private $isKnownCall=false;

    /**
     * @param LoggerInterface $logger
     * @param Registry $registry
     * @param PushService $pusher
     */
    public function __construct($logger, $service, $method) {
        $this->service = $service;
        $this->method = $method;
        $this->logger = $logger;
    }

    public function execute(AMQPMessage $msg) {
        try {
            $info = json_decode($msg->body, true);
	        $timeStart = microtime(true);
            call_user_func_array([$this->service, $this->method], [$info]);
	        $timeUsed = microtime(true) - $timeStart;

	        if (empty($this->isKnownCall)) {
                $this->isKnownCall = true;
                echo sprintf("%s->%s\n", get_class($this->service), $this->method);
            }
	        echo sprintf("%s %s %ss \n", date('Y-m-d H:i:s'), $msg->body, $timeUsed);

	        if ($timeUsed >= 3) {
		        echo ">>> " . json_encode($info, JSON_UNESCAPED_UNICODE) . "\n";
	        }
            return ConsumerInterface::MSG_ACK;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error($e->__toString());
            }
            return ConsumerInterface::MSG_REJECT_REQUEUE;
        }
    }

}