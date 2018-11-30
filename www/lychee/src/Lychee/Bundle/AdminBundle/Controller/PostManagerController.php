<?php

namespace Lychee\Bundle\AdminBundle\Controller;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Lychee\Bundle\AdminBundle\EventListener\PostEvent;
use Lychee\Bundle\AdminBundle\Service\ManagerLog\OperationType;
use Lychee\Bundle\CoreBundle\Entity\Comment;
use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Foundation\ImageUtility;
use Lychee\Module\Notification\NotificationService;
use Lychee\Module\Post\Exception\PostNotFoundException;
use Lychee\Module\Post\PostAnnotation;
use Lychee\Module\Recommendation\RecommendationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class PostManagerController
 * @package Lychee\Bundle\AdminBundle\Controller
 * @Route("/post_manager")
 */
class PostManagerController extends BaseController
{
    use ModuleAwareTrait;

    public function getTitle()
    {
        return '帖子管理';
    }

    /**
     * @Route("/")
     * @Method("GET")
     * @Template
     * @return array
     */
    public function indexAction()
    {
        return $this->response('帖子查询', [
            'tags' => $this->getTagService()->fetchAllTags(),
        ]);
    }

    /**
     * @Route("/query")
     * @Method("GET")
     * @param Request $request
     * @return JsonResponse
     */
    public function queryAction(Request $request) {
        $keyword = $request->query->get('q');
        $includeLink = $request->query->get('include_link');
        $resourceType = $request->query->get('resource_type');
        $page = $request->query->get('p', 0);
        $posts = [];
        if (1 == $page) {
            $post = $this->post()->fetchOne($keyword);
            if (null !== $post) {
                $posts[$post->id] = $post;
            }
        }
        if ($resourceType) {
            $searchServiceName = 'lychee.module.search.resourcePostSearcher';
        } else {
            $searchServiceName = 'lychee.module.search.postSearcher';
        }
        /**
         * @var \Lychee\Module\Search\AbstractSearcher $postSearcher
         */
        $postSearcher = $this->container->get($searchServiceName);
        $count = 10;
        $offset = ($page - 1) * $count;
        if ($includeLink && !$resourceType) {
            if (!empty($posts)) {
                $posts = array_reduce($posts, function ($result, $post) {
                    is_array($result) || $result = [];
                    if (preg_match('/(http[s]{0,1}:\/\/\S+)/i', $post->content)) {
                        $result[] = $post;
                    }

                    return $result;
                });
            }
            while ($postIds = $postSearcher->search($keyword, $offset, $count)) {
                $partialPosts = array_reduce($this->post()->fetch($postIds), function ($result, $post) {
                    is_array($result) || $result = [];
                    if (preg_match('/(http[s]{0,1}:\/\/\S+)/i', $post->content)) {
                        $result[] = $post;
                    }

                    return $result;
                });
                if (!empty($partialPosts)) {
                    $posts = array_merge($posts, $partialPosts);
                    if (count($posts) >= $count) {
                        $page += 1;
                        break;
                    }
                }
                $offset += $count;
                $page += 1;
            }
        } else {
            $postIds = $postSearcher->search($keyword, $offset, $count);
            $posts = array_merge($posts, $this->post()->fetch($postIds));
        }
        krsort($posts);
        $result = $this->getCardData($posts);

        return new JsonResponse([
            'result' => $result,
            'total' => count($result),
            'nextPage' => $page + 1,
        ]);
    }


    /**
     * @Route("/delete")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function deleteAction(Request $request)
    {
        $postId = $request->request->get('post_id');
        $post = $this->post()->fetchOne($postId);
        if (null === $post) {
            throw $this->createNotFoundException('Post Not Found');
        }
        $this->post()->delete($post->id);
	    /** @var NotificationService $topicNotification */
	    $topicNotification = $this->get('lychee.module.notification');
	    $topicNotification->notifyIllegalPostDeletedBySystemEvent($post->id);
        $this->adminEventDispatcher()->dispatch(PostEvent::DELETE, new PostEvent($postId, $this->getUser()->id));

        return new JsonResponse();
    }

    /**
     * @Route("/{id}", requirements={"id" = "\d+"})
     * @Template()
     * @param $id
     * @return array
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function detailAction($id)
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
     * @Route("/change_topic")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function changeTopicAction(Request $request)
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

        return $this->redirect($this->generateUrl('lychee_admin_postmanager_detail', array(
            'id' => $postId,
        )));
    }

    /**
     * @Route("/top")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function topAction(Request $request)
    {
        $postId = $request->request->get('id');
        $this->postSticky()->stickPost($postId);
        $this->managerLog()->log($this->getUser()->id, OperationType::STICKY_POST, $postId);

        return new JsonResponse(array('id' => $postId));
    }

    /**
     * @Route("/top_cancel")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function topCancelAction(Request $request)
    {
        $postId = $request->request->get('id');
        $this->postSticky()->unstickPost($postId);

        return new JsonResponse();
    }

    /**
     * @Route("/top_posts")
     * @Template
     * @return array
     */
    public function topPostsAction()
    {
        $iterator = $this->postSticky()->getStickyPostIdIterator();
        $topPosts = $iterator->setCursor(0)->setStep(1000)->current();
        $posts = $this->post()->fetch($topPosts);

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
        $topicManagerIds = array_map(function($topic) {
            return $topic->managerId;
        }, $topics);
        $topicManagers = $this->account()->fetch($topicManagerIds);
        $managerLogs = ArrayUtility::mapByColumn(
            $this->managerLog()->fetchLogsByTypeAndTargetIds(OperationType::STICKY_POST, $result['managerStickyPost']),
            'targetId'
        );
        $managers = ArrayUtility::mapByColumn($this->manager()->listManagers(), 'id');

        return $this->response('置顶帖子', array(
            'posts' => $posts,
            'authors' => $authors,
            'topics' => $topics,
            'topicManagers' => $topicManagers,
            'managerLogs' => $managerLogs,
            'managers' => $managers,
        ));
    }


    /**
     * @Route("/save")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function saveAction(Request $request)
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

        return $this->redirect($this->generateUrl('lychee_admin_postmanager_detail', array('id' => $post->id)));
    }

    /**
     * @Route("/list_by_author/{authorId}", requirements={"authorId" = "\d+"})
     * @Template
     * @param $authorId
     * @return array
     */
    public function listByAuthorAction($authorId)
    {
        return $this->response('帖子列表', array(
            'authorId' => $authorId,
            'tags' => $this->getTagService()->fetchAllTags(),
        ));
    }

    /**
     * @Route("/author/{authorId}/{cursor}", requirements={"authorId" = "\d+", "cursor" = "\d+"})
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
     * @Route("/load")
     * @Method("GET")
     * @param Request $request
     * @return JsonResponse
     */
    public function loadPostsAction(Request $request) {
        $authorId = $request->query->get('author_id');
        $cursor = $request->query->get('cursor', 0);
        $nextCursor = $request->query->get('next_cursor');
        do {
            if ($authorId) {
                $author = $this->account()->fetchOne($authorId);
                if ($author) {
                    break;
                }
            }
            throw $this->createNotFoundException('Author Not Found.');
        } while (0);
        $postIds = $this->post()->fetchIdsByAuthorId($authorId, (int)$cursor, 20, $nextCursor);
        $posts = $this->post()->fetch($postIds);
        $posts = array_map(function ($post) {
            $post->createTime = $post->createTime->format('Y-m-d H:i');

            return $post;
        }, $posts);
        $topics = [];
        $postImages = [];
        if ($posts) {
            $postImages = array_reduce($posts, function ($result, $item) {
                is_array($result) || $result =[];
                $annotation = json_decode($item->annotation);
                $images = [];
                if ($annotation && isset($annotation->multi_photos)) {
                    $images = $annotation->multi_photos;
                } elseif ($annotation && isset($annotation->original_url)) {
                    $images[] = $annotation->original_url;
                } elseif ($item->imageUrl) {
                    $images[] = $item->imageUrl;
                }
                $result[$item->id] = $images;

                return $result;
            });
            $topicIds = array_reduce($posts, function ($result, $item) {
                is_array($result) || $result = [];
                if (!in_array($item->topicId, $result)) {
                    $result[] = $item->topicId;
                }

                return $result;
            });
            $topics = $this->topic()->fetch($topicIds);
            if ($topics) {
                $topics = ArrayUtility::mapByColumn($topics, 'id');
            }
        }

        return new JsonResponse([
            'author' => $author,
            'posts' => $posts,
            'postImages' => $postImages,
            'topics' => $topics,
            'cursor' => $cursor,
            'nextCursor' => $nextCursor,
        ]);
    }

    /**
     * @Route("/favor")
     * @Method("GET")
     * @Template
     * @return array
     */
    public function favorAction() {
        return $this->response('收藏帖子', [
            'tags' => $this->getTagService()->fetchAllTags(),
        ]);
    }

    /**
     * @Route("/favor/load")
     * @Method("GET")
     * @param Request $request
     * @return JsonResponse
     */
    public function favorLoadAction(Request $request) {
        $cursor = $request->query->get('cursor');
        $favors = $this->adminFavor()->iterator($cursor, 20);
        $postIds = array_map(function ($favor) {
            return $favor->getPostId();
        }, $favors);
        $posts = $this->post()->fetch($postIds);
        krsort($posts);
        $result = $this->getCardData($posts);

        return new JsonResponse(array('total' => count($result), 'result' => $result));
    }

    /**
     * @Route("/favor/toggle")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function toggleFavorAction(Request $request) {
        $postId = $request->request->get('id');
        if ($this->adminFavor()->hasPost($postId)) {
            $this->adminFavor()->removeByPost($postId);
            $deleted = 1;
            $this->getTagService()->removeTagPostByPostId($postId);
        } else {
            $this->adminFavor()->add($postId);
            $deleted = 0;
        }

        return new JsonResponse(['deleted' => $deleted]);
    }

    /**
     * @Route("/unfold")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     * @throws PostNotFoundException
     * @throws \Exception
     */
    public function unfoldAction(Request $request) {
        $id = $request->request->get('id');
        $this->post()->unfold($id);

        return new JsonResponse();
    }

    /**
     * @Route("/tag")
     * @Template
     * @return array
     */
    public function tagAction() {
        $tagService = $this->getTagService();
        $tags = $tagService->fetchAllTags();
        $userIds = array_unique(array_map(function($tag) {
            return $tag->getCreatorId();
        }, $tags));

        return $this->response('标签', [
            'tags' => $tagService->fetchAllTags(),
            'creators' => $this->manager()->fetchByIds($userIds),
        ]);
    }

    /**
     * @Route("/tag/create")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function createTagAction(Request $request) {
        $id = $request->request->get('tag_id');
        $name = $request->request->get('name');
        $tagService = $this->getTagService();
        if (!$id) {
            $tagService->createTag($name, $this->getUser()->id);
        } else {
            $tagService->updateTag($id, $name);
        }

        return $this->redirect($this->generateUrl('lychee_admin_postmanager_tag'));
    }

    /**
     * @Route("/tag/remove")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeTagAction(Request $request) {
        $id = $request->request->get('id');
        $tagService = $this->getTagService();
        $tag = $tagService->fetchOne($id);
        if ($tag) {
            $tagService->removeTag($tag);
        }

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @return \Lychee\Bundle\AdminBundle\Service\TagService
     */
    private function getTagService() {
        return $this->get('lychee_admin.service.tag');
    }

    /**
     * @Route("/add_post_to_tag")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function addPostToTagAction(Request $request) {
        $postId = $request->request->get('post_id');
        $tagIds = $request->request->get('tag_ids');
        if (is_array($tagIds)) {
            foreach ($tagIds as $tagId) {
                $this->getTagService()->addPostToTag($postId, $tagId);
            }
        }

        return new JsonResponse();
    }

    /**
     * @Route("/posts_in_tag/{tagId}", requirements={"tagId" = "\d+"})
     * @Template
     * @param $tagId
     * @return array
     */
    public function postsInTagAction($tagId) {
        $tag = $this->getTagService()->fetchOne($tagId);
        $name = '';
        if ($tag) {
            $name = $tag->getName();
        }

        return $this->response(sprintf("标签[%s]帖子", $name), [
            'tagId' => $tagId,
            'tags' => $this->getTagService()->fetchAllTags(),
        ]);
    }

    /**
     * @Route("/tag/{tagId}/{page}", requirements={"tagId" = "\d+", "page" = "\d+"})
     * @param $tagId
     * @param $page
     * @return JsonResponse
     */
    public function loadPostsInTagAction($tagId, $page = 1) {
        $postIds = $this->getTagService()->fetchIdsByTagId($tagId, $page);
        $posts = $this->post()->fetch($postIds);
        krsort($posts);
        $result = $this->getCardData($posts);

        return new JsonResponse([
            'result' => $result,
            'total' => count($result),
        ]);
    }

	/**
	 * @Route("/comments/{postId}", requirements={"postId" = "\d+"})
	 * @Template
	 * @param $postId
	 * @param Request $request
	 *
	 * @return array
	 */
    public function comments($postId, Request $request) {
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
     * @Route("/untreated_audit")
     * @Template
     * @return array
     */
    public function untreatedAuditAction(Request $request)
    {
        return $this->responsePostByAudit('待审核帖子', $request, \Lychee\Module\Post\Entity\PostAudit::UNTREATED_STATUS);
    }

    /**
     * @Route("/rejected_audit")
     * @Template
     * @return array
     */
    public function rejectedAuditAction(Request $request)
    {
        return $this->responsePostByAudit('已删帖子', $request, \Lychee\Module\Post\Entity\PostAudit::NOPASS_STATUS, 1);
    }

    /**
     * @Route("/rejected_audit_undelete")
     * @Template
     * @return array
     */
    public function rejectedAuditUnDeleteAction(Request $request)
    {
        return $this->responsePostByAudit('仅安卓通过帖子', $request, \Lychee\Module\Post\Entity\PostAudit::NOPASS_STATUS, 0);
    }

    /**
     * @Route("/passed_audit")
     * @Template
     * @return array
     */
    public function passedAuditAction(Request $request)
    {
        return $this->responsePostByAudit('已通过帖子', $request, \Lychee\Module\Post\Entity\PostAudit::PASS_STATUS);
    }

    private function responsePostByAudit($title, Request $request, $status, $deleted=0) {

        $query = $request->query->get('query');
        if (!is_array($query)) {
            $query = (array)$query;
        }
        $date = isset($query['date'])?$query['date']:'';
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        $query['date'] = $date;

        $source = isset($query['source'])?$query['source']:'';
        $query['source'] = $source;


        $cursor = $request->query->getInt('cursor', 0);
        if (0 >= $cursor) {
            $cursor = PHP_INT_MAX;
        }
        $page = $request->query->getInt('page', 1);
        $step = 20;
        $iterate = $this->post()->iterateForAuditPager(
            $status, [$date.' 00:00:00', $date.' 23:59:59'], $deleted, $source
        );
        $paginator = new \Lychee\Bundle\AdminBundle\Components\Foundation\Paginator($iterate);
        $paginator->setCursor($cursor)
            ->setPage($page)
            ->setStep($step)
            ->setStartPageNum($request->query->get('start_page', 1));

        $posts = $paginator->getResult();
        $sources = $this->post()->getAuditSources();
        foreach ($posts as $key => $item) {
            $item['source'] = $sources[$item['source']];
            $posts[$key] = $item;
        }

        $result = array_reduce($posts, function($result, $item) {
            if (false === isset($result['authors'])) {
                $result['authors'][] = $item['authorId'];
            } else {
                if (false === in_array($item['authorId'], $result['authors'])) {
                    $result['authors'][] = $item['authorId'];
                }
            }
            if (false === isset($result['topics'])) {
                $result['topics'][] = $item['topicId'];
            } else {
                if (false === in_array($item['topicId'], $result['topics'])) {
                    $result['topics'][] = $item['topicId'];
                }
            }
            $annotation = json_decode($item['annotation']);
            if (isset($annotation->original_url) && $annotation->original_url) {
                $result['images'][$item['id']] = $annotation->original_url;
            } else {
                $result['images'][$item['id']] = $item['imageUrl'];
            }
            return $result;
        });

        $authors = $this->account()->fetch($result['authors']);
        $topics = $this->topic()->fetch($result['topics']);

        return $this->response($title, array(
            'posts' => $posts,
            'authors' => $authors,
            'topics' => $topics,
            'sources' => $sources,
            'query' => $query,
            'paginator' => $paginator,
        ));
    }




    /**
     * @Route("/pass_audit")
     * @return array
     */
    public function passAuditAction(Request $request) {
        $postIds = $request->request->get('ids');
        if (!is_array($postIds)) {
            $postIds = (array)$postIds;
        }
        $this->post()->passAudit($postIds);
        return new JsonResponse(array('result'=>true));
    }

    /**
     * @Route("/reject_audit")
     * @return array
     */
    public function rejectAuditAction(Request $request) {
        $postIds = $request->request->get('ids');
        if (!is_array($postIds)) {
            $postIds = (array)$postIds;
        }
        $this->post()->rejectAuditAndDelete($postIds);
        return new JsonResponse(array('result'=>true));
    }

    /**
     * @Route("/reject_audit_undelete")
     * @return array
     */
    public function rejectAuditUndeleteAction(Request $request) {
        $postIds = $request->request->get('ids');
        if (!is_array($postIds)) {
            $postIds = (array)$postIds;
        }
        $this->post()->rejectAudit($postIds);
        return new JsonResponse(array('result'=>true));
    }

    /**
     * @Route("/audit_strategy")
     * @Template
     * @param $authorId
     * @return array
     */
    public function auditStrategyAction(Request $request)
    {
        $config = $request->request->get('config');
        if ($config) {
            $this->post()->updateAuditConfig(\Lychee\Module\Post\Entity\PostAuditConfig::STRATEGY_ID, $config['strategy']);
            return $this->redirect($request->headers->get('referer'));
        }

        $config = [];
        $config['strategy'] = $this->post()->getAuditStrategyConfig();
        return $this->response('内容审核策略', array(
            'config' => $config
        ));
    }
}
