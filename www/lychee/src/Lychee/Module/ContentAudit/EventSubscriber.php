<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 5/26/16
 * Time: 8:52 PM
 */

namespace Lychee\Module\ContentAudit;


use Lychee\Component\Storage\StorageException;
use Lychee\Component\Storage\StorageInterface;
use Lychee\Module\Account\AccountEvent;
use Lychee\Module\Account\AccountService;
use Lychee\Module\ContentAudit\Entity\ImageReview;
use Lychee\Module\ContentAudit\Entity\ImageReviewSource;
use Lychee\Module\Post\PostAnnotation;
use Lychee\Module\Post\PostEvent;
use Lychee\Module\Post\PostService;
use Lychee\Module\Topic\TopicEvent;
use Lychee\Module\Topic\TopicService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventSubscriber implements EventSubscriberInterface {

    /**
     * @var PostService $postService
     */
    private $postService;

    /**
     * @var ImageReviewService $imageReviewService
     */
    private $imageReviewService;
    
    private $environment;
    
    private $storageService;

    private $accountService;

    private $topicService;

    private $cacheDir;

    public static function getSubscribedEvents() {
        return [
            PostEvent::CREATE => 'onPostEvent',
            PostEvent::UPDATE => 'onPostEvent',
	        PostEvent::DELETE => 'onPostDelete',
            AccountEvent::CREATE => 'onAccountEvent',
            AccountEvent::UPDATE => 'onAccountEvent',
            TopicEvent::CREATE => 'onTopicEvent',
            TopicEvent::UPDATE => 'onTopicEvent',
        ];
    }
    
    public function __construct(
        PostService $postService,
        ImageReviewService $imageReviewService,
        $environment,
        StorageInterface $storageService,
        AccountService $accountService,
        TopicService $topicService,
        $cacheDir
    ) {
        $this->postService = $postService;
        $this->imageReviewService = $imageReviewService;
        $this->environment = $environment;
        $this->storageService = $storageService;
        $this->accountService = $accountService;
        $this->topicService = $topicService;
        $this->cacheDir = $cacheDir;
    }

    private function auditImage(ImageReview $imageReview) {
        $isRejectAudit = $this->imageReviewService->isRejectAudit($imageReview);
        if ($isRejectAudit) {
            // Delete photo and post
            try {
                $this->storageService->freeze($imageReview->image);
            } catch (StorageException $e) {
            }
            $result = ImageReview::RESULT_REJECT;
        } else {
            $result = ImageReview::RESULT_PASS;
        }
        $this->imageReviewService->setReviewResult($imageReview, $result);
        
        return $result;
    }
    
    public function onPostEvent(PostEvent $event) {
        $postId = $event->getPostId();
        $post = $this->postService->fetchOne($postId);
        $annotation = json_decode($post->annotation);
        if ($annotation && isset($annotation->{PostAnnotation::MULTI_PHOTOS})) {
            $photos = $annotation->{PostAnnotation::MULTI_PHOTOS};
            $gifDir = implode(DIRECTORY_SEPARATOR, [$this->cacheDir, 'gif_cache']);
            @mkdir($gifDir, 0777, true);
            $frames = [];
            $images = [];
            foreach ($photos as $photo) {
                if (preg_match('/^https*:\/\/(qn|dl)\.ciyo(con)*\.(com|cn)/i', $photo)) {
                    $photoUrl = strstr($photo, '?', true);
                    if (!$photoUrl) {
                        $photoUrl = $photo;
                    }
                    $imageInfo = @file_get_contents($photoUrl . '?imageInfo');
                    if (false !== $imageInfo && $imageInfoJson = json_decode($imageInfo)) {
                        /**
                         * {
                         *      format: "gif",
                         *      width: 240,
                         *      height: 185,
                         *      colorModel: "palette0",
                         *      frameNumber: 134
                         * }
                         */
                        if ($imageInfoJson->format === 'gif') {
                            $frames[] = $photo;
                        } else {
                            $images[] = $photo;
                        }
                    }
                }
            }
            $this->imageReviewService->reviewGIF($frames, ImageReviewSource::TYPE_POST, $postId);
            $this->imageReviewService->review($images, ImageReviewSource::TYPE_POST, $postId);
            $imgReviews = $this->imageReviewService->getReviewResult(ImageReviewSource::TYPE_POST, $postId);
            foreach ($imgReviews as $ir) {
                /**
                 * @var $ir ImageReview
                 */
                if ($this->auditImage($ir) == ImageReview::RESULT_REJECT) {
                    $this->postService->delete($postId);
                }
            }
        }
    }

    public function onAccountEvent(AccountEvent $event) {
        $accountId = $event->getAccountId();
        $account = $this->accountService->fetchOne($accountId);
        if ($account && $account->avatarUrl) {
            $this->imageReviewService->review([$account->avatarUrl], ImageReviewSource::TYPE_USER_AVATAR, $accountId);
            $imageReview = $this->imageReviewService->getReviewResult(ImageReviewSource::TYPE_USER_AVATAR, $accountId);
            if ($imageReview) {
                foreach ($imageReview as $ir) {
                    $this->auditImage($ir);
                }
            }
        }
    }

    public function onTopicEvent(TopicEvent $event) {
        $topicId = $event->getTopicId();
        $topic = $this->topicService->fetchOne($topicId);
        if ($topic && $topic->indexImageUrl) {
            $this->imageReviewService->review([$topic->indexImageUrl], ImageReviewSource::TYPE_TOPIC_COVER, $topicId);
            $imageReview = $this->imageReviewService->getReviewResult(ImageReviewSource::TYPE_TOPIC_COVER, $event->getTopicId());
            if ($imageReview) {
                foreach ($imageReview as $ir) {
                    $this->auditImage($ir);
                }
            }
        }
    }

	public function onPostDelete(PostEvent $event) {
//        帖子删除不处理图片审核的关联信息，为了可以让已删图片可以恢复
//		$postId = $event->getPostId();
//		$this->imageReviewService->removeSource(ImageReviewSource::TYPE_POST, $postId);
	}
}