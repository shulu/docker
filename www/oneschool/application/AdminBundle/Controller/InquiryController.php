<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 23/09/2016
 * Time: 4:17 PM
 */

namespace Lychee\Bundle\AdminBundle\Controller;

use Lychee\Bundle\AdminBundle\Service\ManagerLog\OperationType;
use Lychee\Module\Post\Exception\PostNotFoundException;

use Lychee\Bundle\AdminBundle\Service\GrayListService;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Account\Entity\UserVip;
use Lychee\Module\Account\Exception\EmailDuplicateException;
use Lychee\Module\Account\Exception\PhoneDuplicateException;
use Lychee\Module\Search\AbstractSearcher;

use Lychee\Bundle\AdminBundle\Entity\CustomizeContent;
use Lychee\Bundle\AdminBundle\Service\DuplicateCustomizeContentException;
use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Module\Recommendation\Entity\RecommendationItem;
use Lychee\Module\Recommendation\RecommendationType;
use Lychee\Module\Search\TopicSearcher;
use Lychee\Module\Topic\Entity\TopicCreatingApplication;
use Lychee\Module\Topic\Exception\RunOutOfCreatingQuotaException;
use Lychee\Module\Topic\Exception\TopicAlreadyExistException;
use Lychee\Module\Topic\TopicCategoryService;
use Lychee\Module\Topic\TopicParameter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Lychee\Bundle\AdminBundle\Components\Foundation\Paginator;
use Lychee\Bundle\CoreBundle\Entity\Comment;
use Lychee\Bundle\CoreBundle\Entity\User;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Foundation\ImageUtility;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


/**
 * @Route("/inquiry")
 * Class InquiryController
 * @package Lychee\Bundle\AdminBundle\Controller
 */
class InquiryController extends BaseController {

	use ModuleAwareTrait;

	const TOPIC_COUNT_PER_PAGE = 50;

	public function getTitle() {
		return '社区管理';
	}

	/**
	 * @Route("/inquireposts")
	 * @Method("GET")
	 * @Template
	 * @return array
	 */
	public function inquirePostsAction()
	{
		return $this->response('帖子查询', [
			'tags' => $this->getTagService()->fetchAllTags(),
				'writable' => false,
			]
		);
	}

	/**
	 * @Route("/")
	 * @Template
	 * @param Request $request
	 * @return mixed
	 */
	public function indexAction(Request $request)
	{
		$query = $request->query->get('query');
		$paginator = null;
		$nextCursor = null;
		if (!$query) {
			$cursor = $request->query->get('cursor', PHP_INT_MAX);
			if (0 >= $cursor) {
				$cursor = PHP_INT_MAX;
			}
			$iterator = $this->topic()->iterateTopic('DESC');
			$paginator = new Paginator($iterator);
			$paginator->setCursor($cursor)
			          ->setPage($request->query->get('page', 1))
			          ->setStep(30)
			          ->setStartPageNum($request->query->get('start_page', 1));
			$topics = $paginator->getResult();
		} else {
			$resultById = [];
			if (is_numeric($query)) {
				$resultById[] = $this->topic()->fetchOne($query);
			}
			$resultByKw = $this->topic()->fetchByKeyword($query, 0, self::TOPIC_COUNT_PER_PAGE, $nextCursor);
			if (is_array($resultById)) {
				$topics = array_merge($resultById, $resultByKw);
			} else {
				$topics = $resultByKw;
			}
		}
		/**
		 * @var \Lychee\Module\Topic\TopicCategoryService $topicCategoryService
		 */
		$topicCategoryService = $this->container->get('lychee.module.topic.category');
		$properties = $topicCategoryService->getProperties();
		$categories = $topicCategoryService->getCategories();

		return $this->response($this->container->getParameter('topic_name') . '管理', array(
			'paginator' => $paginator,
			'topics' => $topics,
			'query' => $query,
			'properties' => $properties,
			'categories' => $categories,
			'nextCursor' => $nextCursor,
		));
	}

	/**
	 * @return \Lychee\Bundle\AdminBundle\Service\TagService
	 */
	private function getTagService() {
		return $this->get('lychee_admin.service.tag');
	}

	/**
	 * @Route("/post/query")
	 * @Method("GET")
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function postQueryAction(Request $request) {
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
	 * @Route("/post/detail/{id}", requirements={"id" = "\d+"})
	 * @Template()
	 * @param $id
	 * @return array
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	public function postDetailAction($id) {
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
			'writable' => false
		));
	}

	/**
	 * @Route("/post/comments/{postId}")
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
	 * Route("/user")
	 * Template
	 * param Request $request
	 *
	 * return array
	 */
//	public function userAction(Request $request) {
//		$query = $request->query->get('query');
//		$users = array();
//		if (null !== $query) {
//			$users = $this->account()->fetchByKeyword($query, 0, 1000);
//			if (filter_var($query, FILTER_VALIDATE_INT)) {
//				$userQueryById = $this->account()->fetchOne($query);
//				if (null !== $userQueryById && !isset($users[$userQueryById->id])) {
//					$users[$userQueryById->id] = $userQueryById;
//				}
//			}
//		}
//		arsort($users);
//
//		$topicService = $this->topic();
//		$managerTopics = array_reduce($users, function($result, $user) use ($topicService) {
//			$managerId = $user->id;
//			$topicIds = $topicService->fetchIdsByManager($managerId, 0, 2, $nextCursor);
//			$topics = $topicService->fetch($topicIds);
//			if ($nextCursor) {
//				array_push($topics, []);
//			}
//			$result[$managerId] = $topics;
//
//			return $result;
//		});
//
//		$deviceBlock = array_reduce($users, function($result, $user) {
//			!$result && $result = [];
//			/**
//			 * @var \Lychee\Module\Account\DeviceBlocker $deviceBlocker
//			 */
//			$deviceBlocker = $this->get('lychee.module.account.device_blocker');
//			$platformAndDevice = $deviceBlocker->getUserDeviceId($user->id);
//			if (is_array($platformAndDevice)) {
//				if ($deviceBlocker->isDeviceBlocked($platformAndDevice[0], $platformAndDevice[1])) {
//					array_push($result, $user->id);
//				}
//			}
//
//			return $result;
//		});
//
//		return $this->response($this->getTitle(), array(
//			'users' => $users,
//			'genderMale' => User::GENDER_MALE,
//			'genderFemale' => User::GENDER_FEMALE,
//			'managerTopics' => $managerTopics,
//			'deviceBlock' => $deviceBlock,
//		));
//	}

	/**
	 * @Route("/user/detail/{userId}")
	 * @Template
	 * @param $userId
	 * @return array
	 */
	public function userDetail($userId) {
		$user = $this->account()->fetchOne($userId);
		$profile = $this->account()->fetchOneUserProfile($userId);
		$followers = $this->relation()->countUserFollowers($userId);
		$auth = $this->authentication()->getTokenByUserId($userId);
		$lastSignin = $this->get('lychee.module.account.sign_in_recorder')->getUserRecord($userId);
		if ($lastSignin) {
			$lastLogin = $lastSignin->time->format('Y-m-d H:i:s');
		} else {
			$lastLogin = '没有登录信息';
		}

		return $this->response('用户详细信息', [
			'user' => $user,
			'profile' => $profile,
			'followers' => $followers,
			'auth' => $auth,
			'lastLogin' => $lastLogin,
		]);
	}

	/**
	 * @Route("/user/post/{authorId}", requirements={"authorId" = "\d+"})
	 * @Template
	 * @param $authorId
	 * @return array
	 */
	public function userListPostByAuthor($authorId) {
		return $this->response('帖子列表', array(
			'authorId' => $authorId,
			'tags' => $this->getTagService()->fetchAllTags(),
		));
	}

	/**
	 * @Route("/user/post/data/{authorId}/{cursor}", requirements={"authorId" = "\d+", "cursor" = "\d+"})
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
	 * @Route("/user/topic/{managerId}/{cursor}", requirements={"managerId" = "\d+", "cursor" = "\d+"})
	 * @Template
	 * @param $managerId
	 * @param int $cursor
	 * @param Request $request
	 *
	 * @return array
	 */
	public function userTopics($managerId, $cursor = 0, Request $request) {
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
	 * @Route("/user/comment/{authorId}", requirements={"authorId" = "\d+"})
	 * @Template
	 * @param $authorId
	 * @param Request $request
	 *
	 * @return array
	 */
	public function userComments($authorId, Request $request) {
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
	 * @Route("/topic")
	 * @Template
	 * @param Request $request
	 *
	 * @return array
	 */
	public function topicAction(Request $request) {
		$query = $request->query->get('query');
		$paginator = null;
		$nextCursor = null;
		if (!$query) {
			$cursor = $request->query->get('cursor', PHP_INT_MAX);
			if (0 >= $cursor) {
				$cursor = PHP_INT_MAX;
			}

			$iterator = $this->topic()->iterateTopic('DESC');
			$paginator = new Paginator($iterator);
			$paginator->setCursor($cursor)
			          ->setPage($request->query->get('page', 1))
			          ->setStep(30)
			          ->setStartPageNum($request->query->get('start_page', 1));
			$topics = $paginator->getResult();
		} else {
			$resultById = [];
			if (is_numeric($query)) {
				$resultById[] = $this->topic()->fetchOne($query);
			}
			$resultByKw = $this->topic()->fetchByKeyword($query, 0, 50, $nextCursor);
			if (is_array($resultById)) {
				$topics = array_merge($resultById, $resultByKw);
			} else {
				$topics = $resultByKw;
			}
		}
		/**
		 * @var \Lychee\Module\Topic\TopicCategoryService $topicCategoryService
		 */
		$topicCategoryService = $this->container->get('lychee.module.topic.category');
		$properties = $topicCategoryService->getProperties();
		$categories = $topicCategoryService->getCategories();

		return $this->response($this->container->getParameter('topic_name') . '管理', array(
			'paginator' => $paginator,
			'topics' => $topics,
			'query' => $query,
			'properties' => $properties,
			'categories' => $categories,
			'nextCursor' => $nextCursor,
		));
	}

	/**
	 * @Route("/topic/detail/{topicId}", requirements={"topicId" = "\d+"})
	 * @Template
	 * @param $topicId
	 * @return array
	 */
	public function topicDetailAction($topicId) {
		$topic = $this->topic()->fetchOne($topicId);
		if (!$topic) {
			throw $this->createNotFoundException('Topic Not Found');
		}
		/**
		 * @var \Lychee\Module\Topic\TopicCategoryService $topicCategoryService
		 */
		$topicCategoryService = $this->get('lychee.module.topic.category');
		$topicProperty = $topicCategoryService->getPropertyByTopicId($topicId);
		$topicCategories = array_map(function($category) {
			return $category['name'];
		}, $topicCategoryService->getCategoriesByTopicId($topicId));
		$creator = null;
		if ($topic->creatorId) {
			$creator = $this->account()->fetchOne($topic->creatorId);
		}
		$manager = null;
		if ($topic->creatorId === $topic->managerId) {
			$manager = $creator;
		} elseif ($topic->managerId) {
			$manager = $this->account()->fetchOne($topic->managerId);
		}
		$topicFollowerCount = $this->topicFollowing()->getTopicsFollowerCounter(array($topicId))
		                           ->getCount($topicId);
		$topic->indexImageUrl = ImageUtility::resize($topic->indexImageUrl, 240, 9999);
		$topic->coverImageUrl = ImageUtility::resize($topic->coverImageUrl, 240, 9999);
		/**
		 * @var Request $request
		 */
		$request = $this->container->get('request_stack')->getCurrentRequest();
		$startDate = $request->query->get('start_date');
		$endDate = $request->query->get('end_date');
		if ($startDate && $endDate) {
			$startDate = new \DateTime($startDate);
			$endDate = new \DateTime($endDate);
			$postCount = $this->topic()->countPost($topicId, $startDate, $endDate);
			$commentCount = $this->topic()->countComment($topicId, $startDate, $endDate);
			$followerCount = $this->topic()->countFollower($topicId, $startDate, $endDate);
		} else {
			$postCount = $commentCount = $followerCount = null;
		}
		/**
		 * @var \Lychee\Module\Topic\TopicCategoryService $topicCategoryService
		 */
		$topicCategoryService = $this->container->get('lychee.module.topic.category');
		$properties = $topicCategoryService->getProperties();
		$categories = $topicCategoryService->getCategories();

		return $this->response('次元详情', [
			'topic' => $topic,
			'creator' => $creator,
			'manager' => $manager,
			'topicFollowerCount' => $topicFollowerCount,
			'postCount' => $postCount,
			'commentCount' => $commentCount,
			'followerCount' => $followerCount,
			'topicProperty' => $topicProperty,
			'topicCategories' => $topicCategories,
			'properties' => $properties,
			'categories' => $categories,
		]);
	}

	/**
	 * @Route("/topic/post/{id}", requirements={"id" = "\d+"})
	 * @Method("GET")
	 * @Template
	 * @param $id
	 * @return array
	 */
	public function topicPostAction($id) {
		$topic = $this->topic()->fetchOne($id);

		return $this->response('次元: ' . $topic->title, [
			'topicId' => $id,
		]);
	}

	/**
	 * @Route("/topic/load_posts/{topicId}/{cursor}", requirements={"topicId" = "\d+"})
	 * @Template
	 * @param $topicId
	 * @param $cursor
	 * @return JsonResponse
	 */
	public function topicLoadPostsAction($topicId, $cursor = 0) {
		$postIds = $this->post()->fetchIdsByTopicId($topicId, (int)$cursor, 10, $nextCursor);
		rsort($postIds);
		if (0 == $cursor) {
			$stickyPostIds = $this->postSticky()->getStickyPostIds($topicId, 0, 100, $stickyNextCursor);
			$postIds = array_merge($stickyPostIds, $postIds);
		}
		$posts = $this->post()->fetch($postIds);
		if (0 != $cursor) {
			krsort($posts);
		}
		$result = $this->getCardData($posts);

		return new JsonResponse(array('total' => count($result), 'result' => $result));
	}

	/**
	 * @Route("/black_list")
	 * @Template
	 * @param Request $request
	 * @return array
	 */
	public function blacklistAction(Request $request)
	{
		$paginator = new Paginator($this->account()->frozenUserIterator('DESC'));
		$cursor = $request->query->get('cursor', 0);
		if (0 >= (int)$cursor) {
			$cursor = PHP_INT_MAX;
		}
		$paginator->setCursor($cursor)
		          ->setPage($request->query->get('page', 1))
		          ->setStep(20)
		          ->setStartPageNum($request->query->get('start_page', 1));

		$frozenUsers = $paginator->getResult();
		$userIds = array_keys(ArrayUtility::mapByColumn($frozenUsers, 'userId'));
		$users = $this->account()->fetch($userIds);

		return $this->response('黑名单', array(
			'paginator' => $paginator,
			'users' => $users,
			'frozenUsers' => $frozenUsers,
		));
	}

	/**
	 * @Route("/graylist")
	 * @Template
	 * @param Request $request
	 * @return array
	 */
	public function graylistAction(Request $request) {
		$type = $request->query->get('type', GrayListService::TYPE_USER);
		$sort = $request->query->get('sort', GrayListService::SORT_BY_DELETE_TIME);
		$page = $request->query->get('page', 1);
		$count = $request->query->get('count', 20);
		/** @var GrayListService $grayListService */
		$grayListService = $this->get('lychee_admin.service.graylist');
		$list = $grayListService->fetch($type, $sort, $page, $count);

		$selector = array_map(function($item) { return $item[0]; }, $list);
		$users = $topics = $topicManagers = $managerTopics = [];
		if ($type == GrayListService::TYPE_USER) {
			$users = ArrayUtility::mapByColumn($this->account()->fetch($selector), 'id');
			$topicService = $this->topic();
			$managerTopics = array_reduce($users, function($result, $user) use ($topicService) {
				$managerId = $user->id;
				$topicIds = $topicService->fetchIdsByManager($managerId, 0, 2, $nextCursor);
				$topics = $topicService->fetch($topicIds);
				if ($nextCursor) {
					array_push($topics, []);
				}
				$result[$managerId] = $topics;

				return $result;
			});
		} else {
			$topics = ArrayUtility::mapByColumn($this->topic()->fetch($selector), 'id');
			$topicManagerIds = array_map(function($t) { return $t->managerId; }, $topics);
			$topicManagers = ArrayUtility::mapByColumn($this->account()->fetch($topicManagerIds), 'id');
		}
		$sum = $grayListService->fetchListCount();
		$pageCount = min(ceil($sum / $count), 10);
		$ciyoUserId = 31721;
		$ciyoUser = $this->account()->fetchOne($ciyoUserId);
		$deviceBlock = array_reduce($users, function($result, $user) {
			!$result && $result = [];
			/**
			 * @var \Lychee\Module\Account\DeviceBlocker $deviceBlocker
			 */
			$deviceBlocker = $this->get('lychee.module.account.device_blocker');
			$platformAndDevice = $deviceBlocker->getUserDeviceId($user->id);
			if (is_array($platformAndDevice)) {
				if ($deviceBlocker->isDeviceBlocked($platformAndDevice[0], $platformAndDevice[1])) {
					array_push($result, $user->id);
				}
			}

			return $result;
		});
		return $this->response('灰名单', [
			'type' => $type,
			'sort' => $sort,
			'page' => $page,
			'list' => $list,
			'users' => $users,
			'managerTopics' => $managerTopics,
			'topics' => $topics,
			'topicManagers' => $topicManagers,
			'pageCount' => $pageCount,
			'ciyoUser' => $ciyoUser,
			'deviceBlock' => $deviceBlock,
		]);
	}

	/**
	 * @Route("/user")
	 * @Template()
	 * @return array
	 */
	public function userAction(Request $request) {
		$query = $request->query->get('query');
		$page = $request->query->get('page', 1);
		$count = $request->query->get('count', 20);
		if ($query) {
			$offset = ($page - 1) * $count;
			/** @var AbstractSearcher $accountSearcher */
			$accountSearcher = $this->get('lychee.module.search.accountSearcher');
			$userIds = $accountSearcher->search($query, $offset, $count, $total);
			null === $total && $total = 0;
			if (filter_var($query, FILTER_VALIDATE_INT)) {
				$userQueryById = $this->account()->fetchOne($query);
				if (null !== $userQueryById && !in_array($userQueryById->id, $userIds)) {
					array_unshift($userIds, $userQueryById->id);
				}
			}
		} else {
			$count = 20;
			$userIds = $this->account()->fetchIdsByPage($page, $count);
			$total = $this->account()->getUserCount();
		}
		$users = $this->account()->fetch($userIds);
		$topicService = $this->topic();
		$managerTopics = array_reduce($users, function($result, $user) use ($topicService) {
			$managerId = $user->id;
			$topicIds = $topicService->fetchIdsByManager($managerId, 0, 2, $nextCursor);
			$topics = $topicService->fetch($topicIds);
			if ($nextCursor) {
				array_push($topics, []);
			}
			$result[$managerId] = $topics;

			return $result;
		});

		$ciyoUser = $this->account()->fetchOne($this->account()->getCiyuanjiangID());

		$deviceBlock = array_reduce($users, function($result, $user) {
			!$result && $result = [];
			/**
			 * @var \Lychee\Module\Account\DeviceBlocker $deviceBlocker
			 */
			$deviceBlocker = $this->get('lychee.module.account.device_blocker');
			$platformAndDevice = $deviceBlocker->getUserDeviceId($user->id);

			if (is_array($platformAndDevice)) {
				if ($deviceBlocker->isDeviceBlocked($platformAndDevice[0], $platformAndDevice[1])) {
					array_push($result, $user->id);
				}

			}
			return $result;
		});

		return $this->response($this->getTitle(), array(
			'userIds' => $userIds,
			'users' => $users,
			'genderMale' => User::GENDER_MALE,
			'genderFemale' => User::GENDER_FEMALE,
			'ciyoUser' => $ciyoUser,
			'managerTopics' => $managerTopics,
			'deviceBlock' => $deviceBlock,
			'page' => $page,
			'total' => $total,
			'pageCount' => ceil($total / $count),
			'query'=>$query
		));
	}

	/**
	 * @Route("/create_account")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 * @throws \Lychee\Module\Account\Exception\EmailDuplicateException
	 * @throws \Lychee\Module\Account\Exception\PhoneDuplicateException
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 * @throws \Exception
	 */
	public function createAccountAction(Request $request)
	{
		$account = $request->request->get('account');
		$nickname = $request->request->get('nickname');
		if (!$nickname) {
			throw $this->createNotFoundException('Nickname is Empty');
		}
		if (null !== $this->account()->fetchOneByNickname($nickname)) {
			throw new \Exception('Nickname already exists.');
		}
		if (!$account) {
			throw $this->createNotFoundException('Account is Empty.');
		}
		if (false !== filter_var($account, FILTER_VALIDATE_EMAIL)) {
			if (null !== $this->account()->fetchOneByEmail($account)) {
				throw new EmailDuplicateException();
			}
			$user = $this->account()->createWithEmail($account, $nickname);
		} elseif (false !== filter_var($account, FILTER_VALIDATE_INT)) {
			if (null !== $this->account()->fetchOneByPhone('86', $account)) {
				throw new PhoneDuplicateException();
			}
			$user = $this->account()->createWithPhone($account, '86', $nickname);
		} else {
			throw new \Exception('Invalid Account');
		}
		$gender = (int)$request->request->get('gender', User::GENDER_MALE);
		if ($gender !== User::GENDER_MALE) {
			$gender = User::GENDER_FEMALE;
		}
		$user->gender = $gender;
		if ($request->files->has('avatar')) {
			$imageFile = $request->files->get('avatar');
			if (file_exists($imageFile)) {
				$user->avatarUrl = $this->storage()->put($imageFile);
			}
		}
		$this->account()->updateInfo($user->id, $gender, $user->avatarUrl, $user->signature);
		$password = $request->request->get('password');
		if ($password) {
			$this->authentication()->createPasswordForUser($user->id, $password);
		}

		return $this->redirect($this->generateUrl('lychee_admin_inquiry_user'));
	}

	/**
	 * @Route("/vips")
	 * @Template
	 * @param Request $request
	 *
	 * @return array
	 */
	public function vipAction(Request $request) {
		$query = $request->query->get('query');
		$page = $request->query->get('page', 1);
		$count = $request->query->get('count', 20);
		if ($query) {
			$offset = ($page - 1) * $count;
			$userIds = $this->account()->queryVipToGetUserIds($query, $offset, $count, $total);
			$total === null && $total = 0;
			if (filter_var($query, FILTER_VALIDATE_INT)) {
				if ($this->account()->isUserInVip($query)) {
					array_unshift($userIds, $query);
				}
				$total += 1;
			}
			$vips = $this->account()->fetchVipInfosByUserIds($userIds);
		} else {
			$vips = $this->account()->fetchVipUsers($page, $count);
			$vips = ArrayUtility::mapByColumn($vips, 'userId');
			$userIds = array_keys($vips);
			$total = $this->account()->getVipCount();
		}
		$userCounts = $this->account()->fetchCountings($userIds);
		$relationService = $this->relation();
		$postService = $this->post();
		$followerCountAndLatestPostTime = array_reduce($userIds, function($result, $userId) use ($relationService, $postService) {
			$result[0][$userId] = $relationService->countUserFollowers($userId);
			$postId = $postService->getUserLatestPostId($userId);
			$post = $postService->fetchOne($postId);
			if ($post) {
				$result[1][$userId] = $post->createTime;
			} else {
				$result[1][$userId] = new \DateTime();
			}
			return $result;
		});
		list($followersCount, $latestPostTime) = $followerCountAndLatestPostTime;
		$users = $this->account()->fetch($userIds);
		return $this->response('VIP用户', [
			'users' => $users,
			'vips' => $vips,
			'page' => $page,
			'count' => $count,
			'userCounts' => $userCounts,
			'followersCount' => $followersCount,
			'latestPostTime' => $latestPostTime,
			'total' => $total,
			'pageCount' => ceil($total / $count),
			'query'=>$query
		]);
	}

	/**
	 * @Route("/vip/add")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function addVip(Request $request) {
		$userId = $request->request->get('user_id');
		$certificationText = $request->request->get('certification_text');
		$this->account()->addVip($userId, $certificationText);
		return $this->redirect($this->generateUrl('lychee_admin_inquiry_vip'));
	}

	/**
	 * @Route("/vip/remove")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function removeVip(Request $request) {
		$userId = $request->request->get('user_id');
		$this->account()->deleteVipByUserId($userId);
		return $this->redirect($this->generateUrl('lychee_admin_inquiry_vip'));
	}

	/**
	 * @Route("/vip/edit")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function editcertificationAction(Request $request) {

		$userId = $request->request->get('vip_user_id');
		$certificationText = $request->request->get('new_certification_text');

		$state = $this->account()->editCertification($userId, $certificationText);
		if ($state) {
			return new JsonResponse('success');
		}
		else {
			return new JsonResponse("error");
		}

	}

	/**
	 * @Route("/search")
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function searchAction(Request $request)
	{
		$keyword = $request->query->get('keyword');
		$topics = array();
		if ($keyword) {
			$topics = $this->topic()->fetchByKeyword($keyword, 0, 20);
		}

		return new JsonResponse($topics);
	}

	/**
	 * @Route("/post/{id}", requirements={"id" = "\d+"})
	 * @Method("GET")
	 * @Template
	 * @param $id
	 * @return array
	 */
	public function postAction($id) {
		$topic = $this->topic()->fetchOne($id);

		return $this->response('次元: ' . $topic->title, [
			'topicId' => $id,
			'tags' => $this->get('lychee_admin.service.tag')->fetchAllTags(),
		]);
	}

	/**
	 * @Route("/load_posts/{topicId}/{cursor}", requirements={"topicId" = "\d+"})
	 * @Template
	 * @param $topicId
	 * @param $cursor
	 * @return JsonResponse
	 */
	public function loadPostsAction($topicId, $cursor = 0) {
		$postIds = $this->post()->fetchIdsByTopicId($topicId, (int)$cursor, 10, $nextCursor);
		rsort($postIds);
		if (0 == $cursor) {
			$stickyPostIds = $this->postSticky()->getStickyPostIds($topicId, 0, 100, $stickyNextCursor);
			$postIds = array_merge($stickyPostIds, $postIds);
		}
		$posts = $this->post()->fetch($postIds);
		if (0 != $cursor) {
			krsort($posts);
		}
		$result = $this->getCardData($posts);

		return new JsonResponse(array('total' => count($result), 'result' => $result));
	}

	/**
	 * @Route("/create")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function createAction(Request $request) {
		$topicId = $request->request->get('topic_id');
		$topicName = $request->request->get('topic_name');
		$creatorId = $request->request->get('creator');
//        $opMark = $request->request->get('op_mark');
		$topicDesc = str_replace("\r", '', $request->request->get('description'));
		$property = $request->request->get('property');
		$categories = $request->request->get('categories');
		$managerId = $request->request->get('manager_id');
		$creator = $this->account()->fetchOne($creatorId);
		if (null === $creator) {
			return $this->redirect($this->generateUrl('lychee_admin_topic_error', [
				'errorMsg' => '账号不存在',
				'callbackUrl' => $this->generateUrl('lychee_admin_topic_index',[], UrlGeneratorInterface::ABSOLUTE_URL),
			]));
		}
		$indexImageUrl = null;
		if ($request->files->has('index_image')) {
			$imageFile = $request->files->get('index_image');
			if (file_exists($imageFile)) {
				$indexImageUrl = $this->storage()->put($imageFile);
			}
		}
		if (!$topicId) {
			$this->topic()->increaseUserCreatingQuota($creatorId, 1);
			try {
				$p = new TopicParameter();
				$p->creatorId = $creatorId;
				$p->title = $topicName;
				$p->description = $topicDesc;
				$p->indexImageUrl = $indexImageUrl;
				$topic = $this->topic()->create($p);
			} catch (TopicAlreadyExistException $e) {
				return $this->redirect($this->generateUrl('lychee_admin_topic_error', [
					'errorMsg' => $this->getParameter('topic_name') . '已存在',
					'callbackUrl' => $request->headers->get('referer'),
				]));
			} catch (RunOutOfCreatingQuotaException $e) {
				return $this->redirect($this->generateUrl('lychee_admin_topic_error', [
					'errorMsg' => sprintf("创建%s配额不足", $this->getParameter('topic_name')),
					'callbackUrl' => $request->headers->get('referer'),
				]));
			} catch (\Exception $e) {
				return $this->redirect($this->generateUrl('lychee_admin_topic_error', [
					'errorMsg' => $e->getMessage(),
					'callbackUrl' => $request->headers->get('referer'),
				]));
			}
			$topicId = $topic->id;
		} else {
			$topic = $this->topic()->fetchOne($topicId);
			if (null !== $topic) {
				$topic->title = $topicName;
				$topic->creatorId = $creatorId;
				$topic->description = $topicDesc;
				if (null !== $indexImageUrl) {
					$topic->indexImageUrl = $indexImageUrl;
				}
				$this->topic()->update($topic);
			}
		}
		if ($topic->managerId != $managerId) {
			$this->topic()->updateManager($topicId, $managerId);
		}
		$topicCategories = [];
		if ($property) {
			$topicCategories[] = $property;
		}
		if (!empty($categories)) {
			$topicCategories = array_merge($topicCategories, $categories);
		}
		/**
		 * @var \Lychee\Module\Topic\TopicCategoryService $topicCategoryService
		 */
		$topicCategoryService = $this->container->get('lychee.module.topic.category');
		$topicCategoryService->topicAddCategories($topicId, $topicCategories);

		return $this->redirect($request->headers->get('referer'));
	}

	/**
	 * @Route("/fetchone")
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function fetchOneAction(Request $request) {
		$id = $request->query->get('id');
		$topic = $this->topic()->fetchOne($id);
		if (null === $topic) {
			throw $this->createNotFoundException('Topic Not Found.');
		}
		$topic->indexImageUrl = ImageUtility::resize($topic->indexImageUrl, 320, 9999);
		/**
		 * @var \Lychee\Module\Topic\TopicCategoryService $topicCategoryService
		 */
		$topicCategoryService = $this->container->get('lychee.module.topic.category');
		$property = $topicCategoryService->getPropertyByTopicId($id);
		$categories = $topicCategoryService->getCategoriesByTopicId($id);
		$categories = ArrayUtility::columns($categories, 'id');

		return new JsonResponse([
			'topic' => $topic,
			'property' => $property,
			'categories' => $categories,
		]);
	}

	/**
	 * @Route("/detail/{topicId}", requirements={"topicId" = "\d+"})
	 * @Template
	 * @param $topicId
	 * @return array
	 */
	public function detailAction($topicId) {
		$topic = $this->topic()->fetchOne($topicId);
		if (!$topic) {
			throw $this->createNotFoundException('Topic Not Found');
		}
		/**
		 * @var \Lychee\Module\Topic\TopicCategoryService $topicCategoryService
		 */
		$topicCategoryService = $this->get('lychee.module.topic.category');
		$topicProperty = $topicCategoryService->getPropertyByTopicId($topicId);
		$topicCategories = array_map(function($category) {
			return $category['name'];
		}, $topicCategoryService->getCategoriesByTopicId($topicId));
		$creator = null;
		if ($topic->creatorId) {
			$creator = $this->account()->fetchOne($topic->creatorId);
		}
		$manager = null;
		if ($topic->creatorId === $topic->managerId) {
			$manager = $creator;
		} elseif ($topic->managerId) {
			$manager = $this->account()->fetchOne($topic->managerId);
		}
		$topicFollowerCount = $this->topicFollowing()->getTopicsFollowerCounter(array($topicId))
		                           ->getCount($topicId);
		$topic->indexImageUrl = ImageUtility::resize($topic->indexImageUrl, 240, 9999);
		$topic->coverImageUrl = ImageUtility::resize($topic->coverImageUrl, 240, 9999);
		/**
		 * @var Request $request
		 */
		$request = $this->container->get('request_stack')->getCurrentRequest();
		$startDate = $request->query->get('start_date');
		$endDate = $request->query->get('end_date');
		if ($startDate && $endDate) {
			$startDate = new \DateTime($startDate);
			$endDate = new \DateTime($endDate);
			$postCount = $this->topic()->countPost($topicId, $startDate, $endDate);
			$commentCount = $this->topic()->countComment($topicId, $startDate, $endDate);
			$followerCount = $this->topic()->countFollower($topicId, $startDate, $endDate);
		} else {
			$postCount = $commentCount = $followerCount = null;
		}
		/**
		 * @var \Lychee\Module\Topic\TopicCategoryService $topicCategoryService
		 */
		$topicCategoryService = $this->container->get('lychee.module.topic.category');
		$properties = $topicCategoryService->getProperties();
		$categories = $topicCategoryService->getCategories();

		return $this->response('次元详情', [
			'topic' => $topic,
			'creator' => $creator,
			'manager' => $manager,
			'topicFollowerCount' => $topicFollowerCount,
			'postCount' => $postCount,
			'commentCount' => $commentCount,
			'followerCount' => $followerCount,
			'topicProperty' => $topicProperty,
			'topicCategories' => $topicCategories,
			'properties' => $properties,
			'categories' => $categories,
		]);
	}

	/**
	 * @Route("/remove")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function removeAction(Request $request) {
		$id = $request->request->get('topic_id');
		$this->get('lychee.module.topic.deletor')->delete($id);
		$referer = $request->headers->get('referer');

		return $this->redirect($referer);
	}

	/**
	 * @Route("/hide_topics/{page}")
	 * @Template
	 * @param int $page
	 * @return array
	 */
	public function hideTopicListAction($page = 1) {
		$limit = 30;
		$topics = $this->topic()->listHideTopic($page, $limit);
		$count = $this->topic()->hideTopicsCount();

		return $this->response('隐蔽次元', [
			'topics' => $topics,
			'page' => $page,
			'count' => $count,
			'pages' => ceil($count / $limit)
		]);
	}

	/**
	 * @Route("/hide_or_unhide")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function hideOrUnhideTopicAction(Request $request) {
		$topicId = $request->request->get('topic_id');
		$topic = $this->topic()->fetchOne($topicId);
		if ($topic) {
			if ($topic->hidden == 0) {
				$this->topic()->hide($topicId);
			} else {
				$this->topic()->unhide($topicId);
			}
		}

		return $this->redirect($request->headers->get('referer'));
	}

	/**
	 * @Route("/add_hide_topics")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function addHideTopicsAction(Request $request) {
		$topicIds = $request->request->get('topic_ids');
		$topicIds = explode(',', $topicIds);
		if (is_array($topicIds)) {
			foreach ($topicIds as $tid) {
				if (is_numeric($tid)) {
					$this->topic()->hide($tid);
				}
			}
		}
		return $this->redirect($request->headers->get('referer'));
	}

	/**
	 * @Route("/load_more")
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function loadMoreTopicsAction(Request $request) {
		$keyword = $request->query->get('keyword');
		$cursor = $request->query->get('cursor');
		$topics = $this->topic()->fetchByKeyword($keyword, $cursor, self::TOPIC_COUNT_PER_PAGE, $nextCursor);

		return new JsonResponse([
			'topics' => array_reduce($topics, function($result, $t) {
				$result[] = [
					'id' => $t->id,
					'title' => $t->title,
					'createTime' => $t->createTime->format('Y-m-d H:i:s'),
					'postCount' => $t->postCount,
					'opMark' => $t->opMark? $t->opMark:'',
					'deleted' => $t->deleted,
					'hidden' => $t->hidden,
				];
				return $result;
			}),
			'cursor' => $nextCursor,
		]);
	}

	/**
	 * @Route("/category/{catId}", requirements={"catId" = "\d+"})
	 * @Template
	 *
	 * @param $catId
	 * @param Request $request
	 * @return array
	 */
	public function categoryAction($catId, Request $request) {
		$search = $request->query->get('search');
		/** @var TopicCategoryService $catService */
		$catService = $this->get('lychee.module.topic.category');
		$cats = $catService->getCurrentCategories();
		if (!$catId && $cats) {
			$catId = $cats[0]->id;
		}
		if ($search) {
			/** @var TopicSearcher $topicSearcher */
			$topicSearcher = $this->get('lychee.module.search.topicSearcher');
			$allTopicIds = $topicSearcher->search($search, 0, 100);
		} else {
			$allTopicIds = $catService->fetchTopicIdsInCategory($catId);
		}
		$recommendableTopicIds = $this->recommendation()->filterRecommendableTopicIds($allTopicIds);
		rsort($recommendableTopicIds);
		$page = $request->query->get('page', 1);
		$count = $request->query->get('count', 20);
		$offset = ($page - 1) * $count;
		$pageCount = ceil(count($recommendableTopicIds) / $count);
		$topicIds = array_slice($recommendableTopicIds, $offset, $count);
		$topics = ArrayUtility::mapByColumn($this->topic()->fetch($topicIds), 'id');
		krsort($topics);

		return $this->response('推荐次元分类', [
			'cats' => $cats,
			'curCatId' => $catId,
			'topics' => $topics,
			'pageCount' => $pageCount,
			'page' => $page,
			'count' => $count,
		]);
	}

	/**
	 * @Route("/topic_keyword")
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function topickeywordAction(Request $request) {
		$keyword = $request->query->get('keyword');
		$matchTopics = $this->topic()->fetchByKeywordOrderByPostCount($keyword, 0, 20);
		$topicName = array();
		foreach ($matchTopics as $topic) {
			$topicName[$topic->id] = $topic->title;
		}

		return new JsonResponse($topicName);
	}

	/**
	 * @Route("/add_recommendable_topic")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function addRecommendableTopic(Request $request) {
		$topicId = $request->request->get('topic_id');
		$this->recommendation()->addRecommendableTopics($topicId);

		return $this->redirect($request->headers->get('referer'));
	}

	/**
	 * @Route("/remove_recommendable_topic")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function removeRecommendableTopic(Request $request) {
		$topicId = $request->request->get('topic_id');
		$this->recommendation()->removeRecommendableTopics($topicId);

		return $this->redirect($request->headers->get('referer'));
	}

	/**
	 * @Route("/certified_topic/{page}", requirements={"page" = "\d+"})
	 * @Template
	 * @param int $page
	 * @return array
	 */
	public function certifiedTopicAction($page = 1) {
		$countPerPage = 20;
		list($topicIds, $topicsCount) = $this->topic()->fetchCertifiedTopics($page, $countPerPage);
		$topics = $this->topic()->fetch($topicIds);

		return $this->response('认证次元', [
			'topicIds' => $topicIds,
			'topics' => ArrayUtility::mapByColumn($topics, 'id'),
			'pageCount' => ceil($topicsCount / $countPerPage),
		]);
	}

	/**
	 * @Route("/certified_topic/remove")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function removeCertifiedTopic(Request $request) {
		$topicId = $request->request->get('topic_id');
		$referUrl = $request->headers->get('referer');
		try {
			$this->topic()->removeCertified($topicId);
		} catch (\Exception $e) {
			return $this->redirect($this->generateUrl('lychee_admin_topic_error', [
				'errorMsg' => '次元不存在',
				'callbackUrl' => $referUrl,
			]));
		}

		return $this->redirect($referUrl);
	}

	/**
	 * @Route("/certified_topic/add")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function addCertifiedTopic(Request $request) {
		$topicId = $request->request->get('topic_id');
		$referUrl = $request->headers->get('referer');
		try {
			$this->topic()->maskAsCertified($topicId);
		} catch (\Exception $e) {
			return $this->redirect($this->generateUrl('lychee_admin_topic_error', [
				'errorMsg' => '次元不存在',
				'callbackUrl' => $referUrl,
			]));
		}

		return $this->redirect($referUrl);
	}

	/**
	 * 次元推荐改为精选次元
	 * @Route("/featured_topic")
	 * @Template
	 * @return array
	 */
	public function featuredTopic() {
		$items = $this->recommendation()->listAllRecommendationTopics();
		$topicIds = array_map(function ($item) {
			/** @var RecommendationItem $item */
			return $item->getTargetId();
		}, $items);
		$topics = ArrayUtility::mapByColumn($this->topic()->fetch($topicIds), 'id');

		return $this->response('精选次元', [
			'items' => $items,
			'topics' => $topics,
		]);
	}

	/**
	 * @Route("/featured_topic/remove")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function removeFeaturedTopic(Request $request) {
		$id = $request->request->get('id');
		/**
		 * @var \Lychee\Module\Recommendation\Entity\RecommendationItem|null $item
		 */
		$item = $this->recommendation()->fetchItemById($id);

		if (null === $item) {
			throw $this->createNotFoundException();
		}
		$this->recommendation()->removeRecommendedItem($item);
		if ($item->getType() === RecommendationType::TOPIC) {
			$this->recommendation()->removeRecommendableTopics($item->getTargetId());
		}
		$this->clearCache();

		return new JsonResponse();
	}

	private function clearCache() {
		$this->container->get('memcache.default')->delete('rec_web');
		$this->container->get('memcache.default')->delete('rec_web2');
	}

	/**
	 * @Route("/featured_topic/create")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function createFeaturedTopic(Request $request) {
		$callbackUrl = $request->request->get('callback_url');
		$type = $request->request->get('type');
		$targetId = $request->request->get('target_id');
		$reason = $request->request->get('reason');
		$imageUrl = null;
		if ($request->files->has('image')) {
			$imageFile = $request->files->get('image');
			if ($imageFile) {
				$imageUrl = $this->storage()->setPrefix('recommendation/')->put($imageFile);
			}
		}
		if (RecommendationType::TOPIC === $type) {
			$topic = $this->topic()->fetchOne($targetId);
			if (null !== $topic) {
				if (!$imageUrl) {
					$imageUrl = $topic->indexImageUrl;
				}
				$target = $this->recommendation()->fetchItemByTarget($targetId, RecommendationType::TOPIC);
				if ($target) {
					return $this->redirectErrorPage('次元已存在于精选次元中, 无法重复添加', $request);
				}
				$this->recommendation()->addRecommendableTopics($topic->id);
			} else {
				return $this->redirect($this->generateUrl('lychee_admin_error', [
					'errorMsg' => '次元不存在, 请检查推荐ID',
					'callbackUrl' => $request->headers->get('referer'),
				]));
			}
		}
		$em = $this->getDoctrine()->getManager();
		$recommendation = new RecommendationItem();
		$recommendation->setTargetId($targetId)->setType($type)->setReason($reason)->setImage($imageUrl);
		$em->persist($recommendation);
		$em->flush();
		$this->clearCache();

		return $this->redirect($callbackUrl);
	}

	/**
	 * @Route("/featured_topic/switch")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function switchFeaturedTopicPosition(Request $request) {
		$ids = $request->request->get('ids');
		if (is_array($ids) && count($ids) == 2) {
			/** @var RecommendationItem $first */
			$first = $this->recommendation()->fetchItemById($ids[0]);
			/** @var RecommendationItem $second */
			$second = $this->recommendation()->fetchItemById($ids[1]);
			$tmpPosition = $second->getPosition();
			$second->setPosition($first->getPosition());
			$first->setPosition($tmpPosition);
			$em = $this->getDoctrine()->getManager();
			$em->flush();
		}

		return new JsonResponse();
	}

	/**
	 * @Route("/featured_topic/top")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function makeFeaturedTopicTop(Request $request) {
		$id = $request->request->get('id');
		$items = $this->recommendation()->listAllRecommendationTopics();
		$position = 1;
		foreach ($items as $item) {
			/** @var RecommendationItem $item */
			if ($item->getId() != $id) {
				$item->setPosition($position);
				$position += 1;
			} else {
				$item->setPosition(0);
			}
		}
		$em = $this->getDoctrine()->getManager();
		$em->flush();

		return $this->redirect($request->headers->get('referer'));
	}

	/**
	 * @Route("/featured_topic/bottom")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function makeFeaturedTopicBottom(Request $request) {
		$id = $request->request->get('id');
		$items = $this->recommendation()->listAllRecommendationTopics();
		$position = 0;
		$bottomItem = null;
		foreach ($items as $item) {
			/** @var RecommendationItem $item */
			if ($item->getId() != $id) {
				$item->setPosition($position);
				$position += 1;
			} else {
				$bottomItem = $item;
			}
		}
		$bottomItem->setPosition($position);
		$em = $this->getDoctrine()->getManager();
		$em->flush();

		return $this->redirect($request->headers->get('referer'));
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

}