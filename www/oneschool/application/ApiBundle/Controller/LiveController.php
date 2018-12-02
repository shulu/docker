<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 02/11/2016
 * Time: 5:00 PM
 */

namespace Lychee\Bundle\ApiBundle\Controller;


use Lychee\Bundle\ApiBundle\Error\CommonError;
use Lychee\Bundle\ApiBundle\Error\ErrorsException;
use Lychee\Bundle\ApiBundle\Error\LiveError;
use Lychee\Bundle\CoreBundle\Entity\User;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Notification\Entity\PushSetting;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use JPush\Model;

/**
 * Class LiveController
 * @package Lychee\Bundle\ApiBundle\Controller
 */
class LiveController extends Controller {

	/**
	 * @Route("/live/fetch_ciyocoin")
	 * @Method("GET")
	 * @ApiDoc(
	 *     section="live",
	 *   parameters={
	 *     {"name"="pid", "dataType"="integer", "required"=true, "description"="皮哲PID"},
	 *     {"name"="signature", "dataType"="string", "required"=true, "description"="数字签名"}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function fetchCiyoCoin(Request $request) {
		if (false === $this->signatureVerification($request->query->get('signature'), $request->query->all())) {
			return $this->errorsResponse(LiveError::SignatureInvalid());
		}
		$pid = $this->requirePid($request);

		$userId = $this->live()->fetchUserIdByPid($pid);
		$user = $this->account()->fetchOne($userId);

		return new JsonResponse([
			'ciyocoin' => $user->ciyoCoin
		]);
	}

	/**
	 * @Route("/live/deduct_ciyocoin")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="live",
	 *   parameters={
	 *     {"name"="pid", "dataType"="integer", "required"=true, "description"="皮哲PID"},
	 *     {"name"="gifts", "dataType"="string", "required"=true, "description"="礼物信息(JSON字符串)"},
	 *     {"name"="signature", "dataType"="string", "required"=true, "description"="数字签名"}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 * @throws ErrorsException
	 * @throws \Exception
	 */
	public function deductCiyoCoin(Request $request) {
		if (false === $this->signatureVerification($request->request->get('signature'), $request->request->all())) {
			return $this->errorsResponse(LiveError::SignatureInvalid());
		}
		$tid = $this->requireTid($request->request);
		$pid = $this->requirePid($request);
		$userId = $this->live()->fetchUserIdByPid($pid);
		$gifts = $request->request->get('gifts');
		$giftsJson = json_decode($gifts, true);
		if (!$giftsJson || !isset($giftsJson['price']) || !isset($giftsJson['count'])) {
			return $this->errorsResponse(LiveError::GiftsJsonInvalid());
		}
		$unitPrice = $giftsJson['price'];
		$giftCount = $giftsJson['count'];
		$price = $giftCount * $unitPrice;
		if (!$this->live()->isTransactionExist($tid)) {
			/** @var \PDO $conn */
			$conn = $this->getDoctrine()->getConnection();
			$conn->beginTransaction();
			try {
				$this->live()->giftPurchasedRecord($tid, $userId, $pid, $gifts, $unitPrice, $giftCount, $price);
				$this->live()->deductCoin($userId, $price);
				$conn->commit();
			} catch (ErrorsException $e) {
				$conn->rollBack();
				throw $e;
			} catch (\Exception $e) {
				$conn->rollBack();
				throw $e;
			}
		}
		$user = $this->account()->fetchOne($userId);

		return $this->dataResponse([
			'ciyocoin' => $user->ciyoCoin
		]);
	}

	/**
	 * @param Request $request
	 *
	 * @return mixed
	 * @throws ErrorsException
	 */
	private function requirePid(Request $request) {
		if ('POST' === $request->getMethod()) {
			$pid = $request->request->get('pid');
		} elseif ('GET' === $request->getMethod()) {
			$pid = $request->query->get('pid');
		} else {
			$pid = $request->get('pid');
		}
		if (!$pid) {
			throw new ErrorsException(CommonError::ParameterMissing('pid'));
		}

		return $pid;
	}

	private function requireTid(ParameterBag $params) {
		$tid = $params->get('tid');
		if (!$tid) {
			throw new ErrorsException(CommonError::ParameterMissing('tid'));
		}

		return $tid;
	}

	/**
	 * @Route("/live/bind_pid")
	 * @Method("POST")
	 * @ApiDoc(
	 *     section="live",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true},
	 *     {"name"="pid", "dataType"="string", "required"=true, "description"="皮哲PID"}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function bindPizusId(Request $request) {
		$user = $this->requireAuth($request);
		$pid = $this->requirePid($request);
		$this->live()->bindPizusId($user->id, $pid);

		return $this->sucessResponse();
	}

	/**
	 * @Route("/live/fetch_pid")
	 * @Method("GET")
	 * @ApiDoc(
	 *     section="live",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function fetchPizusId(Request $request) {
		$user = $this->requireAuth($request);
		$pid = $this->live()->fetchPizusId($user->id);

		return $this->dataResponse([
			'pid' => $pid
		]);
	}

	private function pizusRequestSignature($params) {
		unset($params['signature']);
		ksort($params);
		$queryStr = http_build_query($params);
		$secret = $this->getParameter('live_api_secret_key');
		$signBefore = $queryStr.$secret;
		$signature = sha1($signBefore);

		return $signature;
	}

	private function signatureVerification($signature, $params) {
		if (0 === strcmp($signature, $this->pizusRequestSignature($params))) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @Route("/user/check_verify")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="live",
	 *   parameters={
	 *     {"name"="uuid", "dataType"="string", "required"=true, "description"="用户ID"},
	 *     {"name"="appid", "dataType"="string", "required"=true, "description"="由inke分配给接入方的appid"},
	 *     {"name"="source", "dataType"="string", "required"=true, "description"="用于标注inke服务来源"},
	 *     {"name"="access_time", "dataType"="string", "required"=true, "description"="用于标注请求时间"},
	 *     {"name"="nonce_str", "dataType"="string", "required"=true, "description"="随机字符串"},
	 *     {"name"="sig", "dataType"="string", "required"=true,
	 *     "description"="请求签名,算法同微信见附https://pay.weixin.qq.com/wiki/doc/api/app/app.php?chapter=4_3"},
	 *   }
	 * )
	 *
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function inkeCheckVerifyAction(Request $request) {

		/** @var Logger $logger */
		$logger = $this->get('monolog.logger.thirdparty_invoke');
		$requestId = strtoupper(substr(sha1(uniqid()), -6));
		$requestQuery = http_build_query($request->query->all());
		$logger->info(sprintf(
			"[%s]%s %s %s?%s",
			$requestId,
			$request->getMethod(),
			$request->headers->get('host'),
			$request->getPathInfo(),
			$requestQuery
		));

        $this->verifySignature($request->query, $this->getParameter('inke_app_key'), 'sig', 'md5', 'nonce_str');
		$appId = $request->query->get('appid', '');
		if (0 === strcmp($appId, $this->getParameter('inke_app_id'))) {
			$userId = $request->query->get('uuid');
			$user = $this->account()->fetchOne($userId);
			if ($user) {
				$genderMap = [
					User::GENDER_MALE => 1,
					User::GENDER_FEMALE => 0,
				];

                $genderInt = $genderMap[$user->gender];

				$response = [
					'error' => 0,
					'error_msg' => '已授权',
					'uuid' => $userId,
					'data' => [
						'nick' => $user->nickname? $user->nickname:'',
						'gender' => $genderInt? $genderInt: 1,
						'portrait' => $user->avatarUrl? $user->avatarUrl:'',
						'phone' => '',
					],
				];
			} else {
				$response = [
					'error' => 1003,
					'error_msg' => '用户不存在',
					'uuid' => $userId,
					'data' => [
						'nick' => '',
						'gender' => '',
						'portrait' => '',
						'phone' => '',
					],
				];
			}

			$logger->info(sprintf("[%s]%s", $requestId, json_encode($response)));
			return $this->dataResponse($response);
		}

		$logger->critical(sprintf("[%s]Failure: %s", $requestId, $requestQuery));
		return $this->failureResponse();
	}

	/**
	 * @Route("/inke/uids")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="live",
	 *   parameters={
	 *     {"name"="nonce_str", "dataType"="string", "required"=true, "description"="随机字符串"},
	 *     {"name"="sig", "dataType"="string", "required"=true,
	 *     "description"="请求签名,算法同微信见附https://pay.weixin.qq.com/wiki/doc/api/app/app.php?chapter=4_3"},
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function fetchInkeUidsAction(Request $request) {
		$this->verifySignature($request->query, $this->getParameter('inke_app_key'), 'sig', 'md5', 'nonce_str');
		/** @var Logger $logger */
		$logger = $this->get('monolog.logger.thirdparty_invoke');
		$logger->info(sprintf("[%s] %s", $request->getPathInfo(), $request->getQueryString()));

		return $this->dataResponse([
			'msg' => 'success',
			'status' => '1',
			'data' => $this->live()->fetchAllInkeUid()
		]);
	}

	/**
	 * @Route("/user/push")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="live",
	 *   parameters={}
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function inkePushAction(Request $request) {
		/** @var Logger $logger */
		$logger = $this->get('monolog.logger.thirdparty_invoke');
		$logger->info(sprintf("[%s] %s", $request->getPathInfo(), $request->getQueryString()));

//		$this->verifySignature($request->query, $this->getParameter('inke_app_key'), 'sig', 'md5', 'access_time');
		$appid = $this->requireParam($request, 'appid');
		$source = $this->requireParam($request, 'source');
		if ($appid !== $this->getParameter('inke_app_id') || $source !== 'inke_push') {
			return $this->failureResponse();
		}
		$content = $request->getContent();
		$logger->info($request->getContent());
		$content = json_decode($content, true);
		if (is_array($content) &&
		    array_key_exists('users', $content) &&
		    array_key_exists('desc', $content) &&
		    array_key_exists('info', $content)
		) {
			$this->verifyInkePushSignature($request->query, $content);
			$uuids = json_decode($content['users'], true);
			if (!is_array($uuids) || !array_key_exists('uuid', $uuids)) {
				return $this->failureResponse();
			}
			$userIds = $uuids['uuid'];
			if (!is_array($userIds)) {
				return $this->failureResponse();
			}
			while (!empty($userIds)) {
				$partialUserIds = array_splice($userIds, 0, 1000);
				$settings = $this->pushSettingManager()->fetch($partialUserIds);
				$now = new \DateTime();
				$pushableIds = [];
				foreach ($partialUserIds as $userId) {
					$setting = isset($settings[$userId]) ? $settings[$userId] : new PushSetting();
					if (!$setting->isTimeNoDisturbing($now)) {
						if ($setting->followeeAnchorType == PushSetting::TYPE_ALL) {
							$pushableIds[] = $userId;
						}
					}
				}
				if (empty($pushableIds)) {
					continue;
				}
				$desc = $content['desc'] ? $content['desc'] : '你关注的主播正在直播，点击围观';
				$info = $content['info'];
				$extras = [
					'type' => 'promotion',
					'linfo' => $info,
				];
				/**
				 * @var \JPush\JPushClient $jpushClient
				 */
				$jpushClient = $this->container()->get('jpush.client');
				$pushPayload = $jpushClient->push();
				$pushPayload->setAudience(Model\alias($pushableIds));

				$androidMessage = Model\android($desc, null, null, $extras);
				$iosMessage = Model\ios($desc, 'default', null, null, $extras);
				$notification = Model\notification($desc, $iosMessage, $androidMessage);
				$pushPayload->setPlatform(Model\all);
				$pushPayload->setNotification($notification);
				$pushPayload->setOptions(Model\options(null, null, null, true));
				try {
					$pushPayload->send();
				} catch (\Exception $e) {

				}
			}

			return $this->dataResponse([
				'error' => 0, // 4001: 推送失败
				'error_msg' => '推送成功'
			]);
		} else {
			return $this->failureResponse();
		}
	}

	/**
	 * @Route("/live/top")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="live",
	 *   parameters={}
	 * )
	 * @return JsonResponse
	 */
	public function getTopInkeLiveAction() {
		$livesInfo = $this->live()->fetchAllStickyInkeLiveREcommendation();
		$inkeUids = ArrayUtility::columns($livesInfo, 'inkeUid');
		if (!$inkeUids) {
			$inkeUids = [];
		}
		return $this->dataResponse([
			'top_lives' => $inkeUids
		]);
	}

	/**
	 * @param ParameterBag $parameterBag
	 * @param $content
	 *
	 * @throws ErrorsException
	 */
	private function verifyInkePushSignature(ParameterBag $parameterBag, $content) {
		$appKey = $this->getParameter('inke_app_key');
		$sigKey = 'sig';
		$params = $parameterBag->all();
		$sig = $params[$sigKey];
		unset($params[$sigKey]);
		$sigParams = array_merge($params, $content);
		ksort($sigParams);
		$paramsArr = [];
		foreach ($sigParams as $key => $p) {
			$paramsArr[] = "$key=$p";
		}
		$paramsArr[] = "key=$appKey";

		if (0 !== strcmp($sig, strtoupper(hash('md5', implode('&', $paramsArr))))) {
			throw new ErrorsException(CommonError::SignatureInvalid());
		}
	}

	/**
	 * @Route("/live/record")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="live",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true},
	 *     {"name"="start_time", "dataType"="integer", "required"=true, "description"="开播时间戳"},
	 *     {"name"="duration", "dataType"="integer", "required"=true, "description"="开播分钟数"}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function addLiveRecordAction(Request $request) {
		$account = $this->requireAuth($request);
		$startTime = $this->requireParam($request->request, 'start_time');
		$duration = $this->requireParam($request->request, 'duration');
		$startTime = (new \DateTime())->setTimestamp($startTime);
		$this->live()->addLiveRecord($account->id, $startTime, $duration);

		//结束直播精选的记录
		$this->post()->finishLivePost($account->id);

		return $this->sucessResponse();
	}
}