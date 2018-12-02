<?php
namespace Lychee\Module\Schedule;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Lychee\Module\Notification\NotificationService;

class EventSubscriber implements EventSubscriberInterface {

    public static function getSubscribedEvents() {
        return array(
            ScheduleEvent::CANCEL => 'onScheduleCancel'
        );
    }

    private $scheduleService;
    private $notificationService;

    /**
     * @param ScheduleService $scheduleService
     * @param NotificationService $notificationService
     */
    public function __construct($scheduleService, $notificationService) {
        $this->scheduleService = $scheduleService;
        $this->notificationService = $notificationService;
    }

    public function onScheduleCancel(ScheduleEvent $event) {
        if ($event->getScheduleId()) {
            $this->scheduleService->onCancel($event->getScheduleId(), $this->notificationService);
        }
    }
}