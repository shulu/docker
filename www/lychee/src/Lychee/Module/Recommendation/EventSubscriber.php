<?php
namespace Lychee\Module\Recommendation;

use Lychee\Module\Comment\CommentEvent;
use Lychee\Module\Post\PostEvent;
use Lychee\Module\Topic\TopicEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Lychee\Module\Recommendation\RecommendationService;

class EventSubscriber implements EventSubscriberInterface {

    public static function getSubscribedEvents() {
        return array(

        );
    }

    private $recommendation;

    /**
     * @param RecommendationService $recommendation
     */
    public function __construct($recommendation) {
        $this->recommendation = $recommendation;
    }
}