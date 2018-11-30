<?php
namespace Lychee\Component\EventDispatcher;

use Lychee\Component\Foundation\StringUtility;
use OldSound\RabbitMqBundle\RabbitMq\Producer;

class DynamicAsyncDispatcher {

    private $container;
    private $logger;

    /**
     * @param Producer $producer
     */
    public function __construct($container, $logger) {
        $this->container = $container;
        $this->logger = $logger;
    }

    public function dispatch($eventName, array $event) {
        try {
            $id = StringUtility::generateUniqueId(json_encode($event));
            $event['eventId'] = $id;
            if (empty($event['time'])) {
                $event['time'] = time();
            }
            $eventJson = json_encode($event);
            $this->container->get('old_sound_rabbit_mq.'.$eventName.'_producer')->publish($eventJson);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('exception in dispatching '.$eventName, array(
                    'event' => $event,
                    'exception' => $e,
                    'trace' => $e->getTraceAsString()
                ));
            }
        }
        return $event;
    }
}