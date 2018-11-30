<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 6/15/16
 * Time: 11:38 AM
 */

namespace Lychee\Bundle\AdminBundle\EventListener;


use Lychee\Bundle\AdminBundle\Service\GrayListService;
use Lychee\Bundle\AdminBundle\Service\ManagerLog\ManagerLogService;
use Lychee\Bundle\AdminBundle\Service\ManagerLog\OperationType;
use Lychee\Module\Post\PostService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventSubscriber implements EventSubscriberInterface {

    private $logService;

    private $grayListService;

    public static function getSubscribedEvents() {
        return [
            PostEvent::DELETE => 'onPostDelete'
        ];
    }

    public function __construct(ManagerLogService $logService, GrayListService $grayListService) {
        $this->logService = $logService;
        $this->grayListService = $grayListService;
    }

    public function onPostDelete(PostEvent $event) {
        $this->logService->log($event->getAdminId(), OperationType::DELETE_POST, $event->getPostId());
        $this->grayListService->log($event->getPostId(), $event->getAdminId());
    }
}