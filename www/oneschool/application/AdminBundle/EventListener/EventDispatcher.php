<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 6/14/16
 * Time: 5:29 PM
 */

namespace Lychee\Bundle\AdminBundle\EventListener;


use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventDispatcher implements EventDispatcherInterface {

    public function __construct(EventDispatcherInterface $dispatcher) {
        $this->dispatcher = $dispatcher;
    }

    public function dispatch($eventName, Event $event = null) {
        $this->dispatcher->dispatch($eventName, $event);
    }

    public function addListener($eventName, $listener, $priority = 0) {}

    public function addSubscriber(EventSubscriberInterface $subscriber) {}

    public function removeListener($eventName, $listener) {}

    public function removeSubscriber(EventSubscriberInterface $subscriber) {}

    public function getListeners($eventName = null) {}

    public function hasListeners($eventName = null) {}

	public function getListenerPriority($eventName, $listener) {
		// do nothing.
	}
}