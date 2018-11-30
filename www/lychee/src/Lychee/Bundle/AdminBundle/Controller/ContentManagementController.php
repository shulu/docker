<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 28/09/2016
 * Time: 5:33 PM
 */

namespace Lychee\Bundle\AdminBundle\Controller;

use Lychee\Bundle\AdminBundle\Entity\Favor;
use Lychee\Bundle\AdminBundle\Entity\Tag;
use Lychee\Bundle\AdminBundle\Service\ManagerLog\OperationType;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Post\Exception\PostNotFoundException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/content")
 * Class ContentManagementController
 * @package Lychee\Bundle\AdminBundle\Controller
 */
class ContentManagementController extends BaseController {

	public function getTitle() {
		return '内容管理';
	}

	/**
	 * @Route("/")
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function indexAction() {
		return $this->redirect($this->generateUrl('lychee_admin_contentmanagement_posttag'));
	}

	/**
	 * @return \Lychee\Bundle\AdminBundle\Service\TagService
	 */
	private function getTagService() {
		return $this->get('lychee_admin.service.tag');
	}

	/**
	 * @Route("/post/tag")
	 * @Template
	 * @return array
	 */
	public function postTagAction() {
		$tagService = $this->getTagService();
		$tags = $tagService->fetchAllTags();
		$userIds = array_unique(array_map(function(Tag $tag) {
			return $tag->getCreatorId();
		}, $tags));

		return $this->response('标签', [
			'tags' => $tagService->fetchAllTags(),
			'creators' => $this->manager()->fetchByIds($userIds),
		]);
	}

	/**
	 * @Route("/post/tag/create")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function createPostTagAction(Request $request) {
		$id = $request->request->get('tag_id');
		$name = $request->request->get('name');
		$tagService = $this->getTagService();
		if (!$id) {
			$tagService->createTag($name, $this->getUser()->id);
		} else {
			$tagService->updateTag($id, $name);
		}

		return $this->redirect($this->generateUrl('lychee_admin_contentmanagement_posttag'));
	}

	/**
	 * @Route("/post/tag/remove")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function removePostTagAction(Request $request) {
		$id = $request->request->get('id');
		$tagService = $this->getTagService();
		/** @var Tag $tag */
		$tag = $tagService->fetchOne($id);
		if ($tag) {
			$tagService->removeTag($tag);
		}

		return $this->redirect($request->headers->get('referer'));
	}

	/**
	 * @Route("/post/add_post_to_tag")
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
	 * @Route("/post/posts_in_tag/{tagId}", requirements={"tagId" = "\d+"})
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
	 * @Route("/post/tag/{tagId}/{page}", requirements={"tagId" = "\d+", "page" = "\d+"})
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
	 * @Route("/post/top")
	 * @Method("POST")
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function postTopAction(Request $request)
	{
		$postId = $request->request->get('id');
		$this->postSticky()->stickPost($postId);
		$this->managerLog()->log($this->getUser()->id, OperationType::STICKY_POST, $postId);

		return new JsonResponse(array('id' => $postId));
	}

	/**
	 * @Route("/post/top_cancel")
	 * @Method("POST")
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function postTopCancelAction(Request $request)
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
	public function topPostsAction() {
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
//		$managerLogs = ArrayUtility::mapByColumn(
//			$this->managerLog()->fetchLogsByTypeAndTargetIds(OperationType::STICKY_POST, $result['managerStickyPost']),
//			'targetId'
//		);
		$managers = ArrayUtility::mapByColumn($this->manager()->listManagers(), 'id');

		return $this->response('置顶帖子', array(
			'posts' => $posts,
			'authors' => $authors,
			'topics' => $topics,
			'topicManagers' => $topicManagers,
//			'managerLogs' => $managerLogs,
			'managers' => $managers,
		));
	}

	/**
	 * @Route("/post/favor")
	 * @Method("GET")
	 * @Template
	 * @return array
	 */
	public function postFavorAction() {
		return $this->response('收藏帖子', [
			'tags' => $this->getTagService()->fetchAllTags(),
		]);
	}

	/**
	 * @Route("/post/favor/load")
	 * @Method("GET")
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function postFavorLoadAction(Request $request) {
		$cursor = $request->query->get('cursor');
		$favors = $this->adminFavor()->iterator($cursor, 20);
		$postIds = array_map(function (Favor $favor) {
			return $favor->getPostId();
		}, $favors);
		$posts = $this->post()->fetch($postIds);
		krsort($posts);
		$result = $this->getCardData($posts);

		return new JsonResponse(array('total' => count($result), 'result' => $result));
	}

	/**
	 * @Route("/post/favor/toggle")
	 * @Method("POST")
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function togglePostFavorAction(Request $request) {
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
	public function postUnfoldAction(Request $request) {
		$id = $request->request->get('id');
		$this->post()->unfold($id);

		return new JsonResponse();
	}

}