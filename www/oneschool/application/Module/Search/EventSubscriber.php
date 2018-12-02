<?php
namespace Lychee\Module\Search;

use Elastica\Exception\ResponseException;
use Lychee\Module\Post\PostEvent;
use Lychee\Module\Topic\Entity\TopicUserFollowing;
use Lychee\Module\Topic\Following\TopicFollowingEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Lychee\Module\Account\AccountEvent;
use Lychee\Module\Topic\TopicEvent;
use Lychee\Module\Search\Indexer;
use Lychee\Module\Account\AccountService;
use Lychee\Module\Topic\TopicService;
use Lychee\Module\Post\PostService;
use Lychee\Module\Topic\Entity\Topic;
use Lychee\Module\Search\TopicFollowerIndexer;
use Lychee\Module\Topic\Following\TopicFollowingService;
use Lychee\Component\Foundation\CursorableIterator\CustomizedCursorableIterator;
use Elastica\Exception\Bulk\ResponseException as BulkResponseException;

class EventSubscriber implements EventSubscriberInterface {

    private $accountIndexer;
    private $accountService;
    private $topicIndexer;
    private $topicService;
    private $postIndexer;
    private $postService;
    private $topicFollowerIndexer;
    private $topicFollowingService;

    /**
     * @param Indexer $accountIndexer
     * @param AccountService $accountService
     * @param Indexer $topicIndexer
     * @param TopicService $topicService
     * @param Indexer $postIndexer
     * @param PostService $postService
     * @param TopicFollowerIndexer $topicFollowerIndexer
     * @param TopicFollowingService $topicFollowingService
     */
    public function __construct(
        $accountIndexer, $accountService,
        $topicIndexer, $topicService,
        $postIndexer, $postService,
        $topicFollowerIndexer, $topicFollowingService
    ) {
        $this->accountIndexer = $accountIndexer;
        $this->accountService = $accountService;
        $this->topicIndexer = $topicIndexer;
        $this->topicService = $topicService;
        $this->postIndexer = $postIndexer;
        $this->postService = $postService;
        $this->topicFollowerIndexer = $topicFollowerIndexer;
        $this->topicFollowingService = $topicFollowingService;
    }

    public static function getSubscribedEvents() {
        return array(
            AccountEvent::CREATE => 'onAccountCreate',
            AccountEvent::FREEZE => 'onAccountFreeze',
            AccountEvent::UNFREEZE => 'onAccountUnfreeze',
            TopicEvent::CREATE => 'onTopicCreate',
            TopicEvent::UPDATE => 'onTopicUpdate',
            TopicEvent::DELETE => 'onTopicDelete',
            TopicEvent::HIDE => 'onTopicHide',
            TopicEvent::UNHIDE => 'onTopicUnhide',
            PostEvent::CREATE => 'onPostCreate',
            PostEvent::DELETE => 'onPostDelete',
            PostEvent::UPDATE => 'onPostUpdate',
            TopicFollowingEvent::FOLLOW => 'onTopicFollow',
            TopicFollowingEvent::UNFOLLOW => 'onTopicUnfollow',
            AccountEvent::UPDATE_NICKNAME => 'onUserRename'
        );
    }

    /**
     * @param Topic $topic
     * @return bool
     */
    private function topicCanIndex($topic) {
        if ($topic->hidden || $topic->deleted) {
            return false;
        } else {
            return true;
        }
    }

    public function onAccountCreate(AccountEvent $event) {
        $account = $this->accountService->fetchOne($event->getAccountId());
        if ($account) {
            $this->accountIndexer->add($account);
        }
    }

    public function onAccountFreeze(AccountEvent $event) {
        $account = $this->accountService->fetchOne($event->getAccountId());
        if ($account) {
            try {
                $this->accountIndexer->remove($account);
            } catch (ResponseException $e) {
                //document miss do nothing
            }
        }
    }

    public function onAccountUnfreeze(AccountEvent $event) {
        $account = $this->accountService->fetchOne($event->getAccountId());
        if ($account) {
            try {
                $this->accountIndexer->add($account);
            } catch (ResponseException $e) {
                //document miss do nothing
            }
        }
    }

    public function onTopicCreate(TopicEvent $event) {
        $topic = $this->topicService->fetchOne($event->getTopicId());
        if ($topic && $this->topicCanIndex($topic)) {
            $this->topicIndexer->add($topic);
        }
    }

    public function onTopicUpdate(TopicEvent $event) {
        $topic = $this->topicService->fetchOne($event->getTopicId());
        if ($topic && $this->topicCanIndex($topic)) {
            try {
                $this->topicIndexer->update($topic);
            } catch (ResponseException $e) {
                //document miss do nothing
            }
        }
    }

    public function onTopicDelete(TopicEvent $event) {
        $topic = $this->topicService->fetchOne($event->getTopicId());
        if ($topic) {
            try {
                $this->topicIndexer->remove($topic);
            } catch (ResponseException $e) {
                //document miss do nothing
            }
        }
    }

    public function onTopicHide(TopicEvent $event) {
        $topic = $this->topicService->fetchOne($event->getTopicId());
        if ($topic) {
            try {
                $this->topicIndexer->remove($topic);
            } catch (ResponseException $e) {
                //document miss do nothing
            }

            $iterator = new CustomizedCursorableIterator(function($cursor, $step, &$nextCursor)use($topic){
                return $this->postService->fetchIdsByTopicId($topic->id, $cursor, $step, $nextCursor);
            });
            $iterator->setStep(200);
            foreach ($iterator as $postIds) {
                $posts = $this->postService->fetch($postIds);
                $this->postIndexer->update($posts);
            }
        }
    }

    public function onTopicUnhide(TopicEvent $event) {
        $topic = $this->topicService->fetchOne($event->getTopicId());
        if ($topic) {
            try {
                $this->topicIndexer->add($topic);
            } catch (ResponseException $e) {
                //document miss do nothing
            }

            $iterator = new CustomizedCursorableIterator(function($cursor, $step, &$nextCursor)use($topic){
                return $this->postService->fetchIdsByTopicId($topic->id, $cursor, $step, $nextCursor);
            });
            $iterator->setStep(200);
            foreach ($iterator as $postIds) {
                $posts = $this->postService->fetch($postIds);
                $this->postIndexer->update($posts);
            }
        }
    }

    public function onPostCreate(PostEvent $event) {
        $post = $this->postService->fetchOne($event->getPostId());
        if ($post == null) {
            return;
        }

        $topic = $this->topicService->fetchOne($post->topicId);
        if ($topic && $this->topicCanIndex($topic)) {
            if (strlen($post->content) > 0 && $post->deleted == false) {
                $this->postIndexer->add($post);
            }
        }
    }

    public function onPostDelete(PostEvent $event) {
        $post = $this->postService->fetchOne($event->getPostId());
        if ($post) {
            try {
                $this->postIndexer->remove($post);
            } catch (ResponseException $e) {
                //document miss do nothing
            }
        }
    }

    public function onPostUpdate(PostEvent $event) {
        $post = $this->postService->fetchOne($event->getPostId());
        if ($post == null) {
            return;
        }

        $topic = $this->topicService->fetchOne($post->topicId);
        if ($topic && $this->topicCanIndex($topic)) {
            try {
                $this->postIndexer->update($post);
            } catch (ResponseException $e) {
                //document miss do nothing
            }
        }
    }

    public function onTopicFollow(TopicFollowingEvent $event) {
        $topicId = $event->getTopicId();
        $userId = $event->getUserId();
        $isFollowing = $this->topicFollowingService->isFollowing($userId, $topicId);
        if ($isFollowing == false) {
            return;
        }

        $followEntity = new TopicUserFollowing();
        $followEntity->topicId = $topicId;
        $followEntity->userId = $userId;
        $followEntity->state = 1;
        $this->topicFollowerIndexer->add($followEntity);

    }

    public function onTopicUnfollow(TopicFollowingEvent $event) {
        $topicId = $event->getTopicId();
        $userId = $event->getUserId();
        $isFollowing = $this->topicFollowingService->isFollowing($userId, $topicId);
        if ($isFollowing) {
            return;
        }

        $followEntity = new TopicUserFollowing();
        $followEntity->topicId = $topicId;
        $followEntity->userId = $userId;
        $followEntity->state = 1;

        try {
            $this->topicFollowerIndexer->remove($followEntity);
        } catch (ResponseException $e) {
            //document miss do nothing
        }
    }

    public function onUserRename(AccountEvent $event) {
        $userId = $event->getAccountId();
        $account = $this->accountService->fetchOne($userId);
        if ($account) {
            try {
                $this->accountIndexer->update($account);
            } catch (ResponseException $e) {
                //document miss do nothing
            }
        }

        $itor = $this->topicFollowingService->getUserFolloweeIterator($userId);
        $itor->setStep(200);
        foreach ($itor as $topicIds) {
            $entities = array();
            foreach ($topicIds as $topicId) {
                $entity = new TopicUserFollowing();
                $entity->topicId = $topicId;
                $entity->userId = $userId;
                $entity->state = 1;
                $entities[] = $entity;
            }
            try {
                $this->topicFollowerIndexer->update($entities);
            } catch (BulkResponseException $e) {
                //document miss do nothing
            }
        }
    }
}