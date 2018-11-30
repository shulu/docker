<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 5/26/16
 * Time: 8:52 PM
 */

namespace Lychee\Module\UGSV;


use Lychee\Module\Post\PostAnnotation;
use Lychee\Module\Post\PostEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CoverStoreEventSubscriber implements EventSubscriberInterface {

    private $container;

    public static function getSubscribedEvents() {
        return [
            PostEvent::CREATE => 'onPostCreate',
        ];
    }

    public function __construct($container) {
        $this->container = $container;
    }


    public function getPostService() {
        return $this->container->get('lychee.module.post');
    }

    public function getLogService() {
        return $this->container->get('monolog.logger.consumer');
    }

    public function onPostCreate(PostEvent $event) {
        try {
            $postService = $this->getPostService();
            $postId = $event->getPostId();
            $postService->moveShortVideoCoverStoreById($postId);
        } catch (\Exception $e) {
            $this->getLogService()->error($e->__toString());
        }
    }

}