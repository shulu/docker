<?php

namespace Lychee\Bundle\AdminBundle\Controller;

use Lychee\Bundle\AdminBundle\Components\Foundation\Paginator;
use Lychee\Bundle\AdminBundle\Entity\CustomizeContent;
use Lychee\Bundle\AdminBundle\EventListener\EventDispatcher;
use Lychee\Bundle\AdminBundle\EventListener\PostEvent;
use Lychee\Bundle\AdminBundle\Service\DuplicateCustomizeContentException;
use Lychee\Bundle\AdminBundle\Service\ManagerLog\OperationType;
use Lychee\Bundle\AdminBundle\Service\TagService;
use Lychee\Bundle\CoreBundle\Entity\Comment;
use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Foundation\ImageUtility;
use Lychee\Component\KVStorage\MemcacheStorage;
use Lychee\Component\Storage\StorageException;
use Lychee\Module\Account\Exception\NicknameDuplicateException;
use Lychee\Module\ContentAudit\ContentAuditService;
use Lychee\Module\ContentAudit\Entity\AntiRubbish;
use Lychee\Module\ContentAudit\Entity\AuditImage;
use Lychee\Module\ContentAudit\Entity\ImageReview;
use Lychee\Module\ContentAudit\Entity\ImageReviewSource;
use Lychee\Module\ContentAudit\ImageReviewService;
use Lychee\Module\IM\Message;
use Lychee\Module\Notification\NotificationService;
use Lychee\Module\Post\Exception\PostNotFoundException;
use Lychee\Module\Post\PostAnnotation;
use Lychee\Module\Recommendation\Entity\RecommendationItem;
use Lychee\Module\Recommendation\Post\GroupManager;
use Lychee\Module\Recommendation\Post\GroupPostsService;
use Lychee\Module\Recommendation\RecommendationType;
use Lychee\Module\Topic\Entity\Topic;
use Lychee\Module\Topic\Entity\TopicCreatingApplication;
use Lychee\Module\Topic\TopicCategoryService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use Lychee\Module\ContentAudit\Entity\ImageReviewAuditConfig;

/**
 * Class ContentAuditController
 * @package Lychee\Bundle\AdminBundle\Controller
 * @Route("/audit")
 */
class ContentAuditController extends BaseController {

    /**
     * @return string
     */
    public function getTitle() {
        return '内容审核';
    }

    /**
     * @Route("/")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function indexAction(Request $request) {
	    return $this->redirect($this->generateUrl('lychee_admin_contentaudit_reportposts'));

	    //原来此处的代码是按时间段显示所有帖子
//        $now = new \DateTime();
//        $interval = $request->query->get('interval');
//        $startDateTime = $request->query->get('start_datetime');
//        $endDateTime = $request->query->get('end_datetime');
//        if (!$startDateTime) {
//            $startDateTime = $now->format('Y-m-d H:00');
//        }
//        if (!$endDateTime) {
//            $endDateTime = $now->format('Y-m-d H:i');
//        }
//        if ($interval) {
//            /**
//             * @var \DateTime $endDateTime
//             */
//            $endTimeObj = new \DateTime($endDateTime);
//	        $startTimeObj = clone $endTimeObj;
//	        $startTimeObj->modify('-' . $interval . 'min');
//	        $startDateTime = $startTimeObj->format('Y-m-d H:i');
//            $endDateTime = $endTimeObj->format('Y-m-d H:i');
//        }
//        $tagService = $this->get('lychee_admin.service.tag');
//        $tags = $tagService->fetchAllTags();
//
//        return $this->response('帖子审核', [
//            'startDateTime' => $startDateTime,
//            'endDateTime' => $endDateTime,
//            'tags' => $tags,
//        ]);
    }

    /**
     * @Route("/freeze")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function freezeAction(Request $request)
    {
        $postId = $request->request->get('post_id');
        $post = $this->post()->fetchOne($postId);
        if (null !== $post) {
            $this->post()->delete($post->id);
	        /** @var NotificationService $topicNotification */
	        $topicNotification = $this->get('lychee.module.notification');
	        $topicNotification->notifyIllegalPostDeletedBySystemEvent($post->id);
            $this->adminEventDispatcher()->dispatch(PostEvent::DELETE, new PostEvent($postId, $this->getUser()->id));

            return new JsonResponse(array('manager' => $this->getUser()->name));
        } else {
            throw $this->createNotFoundException('Post not found.');
        }
    }

    /**
     * @Route("/report_posts")
     * @Template
     * @param Request $request
     * @return array
     */
    public function reportPostsAction(Request $request)
    {
        $paginator = new Paginator($this->report()->reportPostsIterator());
        $paginator->setCursor($request->query->get('cursor', 0))
            ->setPage($request->query->get('page', 1))
            ->setStartPageNum($request->query->get('start_page', 1));

        $reportPosts = $paginator->getResult();
        $postIds = array_keys(ArrayUtility::mapByColumn($reportPosts, 'postId'));
        $posts = $this->post()->fetch($postIds);
        foreach ($posts as $post) {
            $post->imageUrl = $this->getOriginalImage($post);
        }

        $details = $this->getDetailFrom($posts);

        return $this->response('举报审核', array(
            'posts' => $posts,
            'authors' => $details['authors'],
            'topics' => $details['topics'],
            'reportPosts' => $reportPosts,
            'paginator' => $paginator,
        ));
    }

	/**
	 * @Route("/report_comments")
	 * @Template
	 * @param Request $request
	 *
	 * @return array
	 */
    public function reportComments(Request $request) {
	    $paginator = new Paginator($this->report()->reportCommentsIterator());
	    $paginator->setCursor($request->query->get('cursor', 0))
		    ->setPage($request->query->get('page', 1))
		    ->setStartPageNum($request->query->get('start_page', 1));
	    $reportComments = $paginator->getResult();
	    $commentIds = array_keys(ArrayUtility::mapByColumn($reportComments, 'commentId'));
	    $comments = $this->comment()->fetch($commentIds);
	    foreach ($comments as $comment) {
		    $comment->imageUrl = $this->getOriginalImage($comment);
	    }
	    $details = $this->getCommentDetail($comments);

	    return $this->response('举报评论审核', array(
		    'comments' => $comments,
		    'authors' => $details['authors'],
		    'posts' => $details['posts'],
		    'reportComments' => $reportComments,
		    'paginator' => $paginator,
	    ));
    }

	/**
	 * @Route("/report_comments/delete")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
    public function deleteComment(Request $request) {
    	$id = $request->request->get('id');
	    $this->comment()->delete($id);

	    return new JsonResponse();
    }

    /**
     * @param $posts
     * @return array
     */
    private function getDetailFrom($posts)
    {
        $result = array_reduce($posts, function($result, $item) {
            if (false === isset($result['authors'])) {
                $result['authors'][] = $item->authorId;
            } else {
                if (false === in_array($item->authorId, $result['authors'])) {
                    $result['authors'][] = $item->authorId;
                }
            }
            if (false === isset($result['topics'])) {
                $result['topics'][] = $item->topicId;
            } else {
                if (false === in_array($item->topicId, $result['topics'])) {
                    $result['topics'][] = $item->topicId;
                }
            }
            $annotation = json_decode($item->annotation);
            if (isset($annotation->original_url) && $annotation->original_url) {
                $result['images'][$item->id] = $annotation->original_url;
            } else {
                $result['images'][$item->id] = $item->imageUrl;
            }

            return $result;
        });

        $authors = $this->account()->fetch($result['authors']);
        $topics = $this->topic()->fetch($result['topics']);

        return array(
            'authors' => $authors,
            'topics' => $topics,
        );
    }

    private function getCommentDetail($comments) {
    	$result = array_reduce($comments, function($result, $item) {
    		/** @var Comment $item */
		    if (false === isset($result['authors'])) {
			    $result['authors'][] = $item->authorId;
		    } else {
			    if (false === in_array($item->authorId, $result['authors'])) {
				    $result['authors'][] = $item->authorId;
			    }
		    }
		    if (false === isset($result['posts'])) {
			    $result['posts'][] = $item->postId;
		    } else {
			    if (false === in_array($item->postId, $result['posts'])) {
				    $result['posts'][] = $item->postId;
			    }
		    }

		    return $result;
	    });
	    $authors = $this->account()->fetch($result['authors']);
	    $posts = $this->post()->fetch($result['posts']);

	    return [
	    	'authors' => $authors,
		    'posts' => $posts,
	    ];
    }

    /**
     * @Route("/freeze_account")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function freezeAccountAction(Request $request)
    {
        $id = $request->request->get('id');
        $this->account()->freeze($id);

        return new JsonResponse();
    }

    /**
     * @Route("/remove_avatar")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     * @throws \Lychee\Module\Account\Exception\NicknameDuplicateException
     */
    public function removeAvatarAction(Request $request)
    {
        $id = $request->request->get('id');
        $user = $this->account()->fetchOne($id);
        if (null !== $user) {
            $this->account()->updateInfo($user->id, $user->gender, null, $user->signature);
        }

        return new JsonResponse();
    }

	/**
	 * @Route("/load_posts/{cursor}")
	 * @param int $cursor
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
    public function loadPostsAction($cursor = 0, Request $request) {
	    $userAgent = $request->headers->get('User-Agent');
	    if (preg_match('/(android|iphone)/i', $userAgent)) {
		    $imageWidth = 180;
	    } else {
		    $imageWidth = 238;
	    }

    	$query = $request->query->get('query');
	    if ($query) {
	    	$cursor = $request->query->get('cursor');
		    if ($cursor > 0) {
		    	$postIds = [];
			    $nextCursor = $cursor;
		    } else {
			    $post = $this->post()->fetchOne($query);
			    if ($post) {
				    $postIds = [$post->id];
			    } else {
				    $postIds = [];
			    }
			    $nextCursor = $post->id;
		    }
	    } else {
		    $startTime = new \DateTime($request->query->get('start'));
		    $endTime = new \DateTime($request->query->get('end'));
		    if (!$startTime) {
			    $startTime = new \DateTime('-1 hour');
		    }
		    if (!$endTime) {
			    $endTime = new \DateTime();
		    }

		    $postIds = $this->post()->fetchIdsByDesc($cursor, 15, $nextCursor, $startTime, $endTime);
		    if (0 === $nextCursor && !empty($postIds)) {
			    $nextCursor = end($postIds);
			    reset($postIds);
		    }
	    }
	    $result = $this->getData($postIds, $imageWidth);

        return new JsonResponse([
        	'total' => count($result),
	        'result' => $result,
	        'nextCursor' => $nextCursor,
        ]);
    }

    /**
     * @Route("/post/recover")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function recoverPostAction(Request $request) {
        $postId = $request->request->get('id');
        try {
            $this->post()->undelete($postId);
        } catch (PostNotFoundException $e) {
            throw $this->createNotFoundException('Post Not Found');
        }

        return new JsonResponse();
    }

    /**
     * @Route("/topic")
     * @Template
     * @param Request $request
     * @return array
     */
    public function topicAction(Request $request) {
        $cursor = $request->query->get('cursor', 0);
        $prevCursor = $request->query->get('prev_cursor', 0);
        $count = 50;
        $topics = $this->topic()->fetchAll((int)$cursor, $count, $nextCursor);
        list($userIds, $topics) = array_reduce($topics, function($result, $item) {
            !is_array($result) && $result = [];
            $result[0][] = $item->managerId;
            $item->indexImageUrl = ImageUtility::resize($item->indexImageUrl, 200, 99999);
            $result[1][] = $item;

            return $result;
        });
        $users = $this->account()->fetch($userIds);

        return $this->response($this->getParameter('topic_name') . '审核', [
            'topics' => $topics,
            'users' => $users,
            'prevCursor' => $prevCursor,
            'cursor' => $cursor,
            'nextCursor' => $nextCursor,
        ]);
    }

    /**
     * @Route("/topic/delete")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteTopicAction(Request $request) {
        $tid = $request->request->get('tid');
        /**
         * @var \Lychee\Module\Topic\Deletion\DeferDeletor $topicDeletor
         */
        $topicDeletor = $this->get('lychee.module.topic.deletor');
        $topicDeletor->delete($tid);

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/content/delete")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     * @throws PostNotFoundException
     * @throws \Exception
     */
    public function deleteContentAction(Request $request) {
        $postId = $request->request->get('post_id');
        $imgUrl = $request->request->get('img_url');
        $post = $this->post()->fetchOne($postId);
        if ($post) {
            $this->post()->delete($postId);
	        /** @var NotificationService $topicNotification */
	        $topicNotification = $this->get('lychee.module.notification');
	        $topicNotification->notifyIllegalPostDeletedBySystemEvent($post->id);
            $this->adminEventDispatcher()->dispatch(PostEvent::DELETE, new PostEvent($postId, $this->getUser()->id));
            if ($imgUrl) {
                $imgStruct = explode('?', $imgUrl);
                $trashImg = $imgStruct[0];
                if ($this->container->get('kernel')->getEnvironment() === 'prod') {
                    try {
                        $this->storage()->delete($trashImg);
                    } catch (StorageException $e) {
                    }
                }

                if ($post->imageUrl === $trashImg) {
                    $post->imageUrl = null;
                }
                $annotation = json_decode($post->annotation);
                if ($annotation && !empty($annotation)) {
                    if (isset($annotation->multi_photos)) {
                        $imgs = [];
                        foreach ($annotation->multi_photos as $image) {
                            if ($image !== $trashImg) {
                                $imgs[] = $image;
                            }
                        }
                        $annotation->multi_photos = $imgs;
                    } elseif (isset($annotation->resource_thumb) && $annotation->resource_thumb === $trashImg) {
                        $annotation->resource_thumb = '';
                    }
                    $post->annotation = json_encode($annotation);
                }
                $this->post()->update($post);
                $this->get('lychee.module.content_audit')->deleteAuditImg($trashImg);
            }
        }

        return new JsonResponse();
    }

    /**
     * @param $content
     * @return null
     */
    private function getLinkFromContent($content) {
        preg_match('/(http[s]{0,1}:\/\/\S+)/i', $content, $matches);
        if (isset($matches[1])) {
            return $matches[1];
        } else {
            return null;
        }
    }

    /**
     * @param $iterator
     * @param $cursor
     * @param int $count
     * @return array
     */
    private function fetchData($iterator, $cursor, $count = 10) {
        if (0 == $cursor) {
            $cursor = PHP_INT_MAX;
        }
        $iterator->setStep($count)->setCursor((int)$cursor);
        $posts = $iterator->current();
        list($postIds, $topicIds, $authorIds) = array_reduce($posts, function($result, $post) {
            isset($result) || $result = [];
            $result[0][] = $post->id;
            $result[1][] = $post->topicId;
            $result[2][] = $post->authorId;

            return $result;
        });
        $topics = $this->topic()->fetch($topicIds);
        $authors = $this->account()->fetch($authorIds);
        $favors = $this->adminFavor()->filterFavorPostIds($postIds);

        return array($posts, $topics, $authors, $favors);
    }

    /**
     * @Route("/load_folded_posts/{cursor}")
     * @param int $cursor
     * @return JsonResponse
     */
    public function loadFoldedPostsAction($cursor = 0) {
        $request = $this->get('Request');
        $startDatetime = new \DateTime($request->query->get('start'));
        $endDatetime = new \DateTime($request->query->get('end'));
        $iterator = $this->post()->iterateFolded($startDatetime, $endDatetime);
        list($posts, $topics, $authors, $favors) = $this->fetchData($iterator, $cursor);
        $result = [];
        foreach ($posts as $post) {
            /**
             * @var $post \Lychee\Bundle\CoreBundle\Entity\Post
             */
            if (in_array($post->id, $favors)) {
                $favor = true;
            } else {
                $favor = false;
            }
            switch($post->type) {
                case Post::TYPE_RESOURCE:
                    $type = '资源';
                    break;
                case Post::TYPE_GROUP_CHAT:
                    $type = '群聊';
                    break;
                default:
                    $type = '普通';
            }
            if (!$post->deleted) {
                $result[] = [
                    'id' => $post->id,
                    'content' => $this->linkFilter($post->content),
                    'authorId' => $post->authorId,
                    'authorName' => $authors[$post->authorId]->nickname,
                    'type' => $type,
                    'topicId' => $post->topicId,
                    'topicName' => $topics[$post->topicId]->title,
                    'favor' => $favor,
                    'deleted' => $post->deleted,
                ];
            }
            switch ($post->type) {
                case Post::TYPE_RESOURCE:
                    $resource = $this->getResource($post);
                    if (!empty($resource)) {
                        $imageUrl = isset($resource['thumb'])? $resource['thumb'] : '';
                        if ($imageUrl && is_string($imageUrl)) {
                            $result[] = [
                                'id' => $post->id,
                                'imageUrl' => ImageUtility::resize($imageUrl, 240, 9999),
                                'content' => $post->content,
                                'favor' => $favor,
                                'deleted' => $post->deleted,
                            ];
                        }
                        $title = isset($resource['title'])? $resource['title'] : '';
                        $link = isset($resource['link'])? $resource['link'] : '';
                        $result[] = [
                            'id' => $post->id,
                            'content' => '[' . $title . '] ' . $link,
                            'type' => $type,
                            'link' => $link,
                            'favor' => $favor,
                            'deleted' => $post->deleted,
                        ];
                    }
                    break;
                default:
                    $images = $this->getAllImages($post);
                    if (!empty($images)) {
                        foreach ($images as $img) {
                            $result[] = [
                                'id' => $post->id,
                                'imageUrl' => ImageUtility::resize($img, 240, 9999),
                                'content' => $post->content,
                                'favor' => $favor,
                                'deleted' => $post->deleted,
                            ];
                        }
                    }
            }
        }

        return new JsonResponse(array('total' => count($result), 'result' => $result));
    }

    /**
     * @Route("/porn_audit")
     * @Template
     * @return array
     */
    public function pornAuditAction() {
        return $this->response('色情复审', [
            'tags' => $this->get('lychee_admin.service.tag')->fetchAllTags()
        ]);
    }

    /**
     * @Route("/load_porn_audit/{cursor}")
     * @param int $cursor
     * @return JsonResponse
     */
    public function loadPornAuditAction($cursor = 0) {
        /**
         * @var $em EntityManager
         */
        $em = $this->getDoctrine()->getManager();
        $auditImage = $em->getRepository(AuditImage::class);
        if ($cursor == 0) {
            $cursor = PHP_INT_MAX;
        }
        $query = $auditImage->createQueryBuilder('i')
            ->where('i.id < :cursor')
            ->andWhere('i.type = :type')
            ->orderBy('i.id', 'DESC')
            ->setMaxResults(50)
            ->setParameter('cursor', $cursor)
            ->setParameter('type', AuditImage::IMAGE_TYPE_PORN)
            ->getQuery();
        $auditImages = $query->getResult();
        /**
         * @var $lastAuditImg \Lychee\Module\ContentAudit\Entity\AuditImage
         */
        $lastAuditImg = $this->get('lychee.module.content_audit')->fetchAuditImg($cursor);
        $maxPostId = PHP_INT_MAX;
        if ($lastAuditImg) {
            $maxPostId = $lastAuditImg->getPostId();
        }
        $postIds = array_unique(array_map(function($image) { return $image->getPostId(); }, $auditImages));
        $postAudit = array_reduce($auditImages, function($result, $item) {
            $result[$item->getPostId()] = $item->getId();
            return $result;
        });
        $posts = $this->post()->fetch($postIds);
        $posts = ArrayUtility::mapByColumn($posts, 'id');
        krsort($posts);
        list($postIds, $topicIds, $authorIds) = array_reduce($posts, function($result, $post) {
            isset($result) || $result = [];
            $result[0][] = $post->id;
            $result[1][] = $post->topicId;
            $result[2][] = $post->authorId;

            return $result;
        });
        $topics = $this->topic()->fetch($topicIds);
        $authors = $this->account()->fetch($authorIds);
        $favors = $this->adminFavor()->filterFavorPostIds($postIds);

        $result = [];
        foreach ($posts as $post) {
            if ($post->id > $maxPostId) {
                continue;
            }
            /**
             * @var $post \Lychee\Bundle\CoreBundle\Entity\Post
             */
            if (in_array($post->id, $favors)) {
                $favor = true;
            } else {
                $favor = false;
            }
            switch($post->type) {
                case Post::TYPE_RESOURCE:
                    $type = '资源';
                    break;
                case Post::TYPE_GROUP_CHAT:
                    $type = '群聊';
                    break;
                default:
                    $type = '普通';
            }
            if (!$post->deleted) {
                $result[] = [
                    'id' => $post->id,
                    'audit_id' => $postAudit[$post->id],
                    'content' => $this->linkFilter($post->content),
                    'authorId' => $post->authorId,
                    'authorName' => $authors[$post->authorId]->nickname,
                    'type' => $type,
                    'topicId' => $post->topicId,
                    'topicName' => $topics[$post->topicId]->title,
                    'favor' => $favor,
                    'deleted' => $post->deleted,
                ];
            }
            foreach ($auditImages as $auditImage) {
                if ($auditImage->getPostId() == $post->id) {
                    $imageUrl = $auditImage->getImageUrl();
                    $result[] = [
                        'id' => $post->id,
                        'audit_id' => $postAudit[$post->id],
                        'imageUrl' => ImageUtility::resize($imageUrl, 240, 9999),
                        'content' => $post->content,
                        'favor' => $favor,
                        'deleted' => $post->deleted,
                    ];
                }
            }
        }

        return new JsonResponse(array('total' => count($result), 'result' => $result));
    }

    /**
     * @Route("/audit/legal")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function legalPostAction(Request $request) {
        $postId = $request->request->get('post_id');
        $this->get('lychee.module.content_audit')->deleteAuditPost($postId);

        return new JsonResponse();
    }

    /**
     * @Route("/recover_img_review")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function recoverImgReviewAction(Request $request) {

        $id = $request->request->get('id');
        /**
         * @var $imgReviewService ImageReviewService
         */
        $imgReviewService = $this->get('lychee.module.image_review');
        $imageReview = $imgReviewService->recoverImage($id);
        if ($imageReview) {
            $sources = $imgReviewService->recoverReviewSource($id);
        }

        return new JsonResponse();
    }

    /**
     * @Route("/img_review")
     * @Template
     * @return array
     */
    public function imgReviewAction(Request $request) {
        $date = $request->query->get('date');
        $dateArr = getdate();
        if (!$date) {
            $date = sprintf(
                "%s-%s-%s",
                $dateArr['year'],
                str_pad($dateArr['mon'], 2, '0', STR_PAD_LEFT),
                str_pad($dateArr['mday'], 2, '0', STR_PAD_LEFT)
            );
        }

        return $this->response('图片复审', [
            'date' => $date,
        ]);
    }


    /**
     * @Route("/reject_img_review")
     * @Template
     * @return array
     */
    public function rejectImgReviewAction(Request $request) {
        $date = $request->query->get('date');
        $dateArr = getdate();
        if (!$date) {
            $date = sprintf(
                "%s-%s-%s",
                $dateArr['year'],
                str_pad($dateArr['mon'], 2, '0', STR_PAD_LEFT),
                str_pad($dateArr['mday'], 2, '0', STR_PAD_LEFT)
            );
        }

        return $this->response('已删图片复审', [
            'date' => $date,
        ]);
    }

    /**
     * @Route("/fetch_reject_reviewed_img")
     * @param Request $request
     * @return JsonResponse
     */
    public function fetchRejectReviewedImgAction(Request $request) {
        $date = $request->query->get('date');
        $startTime = new \DateTime($date);
        $cursor = $request->query->get('cursor', 0);
        if (0 == $cursor) {
            $cursor = PHP_INT_MAX;
        }
        /**
         * @var $imgReviewService ImageReviewService
         */
        $imgReviewService = $this->get('lychee.module.image_review');
        $imgs = $imgReviewService->fetchImages($startTime, $cursor, $nextCursor, 30, 1);
        $imgs = array_map(function($item) {
            /* @var $item ImageReview */
            $title = '';
            switch ($item->label) {
                case ImageReview::LABEL_LEGAL:
                    $title .= '正常';
                    break;
                case ImageReview::LABEL_SEXY:
                    $title .= '性感';
                    break;
                case ImageReview::LABEL_PORN:
                    $title .= '色情';
                    break;
            }
            if ($item->review == true) {
                $title .= '[疑似]';
            } else {
                $title .= '[确定]';
            }
            $title .= ': ' . floor($item->rate * 100);
            $image = ImageUtility::resize($item->image, 120, 99999);
            $image = ImageUtility::formatFreezeUrl($image);
            $image = $this->storage()->privateUrl($image);

            return [
                'id' => $item->id,
                'title' => $title,
                'image' => $image,
                'label' => $item->label,
                'rate' => $item->rate,
                'last_review_time' => $item->lastReviewTime->format('m-d H:i'),
                'review_result' => $item->reviewResult,
            ];
        }, $imgs);

        return new JsonResponse([
            'total' => count($imgs),
            'result' => $imgs,
        ]);
    }

    /**
     * @Route("/fetch_reviewed_img")
     * @param Request $request
     * @return JsonResponse
     */
    public function fetchReviewedImgAction(Request $request) {
        $date = $request->query->get('date');
        $startTime = new \DateTime($date);
        $cursor = $request->query->get('cursor', 0);
        if (0 == $cursor) {
            $cursor = PHP_INT_MAX;
        }
        /**
         * @var $imgReviewService ImageReviewService
         */
        $imgReviewService = $this->get('lychee.module.image_review');
        $imgs = $imgReviewService->fetchImages($startTime, $cursor, $nextCursor);
        $imgs = array_map(function($item) {
            /* @var $item ImageReview */
            $title = '';
            switch ($item->label) {
                case ImageReview::LABEL_LEGAL:
                    $title .= '正常';
                    break;
                case ImageReview::LABEL_SEXY:
                    $title .= '性感';
                    break;
                case ImageReview::LABEL_PORN:
                    $title .= '色情';
                    break;
            }
            if ($item->review == true) {
                $title .= '[疑似]';
            } else {
                $title .= '[确定]';
            }
            $title .= ': ' . floor($item->rate * 100);
            return [
                'id' => $item->id,
                'title' => $title,
                'image' => ImageUtility::resize($item->image, 120, 99999),
                'label' => $item->label,
                'rate' => $item->rate,
                'last_review_time' => $item->lastReviewTime->format('m-d H:i'),
                'review_result' => $item->reviewResult,
            ];
        }, $imgs);
        
        return new JsonResponse([
            'total' => count($imgs),
            'result' => $imgs,
        ]);
    }

    /**
     * @Route("/delete_image_review")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteImageReviewAction(Request $request) {
        $id = $request->request->get('id');
        /**
         * @var $imgReviewService ImageReviewService
         */
        $imgReviewService = $this->get('lychee.module.image_review');
        $imageReview = $imgReviewService->deleteImage($id);
        if ($imageReview) {
            $sources = $imgReviewService->deleteReviewSource($id);
            /** @var ImageReviewSource $s */
            foreach ($sources as $s) {
                if ($s->sourceType == ImageReviewSource::TYPE_POST) {
	                /** @var NotificationService $topicNotification */
	                $topicNotification = $this->get('lychee.module.notification');
	                $topicNotification->notifyIllegalPostDeletedBySystemEvent($s->sourceId);
                    $this->adminEventDispatcher()->dispatch(
                        PostEvent::DELETE,
                        new PostEvent($s->sourceId, $this->getUser()->id)
                    );
                }
            }
        }

        return new JsonResponse();
    }

    /**
     * @Route("/review_source_detail/{reviewId}", requirements={"reviewId" = "\d+"})
     * @Template
     * @param $reviewId
     * @return array
     */
    public function reviewSourceDetailAction($reviewId) {
        /**
         * @var $imgReviewService ImageReviewService
         */
        $imgReviewService = $this->get('lychee.module.image_review');
        $imgReview = $imgReviewService->fetchOneImageReview($reviewId);
        $sources = $imgReviewService->fetchSourcesWithReviewId($reviewId);
        $postIds = [];
        $topicIds = [];
        $userIds = [];
        foreach ($sources as $s) {
            /** @var ImageReviewSource $s */
            switch ($s->sourceType) {
                case ImageReviewSource::TYPE_POST:
                    $postIds[] = $s->sourceId;
                    break;
                case ImageReviewSource::TYPE_TOPIC_COVER:
                    $topicIds[] = $s->sourceId;
                    break;
                case ImageReviewSource::TYPE_USER_AVATAR:
                    $userIds[] = $s->sourceId;
                    break;
            }
        }
        $posts = $this->post()->fetch($postIds);
        $topics = $this->topic()->fetch($topicIds);
        $creatorIds = array_unique(array_merge(array_map(function($p) {
            /** @var Post $p */
            return $p->authorId;
        }, $posts), array_map(function($t) {
            /** @var Topic $t */
            return $t->creatorId;
        }, $topics)));
        $creators = ArrayUtility::mapByColumn($this->account()->fetch($creatorIds), 'id');
        $users = $this->account()->fetch($userIds);

        return $this->response('色情审核(New)', [
            'imageReview' => $imgReview,
            'posts' => $posts,
            'topics' => $topics,
            'users' => $users,
            'creators' => $creators,
        ]);
    }

    /**
     * @Route("/classification/{type}", requirements={"type" = "\d+"})
     * @Template
     * @param int $type
     * @return array
     */
    public function classificationAction($type = 0) {

        /** @var GroupManager $groupManager */
        $groupManager = $this->get('lychee.module.recommendation.group_manager');
        $categories = $groupManager->getAllGroups();

        $tagService = $this->get('lychee_admin.service.tag');
        $tags = $tagService->fetchAllTags();

        $startTime = new \DateTime('+1 hour');
        $startTime = $startTime->format('Y-m-d H:00');
        $endTime = new \DateTime('-1 hour');
        $endTime = $endTime->format('Y-m-d H:00');
        
        return $this->response('分类热帖', [
            'type' => $type,
            'categories' => $categories,
            'tags' => $tags,
            'startTime' => $startTime,
            'endTime' => $endTime,
        ]);
    }

    /**
     * @Route("/fetch_posts_by_cat/{categoryId}/{cursor}")
     * @param $categoryId
     * @param $cursor
     * @param Request $request
     * @return JsonResponse
     */
    public function fetchPostsByCategory($categoryId, $cursor, Request $request) {
        $userAgent = $request->headers->get('User-Agent');
        if (preg_match('/(android|iphone)/i', $userAgent)) {
            $imageWidth = 180;
        } else {
            $imageWidth = 238;
        }

        if ($categoryId == 0) {
//            $topicIds = $this->recommendation()->fetchRecommendableTopicIds();
	        $items = $this->recommendation()->listAllRecommendationTopics();
	        $topicIds = array_map(function($item) {
	        	/** @var RecommendationItem $item */
	        	return $item->getTargetId();
	        }, $items);
            $startTime = $request->query->get('start_time');
            $endTime = $request->query->get('end_time');
            if ($startTime && $endTime) {
                $postIds = $this->post()->fetchPostIdsBySpecifiedTopics(
                    (int)$cursor,
                    $topicIds,
                    true,
                    new \DateTime($startTime),
                    new \DateTime($endTime)
                );
                rsort($postIds);
                $nextCursor = 0;
                !empty($postIds) && $nextCursor = $postIds[count($postIds) - 1];
            } else {
                $postIds = $this->post()->fetchIdsByTopicIds($topicIds, (int)$cursor, 10, $nextCursor);
            }
        } else {
            /** @var GroupPostsService $groupPostsService */
            $groupPostsService = $this->get('lychee.module.recommendation.group_posts');
            $postIds = $groupPostsService->listPostIdsInGroup($categoryId, $cursor, 10, $nextCursor);
        }
        $reverseOrder = true;
        $result = $this->getData($postIds, $imageWidth, $reverseOrder);

        return new JsonResponse([
            'total' => count($result),
            'result' => $result,
            'nextCursor' => $nextCursor,
        ]);
    }

    /**
     * @Route("/anti_rubbish/{page}")
     * @Template
     * @param int $page
     * @param Request $request
     * @return array
     */
    public function antiRubbishAction($page = 1, Request $request) {
        $startTime = $request->query->get('start_time', (new \DateTime('+1 hour'))->format('Y-m-d H:i:s'));
        $endTime = $request->query->get('end_time', (new \DateTime('-1 hour'))->format('Y-m-d H:i:s'));
        $count = 20;
        /** @var ContentAuditService $contentAuditService */
        $contentAuditService = $this->get('lychee.module.content_audit');
        $result = $contentAuditService->fetchAntiRubbishByTime($startTime, $endTime, $page, $count);
        list($userIds, $postIds, $commentIds) = array_reduce($result, function($result, $item) {
            /** @var AntiRubbish $item */
            is_array($result) || $result = [[], [], []];
            $result[0][] = $item->getUserId();
            switch ($item->getType()) {
                case AntiRubbish::TYPE_POST:
                    $result[1][] = $item->getTargetId();
                    break;
                case AntiRubbish::TYPE_COMMENT:
                    $result[2][] = $item->getTargetId();
                    break;
                default:
            }

            return $result;
        });
        $users = $this->account()->fetch($userIds);
        $posts = $this->post()->fetch($postIds);
        $postImgs = array_reduce($posts, function($result, $item) {
            /** @var Post $item */
            $result[$item->id] = [];
            $annotation = json_decode($item->annotation, true);
            if ($annotation && isset($annotation[PostAnnotation::MULTI_PHOTOS])) {
                $photos = $annotation[PostAnnotation::MULTI_PHOTOS];
                $result[$item->id] = $photos;
            }

            return $result;
        });
        $comments = $this->comment()->fetch($commentIds);
        $total = $contentAuditService->antiRubbishCountByTime($startTime, $endTime);
        $pageCount = ceil($total / $count);

        $ciyoUser = $this->account()->fetchOne($this->account()->getCiyuanjiangID());

        return $this->response('垃圾信息审核', [
            'result' => $result,
            'users' => $users,
            'posts' => $posts,
            'postImgs' => $postImgs,
            'comments' => $comments,
            'page' => $page,
            'total' => $total,
            'pageCount' => $pageCount,
            'ciyoUser' => $ciyoUser,
            'startTime' => $startTime,
            'endTime' => $endTime,
        ]);
    }

	/**
	 * @Route("/topic/audit/{cursor}")
	 * @Template
	 * @param int $cursor
	 * @return array
	 */
	public function topicAuditAction($cursor = 0) {
		$topics = $this->topic()->listCreatingApplication($cursor, 50, $nextCursor);
		/** @var TopicCategoryService $topicCategoriesService */
		$topicCategoriesService = $this->get('lychee.module.topic.category');
		$categories = ArrayUtility::mapByColumn($topicCategoriesService->getCurrentCategories(), 'id');
		$authorIds = array_unique(array_map(function($t) {
			/** @var TopicCreatingApplication $t */
			return $t->creatorId;
		}, $topics));
		$authors = $this->account()->fetch($authorIds);

		return $this->response('次元审核', [
			'topics' => $topics,
			'categories' => $categories,
			'authors' => $authors,
		]);
	}

	/**
	 * @Route("/topic/process_creating_application")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function topicProcessCreatingApplication(Request $request) {
		$operate = $request->request->get('operate');
		switch ($operate) {
			case 'confirm':
				$fn = 'confirmCreatingApplication';
				break;
			case 'reject':
				$fn = 'rejectCreatingApplication';
				break;
			default:
				return $this->redirectErrorPage('操作无效', $request);
		}
		$ids = $request->request->get('ids');
		if (is_array($ids)) {
			foreach ($ids as $id) {
				$this->topic()->$fn($id);
			}
		}

		return $this->redirect($this->generateUrl('lychee_admin_contentaudit_topicaudit'));
	}

	/**
	 * @Route("/post/{id}", requirements={"id" = "\d+"})
	 * @Template()
	 * @param $id
	 * @return array
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	public function postDetailAction($id)
	{
		$post = $this->post()->fetchOne($id);
		if (null === $post) {
			throw $this->createNotFoundException('Post does not exist.');
		}
		$annotation = json_decode($post->annotation);
		$author = $this->account()->fetchOne($post->authorId);
		$topic = null;
		if ($post->topicId) {
			$topic = $this->topic()->fetchOne($post->topicId);
		}
		$images = $this->getAllImages($post);
		$thumbnailImages = array_map(function($im) {
			return ImageUtility::resize($im, 320, 480);
		}, $images);

		return $this->response('帖子详情', array(
			'post' => $post,
			'images' => $images,
			'thumbnailImages' => $thumbnailImages,
			'author' => $author,
			'topic' => $topic,
			'annotation' => $annotation,
		));
	}

	/**
	 * @Route("/post/comments/{postId}", requirements={"postId" = "\d+"})
	 * @Template
	 * @param $postId
	 * @param Request $request
	 *
	 * @return array
	 */
	public function postComments($postId, Request $request) {
		$cursor = $request->query->get('cursor', 0);
		$commentCount = $this->comment()->getCommentCountByPostId($postId);
		$commentIds = $this->comment()->fetchIdsByPostId($postId, (int)$cursor, $commentCount, $nextCursor);
		$comments = $this->comment()->fetch($commentIds);
		$authorIds = array_map(function($c) {
			/** @var Comment $c */
			return $c->authorId;
		}, $comments);
		$authors = $this->account()->fetch($authorIds);

		return $this->response('帖子评论', [
			'comments' => $comments,
			'authors' => $authors,
			'cursor' => $cursor,
			'nextCursor' => $nextCursor,
			'commentCount' => $commentCount
		]);
	}

	/**
	 * @Route("/post/save")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	public function postSaveAction(Request $request)
	{
		$postRequest = $request->request;
		$post = $this->post()->fetchOne($postRequest->get('post_id'));
		if (null === $post) {
			throw $this->createNotFoundException('Post Not Found.');
		}
		$post->title = $postRequest->get('title');
		$post->content = preg_replace('/\r/', '', $postRequest->get('content'));
		$post->videoUrl = $postRequest->get('video');
		$post->audioUrl = $postRequest->get('audio');
		$post->siteUrl = $postRequest->get('site');
		$post->newsSource = $postRequest->get('news_source');
		$images = $postRequest->get('images');
		if (is_array($images) && !empty($images) && $images[0]) {
			$annotationJson = json_decode($post->annotation);
			$annotationJson->multi_photos = $images;
			$annotationJson->original_url = $images[0];
			$post->annotation = json_encode($annotationJson);
			$post->imageUrl = $images[0];
		}
		$this->post()->update($post);

		return $this->redirect($this->generateUrl('lychee_admin_contentaudit_postdetail', array('id' => $post->id)));
	}

	/**
	 * @Route("/user/detail/{userId}")
	 * @Template
	 * @param $userId
	 * @return array
	 */
	public function userDetailAction($userId) {
		$user = $this->account()->fetchOne($userId);
		$profile = $this->account()->fetchOneUserProfile($userId);
		$followers = $this->relation()->countUserFollowers($userId);
		$auth = $this->authentication()->getTokenByUserId($userId);
		$ciyoUserId = 31721;
		$ciyoUser = $this->account()->fetchOne($ciyoUserId);
		$lastSignin = $this->get('lychee.module.account.sign_in_recorder')->getUserRecord($userId);
		if ($lastSignin) {
			$lastLogin = $lastSignin->time->format('Y-m-d H:i:s');
		} else {
			$lastLogin = '没有登录信息';
		}

		return $this->response('用户详细信息', [
			'ciyoUser' => $ciyoUser,
			'user' => $user,
			'profile' => $profile,
			'followers' => $followers,
			'auth' => $auth,
			'lastLogin' => $lastLogin,
		]);
	}

	/**
	 * @Route("/post/change_topic")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	public function postChangeTopicAction(Request $request)
	{
		$postId = $request->request->get('post_id');
		$topicId = $request->request->get('topic_id');
		$post = $this->post()->fetchOne($postId);
		if (null === $post) {
			throw $this->createNotFoundException('Post does not exist.');
		}
		$topic = $this->topic()->fetchOne($topicId);
		if (null === $topic) {
			throw $this->createNotFoundException('Topic does not exist.');
		}
		$this->post()->updateTopic($postId, $topicId);
		$this->postSticky()->unstickPost($postId);

		return $this->redirect($this->generateUrl('lychee_admin_contentaudit_postdetail', array(
			'id' => $postId,
		)));
	}

	/**
	 * @Route("/post/list_by_author/{authorId}", requirements={"authorId" = "\d+"})
	 * @Template
	 * @param $authorId
	 * @return array
	 */
	public function postListByAuthorAction($authorId)
	{
		/** @var TagService $tagService */
		$tagService = $this->get('lychee_admin.service.tag');
		return $this->response('帖子列表', array(
			'authorId' => $authorId,
			'tags' => $tagService->fetchAllTags(),
		));
	}

	/**
	 * @Route("/post/author/{authorId}/{cursor}", requirements={"authorId" = "\d+", "cursor" = "\d+"})
	 * @param $authorId
	 * @param $cursor
	 * @return JsonResponse
	 */
	public function loadPostsByAuthor($authorId, $cursor) {
		$author = $this->account()->fetchOne($authorId);
		if (null === $author) {
			throw $this->createNotFoundException('Author not found');
		}
		$count = 15;
		$postIds = $this->post()->fetchIdsByAuthorId($authorId, (int)$cursor, $count);
		$posts = $this->post()->fetch($postIds);
		krsort($posts);
		$result = $this->getCardData($posts);

		return new JsonResponse(array('total' => count($result), 'result' => $result));
	}

	/**
	 * @Route("/topics/{managerId}/{cursor}", requirements={"managerId" = "\d+", "cursor" = "\d+"})
	 * @Template
	 * @param $managerId
	 * @param int $cursor
	 * @return array
	 */
	public function managerTopicsAction($managerId, $cursor = 0) {
		$request = $this->container->get('request_stack')->getCurrentRequest();
		$prevCursor = $request->query->get('prev_cursor');
		$count = 50;
		$topicIds = $this->topic()->fetchIdsByManager($managerId, $cursor, $count, $nextCursor);
		$topics = $this->topic()->fetch($topicIds);

		return $this->response('领主次元', [
			'managerId' => $managerId,
			'prevCursor' => $prevCursor,
			'cursor' => $cursor,
			'nextCursor' => $nextCursor,
			'topics' => $topics,
		]);
	}

	/**
	 * @Route("/comments_by_author/{authorId}")
	 * @Template
	 * @param $authorId
	 * @param Request $request
	 * @return array
	 */
	public function commentsByAuthorAction($authorId, Request $request) {
		$cursor = $request->query->get('cursor', 0);
		$prevCursor = $request->query->get('prev_cursor');
		$commentIds = $this->comment()->fetchIdsByAuthorId($authorId, (int)$cursor, 20, $nextCursor);
		$comments = $this->comment()->fetch($commentIds);

		return $this->response('用户评论', [
			'authorId' => $authorId,
			'comments' => $comments,
			'cursor' => $cursor,
			'prevCursor' => $prevCursor,
			'nextCursor' => $nextCursor,
		]);
	}

    /**
     * @param $authorId
     * @param Request $request
     * @Route("/recharge_detail/{authorId}")
     * @Template
     * @return array
     */
    public function rechargeDetailAction($authorId, Request $request) {
        $page = $request->query->get('page', 1);
        $count = 20;
        $total = 0;
        $results = $this->purchaseRecorder()->getRechargeDetailByAuthorId($authorId, $count,$page, $total);
        return $this->response('用户充值明细', array(
            'items' => $results,
            'page' => $page,
            'pageCount' => ceil($total/$count),
            'authorId' => $authorId
        ));
    }

    /**
     * @param $authorId
     * @param Request $request
     * @Route("/exchange_detail/{authorId}")
     * @Template
     * @return array
     */
    public function exchangeDetailAction($authorId, Request $request) {
        $page = $request->query->get('page', 1);
        $count = 20;
        $total = 0;
        $results = $this->purchaseRecorder()->getExchangeDetailByAuthorId($authorId, $count, $page, $total);
        return $this->response('用户兑换明细', array(
            'items' => $results,
            'page' => $page,
            'pageCount' => ceil($total/$count),
            'authorId' => $authorId
        ));
    }

	/**
	 * @Route("/user/block")
	 * @Method("POST")
	 * @param Request $request
	 * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function userBlockAction(Request $request)
	{
		$userId = $request->request->get('user_id');
		$cascadeDelete = $request->request->get('cascade_delete');
		$blockDevice = $request->request->get('block_device');
		$ajax = $request->request->get('freeze_user_ajax');
		$this->account()->freeze($userId);
		/**
		 * @var \Lychee\Module\Authentication\TokenIssuer $tokenService
		 */
		$tokenService = $this->get('lychee.module.authentication.token_issuer');
		$tokenService->revokeTokensByUser($userId);
		/** @var ContentAuditService $contentAuditService */
		$contentAuditService = $this->get('lychee.module.content_audit');
		$contentAuditService->removeUserFromAntiRubbish($userId);

		$managerLogDesc = [];
		if ($cascadeDelete == '1') {
			/**
			 * @var \Lychee\Module\Account\AccountCleaner $cleaner
			 */
			$cleaner = $this->get('lychee.module.account.posts_cleaner');
			$cleaner->cleanUser($userId);
			$managerLogDesc['delete_posts_and_comments'] = true;
		}
		if ($blockDevice == '1') {
			/**
			 * @var \Lychee\Module\Account\DeviceBlocker $blocker
			 */
			$blocker = $this->get('lychee.module.account.device_blocker');
			try {
				$blocker->blockUserDevice($userId);
			} catch (\Exception $e) {
				return $this->redirect($this->generateUrl('lychee_admin_error', [
					'errorMsg' => $e->getMessage(),
					'callbackUrl' => $request->headers->get('referer'),
				]));
			}
			$managerLogDesc['block_device'] = true;
		}
		$this->managerLog()->log($this->getUser()->id, OperationType::BLOCK_USER, $userId, $managerLogDesc);

		if ($ajax == 1) {
			return new JsonResponse();
		} else {
			$referer = $request->headers->get('referer');

			return $this->redirect($referer);
		}
	}

	/**
	 * @Route("/sendMessage")
	 * @Method("POST")
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function sendMessageAction(Request $request) {
		$from = $request->request->get('chat_from');
		$sendTo = $request->request->get('chat_to');
		$body = $request->request->get('message');
		if (!$sendTo) {
			throw $this->createNotFoundException('Receiver not found');
		}
		$to = [];
		array_push($to, $sendTo);
		$type = 0;
		$message = new Message();
		$message->from = $from;
		$message->to = $to;
		$message->type = $type;
		$message->time = time();
		$message->body = $body;
		$this->get('lychee.module.im')->dispatch([$message]);

		return new JsonResponse();
	}

	/**
	 * @Route("/change_nickname")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function changenicknameAction(Request $request) {
		$id = $request->request->get('id');
		$nickname = $request->request->get('nickname');
		try {
			$this->account()->updateNickname($id, $nickname);
		} catch (NicknameDuplicateException $e) {
			return $this->redirect($this->generateUrl('lychee_admin_error', [
				'errorMsg' => '昵称已存在',
				'callbackUrl' => $request->headers->get('referer'),
			]));
		} catch (\Exception $e) {
			return $this->redirect($this->generateUrl('lychee_admin_error', [
				'errorMsg' => $e->getMessage(),
				'callbackUrl' => $request->headers->get('referer'),
			]));
		}

		return $this->redirect($request->headers->get('referer'));
	}

	/**
	 * @Route("/user/unfreeze")
	 * @Method("POST")
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function unfreezeUserAction(Request $request)
	{
		$userId = $request->request->get('user_id');
		$this->account()->unfreeze($userId);
		$this->managerLog()->log($this->getUser()->id, OperationType::UNBLOCK_USER, $userId);

		return new JsonResponse();
	}

	/**
	 * @Route("/unblock_device")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function unblockDeviceAction(Request $request) {
		$userId = $request->request->get('user_id');
		/**
		 * @var \Lychee\Module\Account\DeviceBlocker $deviceBlocker
		 */
		$deviceBlocker = $this->get('lychee.module.account.device_blocker');
		$platformAndDevice = $deviceBlocker->getUserDeviceId($userId);
		if (is_array($platformAndDevice)) {
			if ($deviceBlocker->isDeviceBlocked($platformAndDevice[0], $platformAndDevice[1])) {
				$deviceBlocker->unblockDevice($platformAndDevice[0], $platformAndDevice[1]);
				$this->managerLog()->log($this->getUser()->id, OperationType::UNBLOCK_DEVICE, $userId, [
					'platform' => $platformAndDevice[0],
					'device' => $platformAndDevice[1]
				]);
			}
		}

		return $this->redirect($request->headers->get('referer'));
	}




    /**
     * @Route("/img_review_audit_config")
     * @Template
     * @param $authorId
     * @return array
     */
    public function imgReviewAuditConfigAction(Request $request)
    {
        $imgReviewService = $this->get('lychee.module.image_review');
        $config = $request->request->get('config');
        if ($config) {
            $configs = [];
            $configs[ImageReviewAuditConfig::TRASH_PORN_SURE_MIN_RATE_ID] = intval($config[ImageReviewAuditConfig::TRASH_PORN_SURE_MIN_RATE_ID]);
            $configs[ImageReviewAuditConfig::TRASH_PORN_UNSURE_MIN_RATE_ID] = intval($config[ImageReviewAuditConfig::TRASH_PORN_UNSURE_MIN_RATE_ID]);
            $configs[ImageReviewAuditConfig::TRASH_SEXY_SURE_MIN_RATE_ID] = intval($config[ImageReviewAuditConfig::TRASH_SEXY_SURE_MIN_RATE_ID]);
            $configs[ImageReviewAuditConfig::TRASH_SEXY_UNSURE_MIN_RATE_ID] = intval($config[ImageReviewAuditConfig::TRASH_SEXY_UNSURE_MIN_RATE_ID]);
            $imgReviewService->updateAuditConfigs($configs);
            return $this->redirect($request->headers->get('referer'));
        }

        $config = $imgReviewService->getAuditConfigs([
            ImageReviewAuditConfig::TRASH_PORN_SURE_MIN_RATE_ID,
            ImageReviewAuditConfig::TRASH_PORN_UNSURE_MIN_RATE_ID,
            ImageReviewAuditConfig::TRASH_SEXY_SURE_MIN_RATE_ID,
            ImageReviewAuditConfig::TRASH_SEXY_UNSURE_MIN_RATE_ID
        ]);

        return $this->response('图片审核配置', array(
            'config' => $config
        ));
    }

}
