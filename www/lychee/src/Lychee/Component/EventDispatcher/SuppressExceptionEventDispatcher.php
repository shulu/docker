<?php
namespace Lychee\Component\EventDispatcher;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;

class SuppressExceptionEventDispatcher implements EventDispatcherInterface {

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * Constructor.
     *
     * @param EventDispatcherInterface $dispatcher An EventDispatcherInterface instance
     * @param LoggerInterface          $logger     A LoggerInterface instance
     */
    public function __construct(EventDispatcherInterface $dispatcher, LoggerInterface $logger = null) {
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->called = array();
    }

    /**
     * {@inheritdoc}
     */
    public function addListener($eventName, $listener, $priority = 0) {
        $this->dispatcher->addListener($eventName, $listener, $priority);
    }

    /**
     * {@inheritdoc}
     */
    public function addSubscriber(EventSubscriberInterface $subscriber) {
        $this->dispatcher->addSubscriber($subscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function removeListener($eventName, $listener) {
        return $this->dispatcher->removeListener($eventName, $listener);
    }

    /**
     * {@inheritdoc}
     */
    public function removeSubscriber(EventSubscriberInterface $subscriber) {
        return $this->dispatcher->removeSubscriber($subscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function getListeners($eventName = null) {
        return $this->dispatcher->getListeners($eventName);
    }

    /**
     * {@inheritdoc}
     */
    public function hasListeners($eventName = null) {
        return $this->dispatcher->hasListeners($eventName);
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($eventName, Event $event = null) {
        try {
            $event = $this->dispatcher->dispatch($eventName, $event);
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

    public function getListenerPriority($eventName, $listener) {
        return $this->dispatcher->getListenerPriority($eventName, $listener);
    }
} 