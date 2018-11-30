<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 28/09/2016
 * Time: 8:52 PM
 */

namespace Lychee\Bundle\AdminBundle\Controller;

use Lychee\Bundle\AdminBundle\Components\Foundation\Paginator;
use Lychee\Bundle\AdminBundle\Service\GrayListService;
use Lychee\Bundle\CoreBundle\Entity\User;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Account\Entity\UserVip;
use Lychee\Module\Account\Exception\EmailDuplicateException;
use Lychee\Module\Account\Exception\PhoneDuplicateException;
use Lychee\Module\Search\AbstractSearcher;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/list")
 * Class ListController
 * @package Lychee\Bundle\AdminBundle\Controller
 */
class ListController extends BaseController {

	public function getTitle() {
		return '名单管理';
	}

	/**
	 * @Route("/")
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function indexAction() {
		return $this->redirect($this->generateUrl('lychee_admin_list_blacklist'));
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

		return $this->redirect($this->generateUrl('lychee_admin_list_user'));
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
		return $this->redirect($this->generateUrl('lychee_admin_list_vip'));
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
		return $this->redirect($this->generateUrl('lychee_admin_list_vip'));
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
}