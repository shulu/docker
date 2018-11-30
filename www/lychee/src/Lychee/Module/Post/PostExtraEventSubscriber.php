<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 5/26/16
 * Time: 8:52 PM
 */

namespace Lychee\Module\Post;

use Lychee\Module\Post\PostAnnotation;
use Lychee\Module\Post\PostEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PostExtraEventSubscriber implements EventSubscriberInterface {

    private $container;

    public static function getSubscribedEvents() {
        return [
            PostEvent::CREATE => 'onPostCreate',
            PostEvent::UNDELETE => 'onPostRecover',
            PostEvent::DELETE => 'onPostDelete',
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
//        $postService = $this->getPostService();
        $logger = $this->getLogService();
        $postId = $event->getPostId();
        $logger->debug(__FILE__.':'.__line__.', '.$postId.' init post extra data, begin.');
//        $postService->initPostExtraData($postId);
        $logger->debug(__FILE__.':'.__line__.', '.$postId.' init post extra data, done.');
    }

    public function onPostRecover(PostEvent $event) {
//        $postService = $this->getPostService();
        $logger = $this->getLogService();
        $postId = $event->getPostId();
        $logger->debug(__FILE__.':'.__line__.', postId: '.$postId.' pass post extra status, begin.');
//        $postService->passPostExtra([$postId]);
        $logger->debug(__FILE__.':'.__line__.', postId: '.$postId.' pass post extra status, done.');
    }

    public function onPostDelete(PostEvent $event) {
        $postService = $this->getPostService();
        $post = $postService->fetchOne($event->getPostId());
        if ($post) {
            try {
            } catch (ResponseException $e) {
                //document miss do nothing
            }
        }
    }
}
