<?php
namespace Lychee\Component\EventDispatcher;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use OldSound\RabbitMqBundle\RabbitMq\Producer;

class AsyncEventDispatcher implements EventDispatcherInterface {

    private $producer;
    private $logger;

    /**
     * @param Producer $producer
     */
    public function __construct($producer, $logger) {
        $this->producer = $producer;
        $this->producer->setContentType('application/json');
        $this->logger = $logger;
    }

    public function dispatch($eventName, Event $event = null) {
        try {
            $data = array(
                'name' => $eventName,
                'event' => serialize($event)
            );
            $this->producer->publish(json_encode($data));
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('exception in dispatching event', array(
                    'event' => $event,
                    'exception' => $e,
                    'trace' => $e->getTraceAsString()
                ));
            }
        }

        return $event;
    }

    public function addListener($eventName, $listener, $priority = 0) {
        // do nothing.
    }

    public function addSubscriber(EventSubscriberInterface $subscriber) {
        // do nothing.
    }

    public function removeListener($eventName, $listener) {
        // do nothing.
    }

    public function removeSubscriber(EventSubscriberInterface $subscriber) {
        // do nothing.
    }

    public function getListeners($eventName = null) {
        // do nothing.
    }

    public function hasListeners($eventName = null) {
        // do nothing.
    }

    public function getListenerPriority($eventName, $listener) {
        // do nothing.
    }

}