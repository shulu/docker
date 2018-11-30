<?php
namespace Lychee\Module\Activity;

use Lychee\Module\Comment\CommentEvent;
use Lychee\Module\Like\LikeEvent;
use Lychee\Module\Like\LikeType;
use Lychee\Module\Post\PostEvent;
use Lychee\Module\Relation\RelationEvent;
use Lychee\Module\Topic\Following\TopicFollowingEvent;
use Lychee\Module\Topic\TopicEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Lychee\Module\Activity\ActivityService;
use Lychee\Module\Topic\TopicService;
use Lychee\Module\Post\PostService;

class EventSubscriber implements EventSubscriberInterface {

    public static function getSubscribedEvents() {
        return array(
            TopicEvent::CREATE => 'onTopicCreate',
            PostEvent::CREATE => 'onPostCreate',
            TopicFollowingEvent::FOLLOW => 'onFollowTopic',
            RelationEvent::FOLLOW => 'onFollowUser',
            LikeEvent::LIKE => 'onLike',
        );
    }

    private $activityService;
    private $topicService;
    private $postService;

    /**
     * EventSubscriber constructor.
     * @param ActivityService $activityService
     * @param TopicService $topicService
     * @param PostService $postService
     */
    public function __construct($activityService, $topicService, $postService) {
        $this->activityService = $activityService;
        $this->topicService = $topicService;
        $this->postService = $postService;
    }

    public function onTopicCreate(TopicEvent $event) {
        $topic = $this->topicService->fetchOne($event->getTopicId());
        if (!$topic) {
            return;
        }
        $this->activityService->addCreateTopicActivite($topic->creatorId, $topic->id);
    }

    public function onPostCreate(PostEvent $event) {
        $post = $this->postService->fetchOne($event->getPostId());
        if (!$post) {
            return;
        }
        if ($post->topicId) {
            $topic = $this->topicService->fetchOne($post->topicId);
            if ($topic->private == false && $topic->hidden == false) {
                $this->activityService->addPostActivite($post->authorId, $post->id);
            }
        }
    }

    public function onFollowTopic(TopicFollowingEvent $event) {
        if ($event->getFollowedBefore() == true) {
            return;
        }

        $topic = $this->topicService->fetchOne($event->getTopicId());
        if (!$topic) {
            return;
        }

        if ($topic->hidden == false) {
	        $this->activityService->addFollowTopicActivite($event->getUserId(), $event->getTopicId());
        }
    }

    public function onFollowUser(RelationEvent $event) {
        foreach ($event->getFolloweeIds() as $followeeId) {
            $this->activityService->addFollowUserActivite($event->getFollowerId(), $followeeId);
        }
    }

    public function onLike(LikeEvent $event) {
        if ($event->getLikeType() != LikeType::POST) {
            return;
        }

        $post = $this->postService->fetchOne($event->getTargetId());
        if (!$post) {
            return;
        }

        if (!$event->isLikedBefore() && $post->authorId != $event->getLikerId()) {
            if ($post->topicId) {
                $topic = $this->topicService->fetchOne($post->topicId);
                if ($topic->private == false && $topic->hidden == false) {
                    $this->activityService->addLikePostActivite($event->getLikerId(), $event->getTargetId());
                }
            }
        }
    }
}