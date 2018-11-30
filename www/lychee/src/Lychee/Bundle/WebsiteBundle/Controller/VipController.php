<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 13/10/2016
 * Time: 3:47 PM
 */

namespace Lychee\Bundle\WebsiteBundle\Controller;


use Lsw\MemcacheBundle\Cache\AntiDogPileMemcache;
use Lychee\Bundle\ApiBundle\DataSynthesizer\SynthesizerBuilder;
use Lychee\Bundle\CoreBundle\Controller\Controller;
use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Foundation\TopicUtility;
use Lychee\Component\IdGenerator\IdGenerator;
use Lychee\Module\Account\DeviceBlocker;
use Lychee\Module\Authentication\AuthenticationService;
use Lychee\Module\Authentication\Entity\AuthToken;
use Lychee\Module\Authentication\TokenIssuer;
use Lychee\Module\Post\PostParameter;
use Lychee\Module\Post\PostParameterGenerator;
use Lychee\Module\Voting\VotingService;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcacheSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;


/**
 * @Route("/", host="vip.ciyo.cn")
 * Class VipController
 * @package Lychee\Bundle\WebsiteBundle\Controller
 */
class VipController extends Controller {
	use ModuleAwareTrait;

	const SESSION_KEY_TOKEN = 'token';

	const SESSION_KEY_TOKEN_EXPIRED = 'token_expired';

	/**
	 * @Route("/")
	 * @return RedirectResponse
	 */
	public function index() {
		return $this->redirectLogin();
	}

	/**
	 * @Route("/login")
	 * @Template
	 * @return array|RedirectResponse
	 */
	public function loginAction() {
		if ($this->isAuth()) {
			return $this->redirect($this->generateUrl('lychee_website_vip_reports'));
		}
		return [];
	}

	/**
	 * @Route("/logout")
	 * @Method("POST")
	 * @return RedirectResponse
	 */
	public function logoutAction() {
		$session = $this->getSession();
		$session->remove(self::SESSION_KEY_TOKEN);
		$session->invalidate();
		$session->clear();

		return $this->redirect($this->generateUrl('lychee_website_home_index'));
	}

	/**
	 * @Route("/login/do")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return RedirectResponse
	 */
	public function doLogin(Request $request) {
		$areaCode = $request->request->get('area_code');
		$phone = $request->request->get('phone');
		$password = $request->request->get('password');

		$user = $this->account()->fetchOneByPhone($areaCode, $phone);
		if ($user == null) {
			throw $this->createNotFoundException('User Not Found.');
		}
		if ($user->frozen) {
			throw $this->createAccessDeniedException('User is frozen.');
		}
		if (false === $this->account()->isUserInVip($user->id)) {
			throw $this->createAccessDeniedException('User is not VIP');
		}
		$passwordValid = $this->authentication()->isUserPasswordValid($user->id, $password);
		if ($passwordValid == false) {
			throw $this->createAccessDeniedException('Account or password invalid.');
		} else {
			$this->updateUserDevice($request, $user->id);
			/** @var TokenIssuer $tokenIssuer */
			$tokenIssuer = $this->get('lychee.module.authentication.token_issuer');
			$token = $tokenIssuer->issueToken(
				$user->id,
				AuthToken::CLIENT_CIYO_WEB,
				null,
				AuthenticationService::GRANT_TYPE_PHONE,
				AuthenticationService::TOKEN_TTL
			);

			$session = $this->getSession();
			$session->set(self::SESSION_KEY_TOKEN, $token->accessToken);
			$session->set(self::SESSION_KEY_TOKEN_EXPIRED, time() + $token->ttl);

			$response = new RedirectResponse($this->generateUrl('lychee_website_vip_reports'));
//			$response->headers->setCookie(new Cookie('authentication', $token->accessToken, time() + $token->ttl - 1));

			return $response;
		}
	}

	/**
	 * @param Request $request
	 * @param int $userId
	 */
	protected function updateUserDevice($request, $userId) {
		$platform = $request->get('client');
		$deviceId = $request->get('uuid');

		if ($platform && $deviceId) {
			/** @var DeviceBlocker $deviceBlocker */
			$deviceBlocker = $this->get('lychee.module.account.device_blocker');
			$deviceBlocker->updateUserDeviceId($userId, $platform, $deviceId);
		}
	}

	/**
	 * @return Session
	 */
	private function getSession() {
		$memcache = new \Memcache();
		$memcache->connect($this->getParameter('core_memcache_host'), $this->getParameter('core_memcache_port'));

		$storage = new NativeSessionStorage([
			'cookie_lifetime' => 7 * 24 * 60 * 60, // 7 day
			'gc_maxlifetime' => 7 * 24 * 60 * 60,
		], new MemcacheSessionHandler($memcache));
		$session = new Session($storage);

		return $session;
	}

	/**
	 * @return int|null
	 */
	private function isAuth() {
		$session = $this->getSession();
		$token = $session->get(self::SESSION_KEY_TOKEN);
		if ($token) {
			return $this->authentication()->getUserIdByToken($token);
		}
		return null;
	}

	/**
	 * @Route("/user_info")
	 * @Template
	 * @return array|RedirectResponse
	 */
	public function userInfo() {
		if (!($userId = $this->isAuth())) {
			return $this->redirectLogin();
		}
		$user = $this->account()->fetchOne($userId);
		$profile = $this->account()->fetchOneUserProfile($userId);
		$followerCount = $this->relation()->countUserFollowers($userId);
		$followeeCount = $this->relation()->countUserFollowees($userId);

		return compact('user', 'profile', 'followerCount', 'followeeCount');
	}

	/**
	 * @Route("/fetch_posts/{cursor}", requirements={"cursor" = "\d+"})
	 * @param int $cursor
	 *
	 * @return JsonResponse
	 */
	public function fetchPosts($cursor = 0) {
		$userId = $this->isAuth();
		$data = [];
		if ($userId) {
			$postIds = $this->post()->fetchIdsByAuthorId($userId, (int)$cursor, 20, $nextCursor);
			/** @var SynthesizerBuilder $synthesizerBuilder */
			$synthesizerBuilder = $this->get('lychee_api.synthesizer_builder');
			$synthesizer = $synthesizerBuilder->buildListPostSynthesizer($postIds, $userId);
			$result = $synthesizer->synthesizeAll();
			$data = array_values(array_filter($result, function($p){
				return !isset($p['deleted']) || $p['deleted'] == false;
			}));
			$data = array_map(function($post) {
				$post['topic']['color'] = TopicUtility::filterColor($post['topic']['color']);
				return $post;
			}, $data);
		}

		return new JsonResponse(compact('data', 'nextCursor'));
	}

	/**
	 * @Route("/reports")
	 * @Template
	 *
	 * @return array|RedirectResponse
	 */
	public function reports() {
		if (!($userId = $this->isAuth())) {
			return $this->redirectLogin();
		}
		$today = (new \DateTime())->modify('midnight');
		$todayFollowerCount = $this->relation()->getFollowerCountByDate($userId, $today);
		$todayUnFollowerCount = $this->relation()->getUnFollowerCountByDate($userId, $today);
		$yesterday = clone $today;
		$yesterday->modify('-1 day');
		$yesterdayFollowerCount = $this->relation()->getFollowerCountByDate($userId, $yesterday);
		$yesterdayUnFollowerCount = $this->relation()->getUnFollowerCountByDate($userId, $yesterday);
		$todayGrowth = $todayFollowerCount - $todayUnFollowerCount;
		$yesterdayGrowth = $yesterdayFollowerCount - $yesterdayUnFollowerCount;

		return compact(
			'todayFollowerCount',
			'todayUnFollowerCount',
			'yesterdayFollowerCount',
			'yesterdayUnFollowerCount',
			'todayGrowth',
			'yesterdayGrowth'
		);
	}

	/**
	 * @Route("/get_trend")
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function getTrend(Request $request) {
		if (!($userId = $this->isAuth())) {
			return new JsonResponse();
		}
		$dates = $request->query->get('dates', 7);
		$today = new \DateTime();
		$data = [];
		$labels = [];
		for ($i = $dates - 1; $i >= 0; $i--) {
			$date = clone $today;
			$date->modify("-$i day midnight");
			$labels[] = $date->format('m/d');
			$data[] = $this->relation()->getFollowerCountByDate($userId, $date);
		}

		return new JsonResponse(compact('labels', 'data'));
	}

	/**
	 * @Route("/sender")
	 * @Template
	 */
	public function sender() {
		if (!$this->isAuth()) {
			return $this->redirectLogin();
		}

		return [];
	}

	/**
	 * @Route("/get_sender_template/{type}")
	 * @param $type
	 *
	 * @return Response
	 */
	public function getSenderTemplate($type) {
		if ($userId = $this->isAuth()) {
			$topicIds = $this->topicFollowing()->fetchTopicIdsByFollower($userId);
			$topics = $this->topic()->fetch($topicIds);
			return $this->render("LycheeWebsiteBundle:Vip:$type.html.twig", [
				'topics' => $topics
			]);
		}
	}

	private function redirectLogin() {
		return $this->redirect($this->generateUrl('lychee_website_vip_login'));
	}

	/**
	 * @Route("/topic/keyword/search")
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function searchTopic(Request $request) {
		$keyword = $request->query->get('keyword');
		$userId = $this->isAuth();
		$topics = [];
		if ($userId) {
			$topics = array_reduce($this->topicFollowing()->searchUserFollowingTopicByKeyword($userId, $keyword), function($result, $item) {
				$result[] = [
					'id' => $item['topic_id'],
					'name' => $item['title'],
				];

				return $result;
			});
		}

		return new JsonResponse($topics);
	}

	/**
	 * @Route("/sendpost")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return RedirectResponse
	 * @throws \Exception
	 */
	public function sendPost(Request $request) {
		if (!($accountId = $this->isAuth())) {
			return $this->redirectLogin();
		}

		/**
		 * @TODO: 图片超过9张的错误提示
		 */
//		if (count($images) > 9) {
//			return $this->createAccessDeniedException('图片不能超过9张');
//		}
		/**
		 * @TODO: 资源链接格式不正确的错误提示
		 */
//		if ($resourceUrl) {
//			if (!preg_match('/^http[s]{0,1}:\/\//', $resourceUrl)) {
//				return $this->redirect($this->generateUrl('lychee_admin_error', [
//					'errorMsg' => '资源链接不正确',
//					'callbackUrl' => $request->headers->get('referer'),
//				]));
//			}
//		}

		$postType = $request->request->get('post_type', Post::TYPE_NORMAL);
		$topicId = $request->request->get('topic_id');
		$content = $request->request->get('content');
		$images = $request->files->get('pictures');
		$postParameterGenerator = new PostParameterGenerator(new PostParameter(), $this->storage(), $postType, $topicId, $accountId);
		$postParameterGenerator->setDefaultPostParameter($images)->setContent($content);
		/** @var IdGenerator $idGenerator */
		$idGenerator = $this->get('lychee.module.post.id_generator');
		switch ($postType) {
			case Post::TYPE_RESOURCE:
				$postParameterGenerator->setResourcePostParameter($request->request->get('resource_url'));
				break;
			case Post::TYPE_SCHEDULE:
				/**
				 * @var \Lychee\Module\Schedule\ScheduleService $scheduleService
				 */
				$scheduleService = $this->get('lychee.module.schedule');
				$postParameterGenerator->setSchedulePostParameter(
					$idGenerator,
					$scheduleService,
					$request->request->get('schedule_title'),
					$request->request->get('content'),
					$request->request->get('schedule_address'),
					$request->request->get('start_time'),
					$request->request->get('end_time')
				);
				break;
			case Post::TYPE_VIDEO:
				$postParameterGenerator->setVideoPostParameter(
					$request->request->get('video_url'),
					$request->request->get('video_cover'),
					$request->files->get('new_video_cover')
				);
				break;
			case Post::TYPE_VOTING:
				/** @var VotingService $votingService */
				$votingService = $this->get('lychee.module.voting');
				$optionTitles = [];
				for ($i = 1; $i < 9; $i++) {
					$optionTitles[] = $request->request->get('op_' . $i);
				}
				if (count($optionTitles) < 2) {
					throw new \Exception('投票选项必须多于2个');
				}
				$postParameterGenerator->setVotingPostParameter(
					$request->request->get('voting_title'),
					$request->request->get('content'),
					$optionTitles,
					$idGenerator,
					$votingService
				);
				break;
			default:
		}
		$this->post()->create($postParameterGenerator->genPostParameter());

		return $this->redirect($this->generateUrl('lychee_website_vip_userinfo'));
	}

	/**
	 * @Route("/fetch_video_cover")
	 * @Method("POST")
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function fetchVideoCover(Request $request) {
		$url = $request->request->get('url');
		$coverUrl = $this->contentManagement()->fetchVideoCover($url);

		return new JsonResponse([
			'img' => $coverUrl
		]);
	}
}