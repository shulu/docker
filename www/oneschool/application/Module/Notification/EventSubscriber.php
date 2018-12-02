<?php
namespace Lychee\Module\Notification;

use JPush\JPushClient;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Foundation\CursorWrapper;
use Lychee\Constant;
use Lychee\Module\Account\AccountEvent;
use Lychee\Module\Comment\CommentEvent;
use Lychee\Module\Like\LikeEvent;
use Lychee\Module\Like\LikeType;
use Lychee\Module\Notification\Push\PushEventType;
use Lychee\Module\Post\PostEvent;
use Lychee\Module\Relation\RelationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Lychee\Module\Post\PostService;
use Lychee\Module\Account\AccountService;
use Lychee\Module\Comment\CommentService;
use Lychee\Module\Relation\RelationService;
use Lychee\Module\IM\IMService;
use Lychee\Module\Notification\NotificationCountingService;
use Lychee\Module\Notification\Push\PushService;
use JPush\Model;

class EventSubscriber implements EventSubscriberInterface {

    public static function getSubscribedEvents() {
        return array(
            PostEvent::CREATE => 'onPostCreate',
            CommentEvent::CREATE => 'onCommentCreate',
            RelationEvent::FOLLOW => 'onFollow',
            LikeEvent::LIKE => 'onLike',
            AccountEvent::CREATE => 'onAccountCreate',
        );
    }

    private $postService;
    private $notificationCountingService;
    private $topicNotificationService;
    private $accountService;
    private $commentService;
    private $relationService;
    private $imService;
    private $pushService;
    private $jpushClient;

    /**
     * @param NotificationCountingService $notificationCountingService
     * @param NotificationService $topicNotificationService
     * @param AccountService $accountService
     * @param PostService $postService
     * @param CommentService $commentService
     * @param RelationService $relationService
     * @param IMService $imService
     * @param PushService $pushService
	 * @param JPushClient $jpushClient
	 */
    public function __construct(
        $notificationCountingService,
        $topicNotificationService,
        $accountService,
        $postService,
        $commentService,
        $relationService,
        $imService,
        $pushService,
		JPushClient $jpushClient
    ) {
        $this->notificationCountingService = $notificationCountingService;
        $this->topicNotificationService = $topicNotificationService;
        $this->accountService = $accountService;
        $this->postService = $postService;
        $this->commentService = $commentService;
        $this->relationService = $relationService;
        $this->imService = $imService;
        $this->pushService = $pushService;
        $this->jpushClient = $jpushClient;
    }

    public function onPostCreate(PostEvent $event) {
        $post = $this->postService->fetchOne($event->getPostId());
        if ($post == null) {
            return;
        }
        if ($post->topicId == null) {
            return;
        }

        $mentionedUserIds = $this->extractMentionedUserIdFromContent($post->content);
        if (count($mentionedUserIds) > 0) {
            $userIdsToNotify = $this->filterMentionedUserIds($post->authorId, $mentionedUserIds);
            foreach ($userIdsToNotify as $userId) {
                $this->topicNotificationService->notifyMentionInPostEvent(
                    $userId, $post->topicId, $post->authorId, $post->id
                );
            }
        }

        $author = $this->accountService->fetchOne($post->authorId);
        if ($author == null) {
            return;
        }
        $pushContent = "[{$author->nickname}]发布了一张新帖子";

        $followerIter = new CursorWrapper(
            function($cursor, $count, &$nextCursor) use ($post) {
                if ($cursor === 0) {
                    $followees = $this->relationService->fetchFollowerIdsByUserId(
                        $post->authorId, $cursor, $count - 1, $nextCursor
                    );
                    return $followees;
                } else {
                    return $this->relationService->fetchFollowerIdsByUserId(
                        $post->authorId, $cursor, $count, $nextCursor
                    );
                }
            },
            1000
        );
//        if ($post->authorId != '31724') {
//        	return null;
//        }
        $pushableIds = [];
        gc_enable();
        while (true) {
            if (!$followerIter->valid()) {
                break;
            }
            $followerIds = $followerIter->current();
            $pushableUserIds = $this->pushService->filterPushableTo(PushEventType::POST, $post->authorId, $followerIds);
            $pushableIds = array_merge($pushableIds, $pushableUserIds);
            while (count($pushableIds) >= 1000) {
                $head = array_splice($pushableIds, 0, 1000);
//                $this->pushService->push($head, PushEventType::toString(PushEventType::POST), $pushContent, time() + 1800);
	            /**
	             * 默认的push服务基神用scala实现，因对scala无经验，所以暂时用php来实现推送。
	             */
	            $this->pushPostNotification($head, $post->id, $author->nickname);
            }
            gc_collect_cycles();
            $followerIter->next();
        }
	    if (count($pushableIds) > 0) {
//		    $this->pushService->push($pushableIds, PushEventType::toString(PushEventType::POST), $pushContent, time() + 1800);
		    $this->pushPostNotification($pushableIds, $post->id, $author->nickname);
	    }
    }

    private function pushPostNotification($audience, $postId, $authorNickname) {
	    $pushContent = "[{$authorNickname}]发布了一张新帖子";
	    $extras = [
	    	'type' => 'promotion',
		    'pid' => $postId
	    ];
	    $audience = array_map(function($item) { return (string)$item; }, $audience);
	    $pushPayload = $this->jpushClient->push();
	    $pushPayload->setAudience(Model\alias($audience));
	    $androidMessage = Model\android($pushContent, null, null, $extras);
	    $iosMessage = Model\ios($pushContent, 'default', null, null, $extras);
	    $notification = Model\notification($pushContent, $iosMessage, $androidMessage);
	    $pushPayload->setPlatform(Model\all);
	    $pushPayload->setNotification($notification);
	    $pushPayload->setOptions(Model\options(null, null, null, true));
	    try {
		    $pushPayload->send();
	    } catch (\Exception $e) {

	    }
    }

    public function onCommentCreate(CommentEvent $event) {
        $comment = $this->commentService->fetchOne($event->getCommentId());
        if ($comment == null) {
            return;
        }

        $post = $this->postService->fetchOne($comment->postId);
        if ($post == null) {
            return;
        }

        $excludedMentionedUserIds = array();
        if ($comment->repliedId > 0) {
            $repliedComment = $this->commentService->fetchOne($comment->repliedId);
            if ($repliedComment && $repliedComment->authorId != $comment->authorId) {
                $this->topicNotificationService->notifyCommentReplyEvent(
                    $repliedComment->authorId, $post->topicId, $comment->authorId, $comment->id
                );
                $excludedMentionedUserIds[] = $repliedComment->authorId;
            }
        } else {
            if ($post && $post->authorId != $comment->authorId) {
                $this->topicNotificationService->notifyCommentEvent(
                    $post->authorId, $post->topicId, $comment->authorId, $comment->id
                );
                $excludedMentionedUserIds[] = $post->authorId;
            }
        }

        if ($post->topicId == null) {
            return;
        }

        $mentionedUserIds = $this->extractMentionedUserIdFromContent($comment->content);
        $userIdsToNotify = ArrayUtility::diffValue($mentionedUserIds, $excludedMentionedUserIds);
        if (count($userIdsToNotify) > 0) {
            $userIdsToNotify = $this->filterMentionedUserIds($comment->authorId, $userIdsToNotify);

            foreach ($userIdsToNotify as $userId) {
                $this->topicNotificationService->notifyMentionInCommentEvent(
                    $userId, $post->topicId, $comment->authorId, $comment->id
                );
            }
        }
    }

    private function filterMentionedUserIds($fromUserId, $mentionedUserIds) {
        $noBlockingUserIds = $this->relationService->userBlackListFilterNoBlocking($mentionedUserIds, $fromUserId);
        return ArrayUtility::diffValue($noBlockingUserIds, array($fromUserId));
    }

    public function onFollow(RelationEvent $event) {
//        foreach ($event->getFolloweeIds() as $followeeId) {
//            if ($followeeId != Constant::CIYUANJIANG_ID) {
//                $this->imService->dispatchFollowing($event->getFollowerId(), $followeeId, 0);
//            }
//        }
    }

    public function onLike(LikeEvent $event) {
        if ($event->isLikedBefore()) {
            return;
        }

        if ($event->getLikeType() === LikeType::POST) {
            $post = $this->postService->fetchOne($event->getTargetId());
            if ($post && $post->authorId !== $event->getLikerId()) {
                $this->topicNotificationService->notifyLikePostEvent(
                    $post->authorId, $post->topicId, $event->getLikerId(), $post->id
                );
            }
        }
    }

    /**
     * @param string $content
     *
     * @return array
     */
    private function extractMentionedUserIdFromContent($content) {
        $pattern = '/(?>@([^\p{C}\p{Z}@]{2,36}))(?: |$)/u';
        if (preg_match_all($pattern, $content, $matches) > 0) {
            $userNames = $matches[1];
            $userIds = $this->accountService->fetchIdsByNicknames($userNames);
            return $userIds;
        } else {
            return array();
        }
    }

    public function onAccountCreate(AccountEvent $event) {
        if ($event->getAccountId()) {
            $this->notificationCountingService->resetOfficialUnreadCounting($event->getAccountId());
        }
    }

}