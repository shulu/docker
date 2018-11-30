<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 16/01/2017
 * Time: 7:49 PM
 */

namespace Lychee\Bundle\ApiBundle\Controller;

use Lsw\MemcacheBundle\Cache\MemcacheInterface;
use Lychee\Bundle\ApiBundle\Error\AuthenticationError;
use Lychee\Module\Authentication\Entity\AuthToken;
use Lychee\Module\ExtraMessage\EMGrantType;
use Lychee\Module\ExtraMessage\Entity\EMClientVersion;
use Lychee\Module\ExtraMessage\Entity\EMPaymentRanking;
use Lychee\Module\ExtraMessage\Entity\EMPictureRecord;
use Lychee\Module\ExtraMessage\Entity\EMPromotionCode;
use Lychee\Module\ExtraMessage\Entity\EMUser;
use Lychee\Module\Payment\Entity\PaymentProduct;
use Lychee\Module\Payment\Entity\PaymentProductPurchased;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Lychee\Bundle\ApiBundle\Error\PromotionCodeError;
use Symfony\Component\HttpFoundation\Response;
use Lychee\Module\ExtraMessage\EMAuthenticationService;
use Lychee\Bundle\ApiBundle\Error\ErrorsException;
use Lychee\Module\Payment\PurchaseRecorder;
use Lychee\Bundle\ApiBundle\Error\PaymentError;
use Lychee\Bundle\CoreBundle\Validator\Constraints\NotSensitiveWord;
use Lychee\Bundle\ApiBundle\Error\CommonError;
use Lychee\Component\Foundation\HttpUtility;

class ExtraMessageController extends Controller {

	/**
	 * @Route("/extramessage/contacts")
	 * @method("POST")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   parameters={
	 *     {"name"="device_id", "dataType"="string", "required"=true, "description"="设备ID"},
	 *     {"name"="qq_or_email", "dataType"="string", "required"=true, "description"="QQ或Email"},
	 *     {"name"="nonce", "dataType"="string", "required"=true, "description"="随机字符串"},
	 *     {"name"="sig", "dataType"="string", "required"=true, "description"="数字签名"}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function appendContactsAction(Request $request) {
		$this->verifySignature($request->request);
		$deviceId = $this->requireParam($request->request, 'device_id');
		$qqOrMail = $this->requireParam($request->request, 'qq_or_email');
		$this->extraMessageService()->appendContacts($deviceId, $qqOrMail);

		return $this->sucessResponse();
	}

	/**
	 * @Route("/extramessage/download/redirect")
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function redirectDownloadAction(Request $request) {
		$userAgent = $request->server->get('HTTP_USER_AGENT');

		$iphoneLink = 'https://itunes.apple.com/cn/app/yi-ci-yuan-tong-xun2/id1193559690?l=zh&ls=1&mt=8';
		$taptapLink = 'https://www.taptap.com/app/34949';

		$downloadUrl = null;
		if (stripos($userAgent, 'MicroMessenger') !== false) {
			$downloadUrl = $taptapLink;
		} elseif (stripos($userAgent, 'Weibo') !== false) {
			$downloadUrl = $taptapLink;
		} else if (stripos($userAgent, 'Android') !== false) {
			$downloadUrl = $taptapLink;
		} else if (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPod') !== false) {
			$downloadUrl = $iphoneLink;
		} else {
			$downloadUrl = $taptapLink;
		}

		if ($downloadUrl == null) {
			throw $this->createNotFoundException();
		}

		return $this->redirect($downloadUrl);
	}

	/**
	 * @Route("/extramessage/plays/{id}")
	 * @method("GET")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   parameters={
	 *
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function getPlays($id) {
		$item = $this->extraMessageService()->getPlayById($id);
		$allowHost = '*';
		$response = new Response();
		$response->headers->set('Access-Control-Allow-Origin', $allowHost);
		$response->setContent(json_encode($item));
		return $response;
	}

	/**
	 * @Route("/extramessage/options/{id}")
	 * @method("GET")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   parameters={
	 *
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function getOptions($id) {
		$items = $this->extraMessageService()->getoptionsByOptionId($id);
		$allowHost = '*';
		$response = new Response();
		$response->headers->set('Access-Control-Allow-Origin', $allowHost);
		$response->setContent(json_encode($items));
		return $response;
	}

	/**
	 * @Route("/extramessage/signin/sina_weibo")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="异次元通讯新浪微博登录",
	 *   parameters={
	 *     {"name"="weibo_token", "dataType"="string", "required"=true,
	 *       "description"="微博的access_token"},
	 *     {"name"="weibo_uid", "dataType"="string", "required"=true,
	 *       "description"="微博的用户id"},
	 *     {"name"="nickname", "dataType"="string", "required"=true,
	 *       "description"="注册用户的昵称"},
	 *     {"name"="device_id", "dataType"="string", "required"=true,
	 *       "description"="设备唯一标识"},
	 *     {"name"="nonce", "dataType"="string", "required"=true},
	 *     {"name"="sig", "dataType"="string", "required"=true},
	 *     {"name"="app_os_version", "dataType"="string", "required"=false,
	 *       "description"="手机os版本号"},
	 *     {"name"="app_ver", "dataType"="string", "required"=false,
	 *       "description"="app版本号"},
	 *     {"name"="channel", "dataType"="string", "required"=false,
	 *       "description"="渠道"},
	 *     {"name"="client", "dataType"="string", "required"=true,
	 *       "description"="客户端类型"},
	 *     {"name"="device_name", "dataType"="string", "required"=false,
	 *       "description"="客户端名称"},
	 *     {"name"="uuid", "dataType"="string", "required"=false,
	 *       "description"="客户端唯一标识"},
	 *     {"name"="nonce", "dataType"="string", "required"=true},
	 *     {"name"="sig", "dataType"="string", "required"=true}
	 *   }
	 * )
	 */
	public function signinWithWeibo(Request $request) {

		$this->verifySignature( $request->request );

		$token    = $this->requireParam( $request->request, 'weibo_token' );
		$weiboUid = $this->requireInt( $request->request, 'weibo_uid' );
		$nickname = $this->requireParam( $request->request, 'nickname' );
		$uuid     = $this->requireParam( $request->request, 'device_id' );
		$os       = $this->requireParam( $request->request, 'client' );

		if ( $this->emAuthenticationService()->weiboTokenIsValid( $token, $weiboUid ) === false ) {
			return $this->errorsResponse( AuthenticationError::WeiboTokenInvalid() );
		}

		$isNewUser = false;
		$userId    = $this->emAuthenticationService()->getUserIdByWeiboUid( $weiboUid );
		if ( $userId === null ) {
			$user = $this->emAuthenticationService()->createWithPhone( null, null, $nickname );
			$this->emAuthenticationService()->registerWeiboUidWithUserId( $weiboUid, $user->id );
			$isNewUser = true;
		} else {
			$user = $this->emAuthenticationService()->fetchAccount( $userId );
		}

		if ($os == 'ios') {

			$hasRecords = $this->purchaseRecorder()->hasUploadedReceipt($uuid, PaymentProduct::EXTRAMESSAGE_APP_ID);
			if($hasRecords == false){
				$records = $this->purchaseRecorder()->listUserRecords( $uuid, PaymentProduct::EXTRAMESSAGE_APP_ID);
				$hasRecords = count( $records ) > 0;
			}

		} else {

			$records = $this->purchaseRecorder()->listUserRecords( $uuid, PaymentProduct::EXTRAMESSAGE_APP_ID);
			$hasRecords = count( $records ) > 0;
		}

		return $this->createAuthenticationResponse($user, EMAuthenticationService::GRANT_TYPE_WEIBO, $isNewUser, $hasRecords);
	}

	/**
	 * @param string $accessKey
	 * @param string $uid
	 *
	 * @return array|null
	 */
	public function bilibiliAccessKeyIsValid($accessKey, $uid) {
		$params = array(
			'access_key' => $accessKey,
			'uid' => $uid
		);

		$params = $this->buildAndSignBilibiliParams($params);

		$url = 'http://pnew.biligame.net/api/server/session.verify';
		$json = HttpUtility::postBilibiliJson($url, $params);
		if ($json !== null && $json['code'] == 0) {
			return $json;
		} else {
			$this->getLogger()->critical('extra message bilibili access key verify fail.', array(
				'param' => $params));
			return null;
		}
	}

	/**
	 * @Route("/extramessage/signin/bilibili")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="异次元通讯bilibili登录",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true,
	 *       "description"="bilibili access token"},
	 *     {"name"="uid", "dataType"="string", "required"=true,
	 *       "description"="B站用户uid"},
	 *     {"name"="device_id", "dataType"="string", "required"=true,
	 *       "description"="设备唯一标识"},
	 *     {"name"="app_os_version", "dataType"="string", "required"=false,
	 *       "description"="手机os版本号"},
	 *     {"name"="app_ver", "dataType"="string", "required"=false,
	 *       "description"="app版本号"},
	 *     {"name"="channel", "dataType"="string", "required"=false,
	 *       "description"="渠道"},
	 *     {"name"="client", "dataType"="string", "required"=true,
	 *       "description"="客户端类型"},
	 *     {"name"="device_name", "dataType"="string", "required"=false,
	 *       "description"="客户端名称"},
	 *     {"name"="uuid", "dataType"="string", "required"=false,
	 *       "description"="客户端唯一标识"},
	 *     {"name"="nonce", "dataType"="string", "required"=true},
	 *     {"name"="sig", "dataType"="string", "required"=true}
	 *   }
	 * )
	 */
	public function signinWithBilibili(Request $request) {

		//$this->verifySignature($request->request);

		$access_token = $this->requireParam($request->request, 'access_token');
		$uid = $this->requireParam($request->request, 'uid');
		$uuid = $this->requireParam($request->request, 'device_id');
		$os       = $this->requireParam( $request->request, 'client' );

		$ret = $this->bilibiliAccessKeyIsValid($access_token, $uid);
		if (empty($ret)) {
			return $this->errorsResponse(AuthenticationError::BilibiliAccessKeyInvalid());
		}

		$openId = $ret['open_id'];
		$userId = $this->emAuthenticationService()->getUserIdByBilibiliOpenId($uid);

		$this->getLogger()->info('Bilibili get user info ', array(
			'ret' => $ret
		));

		$isNewUser = false;
		if ($userId === null) {
			$nickname = $ret['uname'];
			$user = $this->emAuthenticationService()->createWithPhone(null, null, $nickname);
			$this->emAuthenticationService()->registerBilibiliOpenIdWithUserId($openId, $user->id);
			$isNewUser = true;
		} else {
			$user = $this->emAuthenticationService()->fetchAccount($userId);
		}


		if ($os == 'ios') {

			$hasRecords = $this->purchaseRecorder()->hasUploadedReceipt($uuid, PaymentProduct::EXTRAMESSAGE_APP_ID);

			if($hasRecords == false){
				$records = $this->purchaseRecorder()->listUserRecords( $uuid, PaymentProduct::EXTRAMESSAGE_APP_ID);
				$hasRecords = count( $records ) > 0;
			}

		} else {

			$records = $this->purchaseRecorder()->listUserRecords( $uuid, PaymentProduct::EXTRAMESSAGE_APP_ID);
			$hasRecords = count( $records ) > 0;
		}

//		$isNewUser = false;
//		$hasRecords = false;
//		$user = $this->emAuthenticationService()->fetchAccount(29);

		return $this->createAuthenticationResponse($user, EMAuthenticationService::GRANT_TYPE_BILIBILI, $isNewUser, $hasRecords);
	}

	/**
	 * @param string $token
	 * @param string $uid
	 *
	 * @return array|null
	 */
	public function dmzjAccessTokenIsValid($token, $uid) {
		$params = array(
			'token' => $token,
			'uid' => $uid
		);

		$url = 'http://i.dmzj.com/youyifu/token';
		$json = HttpUtility::postJson($url, $params);
		if ($json !== null && isset($json['uid']) && $json['uid'] > 0) {
			return $json;
		} else {
			$this->getLogger()->critical('extra message dmzj token invalid.', array(
				'param' => $params, 'response' => $json));
			return null;
		}
	}

	/**
	 * @Route("/extramessage/signin/dmzj")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="异次元通讯动漫之家登录",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true,
	 *       "description"="bilibili access token"},
	 *     {"name"="uid", "dataType"="string", "required"=true,
	 *       "description"="B站用户uid"},
	 *     {"name"="device_id", "dataType"="string", "required"=true,
	 *       "description"="设备唯一标识"},
	 *     {"name"="app_os_version", "dataType"="string", "required"=false,
	 *       "description"="手机os版本号"},
	 *     {"name"="app_ver", "dataType"="string", "required"=false,
	 *       "description"="app版本号"},
	 *     {"name"="channel", "dataType"="string", "required"=false,
	 *       "description"="渠道"},
	 *     {"name"="client", "dataType"="string", "required"=true,
	 *       "description"="客户端类型"},
	 *     {"name"="device_name", "dataType"="string", "required"=false,
	 *       "description"="客户端名称"},
	 *     {"name"="uuid", "dataType"="string", "required"=false,
	 *       "description"="客户端唯一标识"},
	 *     {"name"="nonce", "dataType"="string", "required"=true},
	 *     {"name"="sig", "dataType"="string", "required"=true}
	 *   }
	 * )
	 */
	public function signinWithDmzj(Request $request) {

		//$this->verifySignature($request->request);

		$access_token = $this->requireParam($request->request, 'access_token');
		$uid = $this->requireParam($request->request, 'uid');
		$uuid = $this->requireParam($request->request, 'device_id');
		$os       = $this->requireParam( $request->request, 'client' );

		$ret = $this->dmzjAccessTokenIsValid($access_token, $uid);
		if (empty($ret)) {
			return $this->errorsResponse(AuthenticationError::DmzjTokenInvalid());
		}

		$openId = $ret['uid'];
		$userId = $this->emAuthenticationService()->getUserIdByDmzjOpenId($uid);

		$this->getLogger()->info('dmzj get user info ', array(
			'ret' => $ret
		));

		$isNewUser = false;
		if ($userId === null) {
			$nickname = $ret['nickname'];
			$user = $this->emAuthenticationService()->createWithPhone(null, null, $nickname);
			if (isset($ret['avatar']) && $ret['avatar']) {
				$user->avatarUrl = $ret['avatar'];
				$this->emAuthenticationService()->updateUserInfo($user);
			}

			$this->emAuthenticationService()->registerDmzjOpenIdWithUserId($openId, $user->id);
			$isNewUser = true;
		} else {
			$user = $this->emAuthenticationService()->fetchAccount($userId);
		}


		if ($os == 'ios') {

			$hasRecords = $this->purchaseRecorder()->hasUploadedReceipt($uuid, PaymentProduct::EXTRAMESSAGE_APP_ID);

			if($hasRecords == false){
				$records = $this->purchaseRecorder()->listUserRecords( $uuid, PaymentProduct::EXTRAMESSAGE_APP_ID);
				$hasRecords = count( $records ) > 0;
			}

		} else {

			$records = $this->purchaseRecorder()->listUserRecords( $uuid, PaymentProduct::EXTRAMESSAGE_APP_ID);
			$hasRecords = count( $records ) > 0;
		}

//		$isNewUser = false;
//		$hasRecords = false;
//		$user = $this->emAuthenticationService()->fetchAccount(29);

		return $this->createAuthenticationResponse($user, EMAuthenticationService::GRANT_TYPE_DMZJ, $isNewUser, $hasRecords);
	}

	/**
	 * @Route("/extramessage/signin/qq")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="异次元通讯QQ登录",
	 *   parameters={
	 *     {"name"="token", "dataType"="string", "required"=true,
	 *       "description"="qq api的access_token"},
	 *     {"name"="open_id", "dataType"="string", "required"=true,
	 *       "description"="qq api的open id"},
	 *     {"name"="nickname", "dataType"="string", "required"=true,
	 *       "description"="注册用户的昵称"},
	 *     {"name"="device_id", "dataType"="string", "required"=true,
	 *       "description"="设备唯一标识"},
	 *     {"name"="app_os_version", "dataType"="string", "required"=false,
	 *       "description"="手机os版本号"},
	 *     {"name"="app_ver", "dataType"="string", "required"=false,
	 *       "description"="app版本号"},
	 *     {"name"="channel", "dataType"="string", "required"=false,
	 *       "description"="渠道"},
	 *     {"name"="client", "dataType"="string", "required"=true,
	 *       "description"="客户端类型"},
	 *     {"name"="device_name", "dataType"="string", "required"=false,
	 *       "description"="客户端名称"},
	 *     {"name"="uuid", "dataType"="string", "required"=false,
	 *       "description"="客户端唯一标识"},
	 *     {"name"="nonce", "dataType"="string", "required"=true},
	 *     {"name"="sig", "dataType"="string", "required"=true}
	 *   }
	 * )
	 */
	public function signinWithQQ(Request $request) {

		$this->verifySignature($request->request);

		$token = $this->requireParam($request->request, 'token');
		$openId = $this->requireParam($request->request, 'open_id');
		$nickname = $this->requireParam($request->request, 'nickname');
		$uuid = $this->requireParam($request->request, 'device_id');
		$os       = $this->requireParam( $request->request, 'client' );

		if (preg_match('/^[0-9A-F]{32}$/i', $openId) == false) {
			return $this->errorsResponse(AuthenticationError::QQOpenIdInvalid());
		}
		if ($this->emAuthenticationService()->qqOpenIdIsValid($token, $openId) === false) {
			return $this->errorsResponse(AuthenticationError::QQTokenInvalid());
		}

		$userId = $this->emAuthenticationService()->getUserIdByQQOpenId($openId);

		$isNewUser = false;
		if ($userId === null) {
			$user = $this->emAuthenticationService()->createWithPhone(null, null, $nickname);
			$this->emAuthenticationService()->registerQQOpenIdWithUserId($openId, $user->id);
			$isNewUser = true;
		} else {
			$user = $this->emAuthenticationService()->fetchAccount($userId);
		}

		if ($os == 'ios') {

			$hasRecords = $this->purchaseRecorder()->hasUploadedReceipt($uuid, PaymentProduct::EXTRAMESSAGE_APP_ID);

			if($hasRecords == false){
				$records = $this->purchaseRecorder()->listUserRecords( $uuid, PaymentProduct::EXTRAMESSAGE_APP_ID);
				$hasRecords = count( $records ) > 0;
			}

		} else {

			$records = $this->purchaseRecorder()->listUserRecords( $uuid, PaymentProduct::EXTRAMESSAGE_APP_ID);
			$hasRecords = count( $records ) > 0;
		}

		return $this->createAuthenticationResponse($user, EMAuthenticationService::GRANT_TYPE_QQ, $isNewUser, $hasRecords);
	}

	/**
	 * @Route("/extramessage/signin/wechat")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="异次元通讯微信登录",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true,
	 *       "description"="微信的access_token"},
	 *     {"name"="open_id", "dataType"="string", "required"=true,
	 *       "description"="微信的open id"},
	 *     {"name"="nickname", "dataType"="string", "required"=true,
	 *       "description"="注册用户的nickname"},
	 *     {"name"="device_id", "dataType"="string", "required"=true,
	 *       "description"="设备唯一标识"},
	 *     {"name"="nonce", "dataType"="string", "required"=true},
	 *     {"name"="sig", "dataType"="string", "required"=true},
	 *     {"name"="app_os_version", "dataType"="string", "required"=false,
	 *       "description"="手机os版本号"},
	 *     {"name"="app_ver", "dataType"="string", "required"=false,
	 *       "description"="app版本号"},
	 *     {"name"="channel", "dataType"="string", "required"=false,
	 *       "description"="渠道"},
	 *     {"name"="client", "dataType"="string", "required"=true,
	 *       "description"="客户端类型"},
	 *     {"name"="device_name", "dataType"="string", "required"=false,
	 *       "description"="客户端名称"},
	 *     {"name"="uuid", "dataType"="string", "required"=false,
	 *       "description"="客户端唯一标识"},
	 *     {"name"="nonce", "dataType"="string", "required"=true},
	 *     {"name"="sig", "dataType"="string", "required"=true}
	 *   }
	 * )
	 */
	public function signinWithWechat(Request $request) {

		$this->verifySignature($request->request);

		$token = $this->requireParam($request->request, 'access_token');
		$openId = $this->requireParam($request->request, 'open_id');
		$nickname = $this->requireParam($request->request, 'nickname');
		$uuid = $this->requireParam($request->request, 'device_id');
		$os       = $this->requireParam( $request->request, 'client' );

		if ($this->emAuthenticationService()->wechatTokenIsValid($token, $openId) === false) {
			$this->getLogger()->error('wechat auth fail.', array(
				'client_version' => $request->get(self::CLIENT_APP_VERSION_KEY),
				'client_platform' => $request->get(self::CLIENT_PLATFORM_KEY),
				'client_os_version' => $request->get(self::CLIENT_OS_VERSION_KEY),
			));
			return $this->errorsResponse(AuthenticationError::WechatTokenInvalid());
		}

		$isNewUser = false;
		$userId = $this->emAuthenticationService()->getUserIdByWechatOpenId($openId);
		if ($userId === null) {
			$user = $this->emAuthenticationService()->createWithPhone(null, null, $nickname);
			$this->emAuthenticationService()->registerWechatOpenIdWithUserId($openId, $user->id);
			$isNewUser = true;
		} else {
			$user = $this->emAuthenticationService()->fetchAccount($userId);
		}

		if ($os == 'ios') {

			$hasRecords = $this->purchaseRecorder()->hasUploadedReceipt($uuid, PaymentProduct::EXTRAMESSAGE_APP_ID);
			if($hasRecords == false){
				$records = $this->purchaseRecorder()->listUserRecords( $uuid, PaymentProduct::EXTRAMESSAGE_APP_ID);
				$hasRecords = count( $records ) > 0;
			}

		} else {

			$records = $this->purchaseRecorder()->listUserRecords( $uuid, PaymentProduct::EXTRAMESSAGE_APP_ID);
			$hasRecords = count( $records ) > 0;
		}

		return $this->createAuthenticationResponse($user, EMAuthenticationService::GRANT_TYPE_WECHAT, $isNewUser, $hasRecords);
	}

	/**
	 * @param EMUser $user
	 * @param string $grantType
	 * @param bool $isNewUser
	 * @return JsonResponse
	 */
	private function createAuthenticationResponse($user, $grantType, $isNewUser, $hasPurchaseRecord) {
		$token = $this->emAuthenticationService()->createTokenForUser($user->id, $grantType);

		$genderInt = $user->gender ? $user->gender : EMUser::GENDER_MALE;
		$userArray = array(

			'id' => $user->id,
			'nickname' => $user->nickname,
			'avatarUrl' => $user->avatarUrl ? $user->avatarUrl : '',
			'birthday' => $user->birthday ? $user->birthday->format('Y-m-d') : '',
			'gender' => $genderInt,
			'location' => $user->location ? $user->location : '',
			'age' => $user->age ? $user->age : 0,
			'signature' => $user->signature ? $user->signature : ''
		);

		return $this->dataResponse(array(
			'access_token' => $token->accessToken,
			'expires_in' => $token->ttl,
			'expires_at' => time() + $token->ttl - 1,
			'account' => $userArray,
			'is_new' => $isNewUser,
			'has_purchase_record' => $hasPurchaseRecord
		));
	}

	/**
	 * @Route("/extramessage/signin/facebook")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="异次元通讯Facebook登录",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true,
	 *       "description"="微信的access_token"},
	 *     {"name"="open_id", "dataType"="string", "required"=true,
	 *       "description"="微信的open id"},
	 *     {"name"="nickname", "dataType"="string", "required"=true,
	 *       "description"="注册用户的nickname"},
	 *     {"name"="device_id", "dataType"="string", "required"=true,
	 *       "description"="设备唯一标识"},
	 *     {"name"="nonce", "dataType"="string", "required"=true},
	 *     {"name"="sig", "dataType"="string", "required"=true},
	 *     {"name"="app_os_version", "dataType"="string", "required"=false,
	 *       "description"="手机os版本号"},
	 *     {"name"="app_ver", "dataType"="string", "required"=false,
	 *       "description"="app版本号"},
	 *     {"name"="channel", "dataType"="string", "required"=false,
	 *       "description"="渠道"},
	 *     {"name"="client", "dataType"="string", "required"=true,
	 *       "description"="客户端类型"},
	 *     {"name"="device_name", "dataType"="string", "required"=false,
	 *       "description"="客户端名称"},
	 *     {"name"="uuid", "dataType"="string", "required"=false,
	 *       "description"="客户端唯一标识"},
	 *     {"name"="nonce", "dataType"="string", "required"=true},
	 *     {"name"="sig", "dataType"="string", "required"=true}
	 *   }
	 * )
	 */
    public function signinWithFacebook(Request $request)
    {
//        $this->verifySignature($request->request);

        $token = $this->requireParam($request->request, 'access_token');
        $fbUserId = $this->requireParam($request->request, 'open_id');
        $nickname = $this->requireParam($request->request, 'nickname');
        $uuid = $this->requireParam($request->request, 'device_id');
        $os       = $this->requireParam( $request->request, 'client' );

        $appid = $this->getParameter('facebook_appid');
        $secret = $this->getParameter('facebook_secret');
        $proxy_host = $this->getParameter('proxy_host');
        $proxy_port = $this->getParameter('proxy_port');

        $proxy = [];
        if ($proxy_host) {
	        $proxy = [
        		'host' => $proxy_host,
        		'port' => $proxy_port,
	        ];
        }

        list($isValid, $nickname, $avatar) = $this->emAuthenticationService()
	        ->facebookTokenIsValid($token, $fbUserId, $appid, $secret, $proxy);
        if (!$isValid) {
            return $this->errorsResponse(AuthenticationError::FacebookTokenInvalid());
        }

        $isNewUser = false;
        $userId = $this->emAuthenticationService()->getUserIdByFacebookUserId($fbUserId);
        if ($userId === null) {
            $user = $this->emAuthenticationService()->createWithPhone(null, null, $nickname, EMAuthenticationService::GRANT_TYPE_FACEBOOK);

            $this->emAuthenticationService()->registerFacebookUserIdWithUserId($fbUserId, $user->id);
            $isNewUser = true;
        } else {
            $user = $this->emAuthenticationService()->fetchAccount($userId);
	        if ($avatar && !$user->avatarUrl) {
		        $user->avatarUrl = $avatar;
		        $this->emAuthenticationService()->updateUserInfo($user);
	        }
        }

        if ($os == 'ios') {

            $hasRecords = $this->purchaseRecorder()->hasUploadedReceipt($uuid, PaymentProduct::EXTRAMESSAGE_APP_ID);
            if($hasRecords == false){
                $records = $this->purchaseRecorder()->listUserRecords( $uuid, PaymentProduct::EXTRAMESSAGE_APP_ID);
                $hasRecords = count( $records ) > 0;
            }

        } else {

            $records = $this->purchaseRecorder()->listUserRecords( $uuid, PaymentProduct::EXTRAMESSAGE_APP_ID);
            $hasRecords = count( $records ) > 0;
        }

        return $this->createAuthenticationResponse($user, EMAuthenticationService::GRANT_TYPE_FACEBOOK, $isNewUser, $hasRecords);

	}

	/**
	 *
	 *
	 * @Route("/extramessage/signout")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true},
	 *     {"name"="nonce", "dataType"="string", "required"=true},
	 *     {"name"="sig", "dataType"="string", "required"=true}
	 *   }
	 * )
	 */
	public function signoutAction(Request $request) {

		$this->verifySignature( $request->request );
		$accessToken = $request->request->get( 'access_token' );
		if (isset($accessToken)) {
			$this->emAuthenticationService()->revokeToken( $accessToken );
		}
		return $this->sucessResponse();
	}

	/**
	 * @Route("/extramessage/purchase_records/transfer_device_id")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="将以往使用device_id购买的记录转换成user_id",
	 *   parameters={
	 *     {"name"="device_id", "dataType"="string", "required"=true},
	 *     {"name"="access_token", "dataType"="string", "required"=true},
	 *     {"name"="client", "dataType"="string", "required"=true,
	 *       "description"="客户端类型"}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function transferDeviceId(Request $request) {

		$this->verifySignature($request->request);
		$deviceId = $this->requireDeviceId($request);
		$user = $this->requireEMAuth($request);
		$os = $this->requireParam($request->request, 'client');

		/** @var PurchaseRecorder $recorder */
		$recorder = $this->get('lychee.module.payment.purchase_recorder');

		if($os == 'ios') {
			$recorder->transferAppStoreReceiptDeviceId($deviceId, $user->id, PaymentProduct::EXTRAMESSAGE_APP_ID);
		}

		$recorder->transferPayerDeviceIdToUserId( $deviceId, $user->id, PaymentProduct::EXTRAMESSAGE_APP_ID );
		return $this->sucessResponse();
	}

	/**
	 * @Route("/extramessage/game_records")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="查询存档记录",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function getGameRecords(Request $request) {

		$account = $this->requireEMAuth($request);

		$existingRecord = $this->extraMessageService()->getGameRecordyUserId($account->id);

		if($existingRecord != null){

			$filename = $existingRecord->path;
			$response = new Response();

			// Set headers
			$response->headers->set('Cache-Control', 'private');
			$response->headers->set('Content-type', mime_content_type($filename));
			$response->headers->set('Content-Disposition', 'attachment; filename="' . basename($filename) . '";');
			$response->headers->set('Content-length', filesize($filename));

			// Send headers before outputting anything
			$response->sendHeaders();
			$response->setContent(file_get_contents($filename));
			return $response;

		} else {

			return new Response();
			//return $this->errorsResponse(AuthenticationError::WechatTokenInvalid());
		}

	}

	/**
	 * @Route("/extramessage/picture_records")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="查询图鉴记录",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function getPictureRecords(Request $request) {

		//$this->verifySignature($request->request);
		$account = $this->requireEMAuth($request);

		$existingRecord = $this->extraMessageService()->getPictureRecordByUserId($account->id);

		if($existingRecord != null){

			return $this->dataResponse(
				array(
					'record' => $existingRecord->record
				)
			);

		} else {
			return $this->dataResponse(
				array(
					'record' => ''
				)
			);
		}
	}

	/**
	 * @Route("/extramessage/picture_records/add")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="更新图鉴记录",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true},
	 *     {"name"="record", "dataType"="string", "required"=true, "description"="图鉴记录，json格式"}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function addPictureRecords(Request $request) {

		//$this->verifySignature($request->request);
		$account = $this->requireEMAuth($request);
		$record = $this->requireParam($request, 'record');
		$record = stripslashes($record);
		if (!empty($record)) {
			$this->extraMessageService()->addPictureRecord($account->id, $record);
		}

		return $this->sucessResponse();
	}

	/**
	 * @Route("/extramessage/purchase_records")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="查询购买记录",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function getPurchaseRecords(Request $request) {

		$this->verifySignature($request->request);
		$user = $this->requireEMAuth($request);
		$payer = $user->id;

		/** @var PurchaseRecorder | null $recorder */
		$recorder = $this->get('lychee.module.payment.purchase_recorder');
		$records = $recorder->listUserRecords($payer, PaymentProduct::EXTRAMESSAGE_APP_ID);
		$result = array_map(function($item) {
			return [
				'product_id' => $item['app_store_id'],
				'purchase_time' => strtotime($item['purchase_time']),
				'transaction_id' => $item['transaction_id'],
				'appstore_transaction_id' => $item['appstore_transaction_id'],
				'promotion_code_id' => $item['promotion_code_id']
			];
		}, $records);


		return $this->dataResponse(
			array(
				'records' => $result
			)
		);
	}

	/**
	 * @Route("/extramessage/purchase_records/test")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="查询购买记录",
	 *   parameters={
	 *     {"name"="payer", "dataType"="string", "required"=true}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function getPurchaseRecordsTest(Request $request) {

		$payer = $this->requireParam($request, 'payer');

		/** @var PurchaseRecorder | null $recorder */
		$recorder = $this->get('lychee.module.payment.purchase_recorder');
		$records = $recorder->listUserRecords($payer, PaymentProduct::EXTRAMESSAGE_APP_ID);
		$result = array_map(function($item) {
			return [
				'product_id' => $item['app_store_id'],
				'purchase_time' => strtotime($item['purchase_time']),
				'transaction_id' => $item['transaction_id'],
				'appstore_transaction_id' => $item['appstore_transaction_id'],
				'promotion_code_id' => $item['promotion_code_id']
			];
		}, $records);


		return $this->dataResponse(
			array(
				'records' => $result
			)
		);
	}



	/**
	 * @Route("/extramessage/promotion_code/story")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="获取自己某个章节的兑换码列表, 4.0版本以上用",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true},
	 *     {"name"="story_id", "dataType"="string", "required"=true, "description"="章节id"},
	 *     {"name"="nonce", "dataType"="string", "required"=true},
	 *     {"name"="sig", "dataType"="string", "required"=true},
	 *     {"name"="app_os_version", "dataType"="string", "required"=false,
	 *       "description"="手机os版本号"},
	 *     {"name"="app_ver", "dataType"="string", "required"=false,
	 *       "description"="app版本号"},
	 *     {"name"="channel", "dataType"="string", "required"=false,
	 *       "description"="渠道"},
	 *     {"name"="client", "dataType"="string", "required"=false,
	 *       "description"="客户端类型"},
	 *     {"name"="device_name", "dataType"="string", "required"=false,
	 *       "description"="客户端名称"},
	 *     {"name"="uuid", "dataType"="string", "required"=false,
	 *       "description"="客户端唯一标识"}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function getStoryPromotionCode(Request $request) {

		//$this->verifySignature( $request->request );

		$account   = $this->requireEMAuth( $request );
		$storyId = $this->requireParam( $request->query, 'story_id' );

		$records = $this->extraMessageService()->getPromotionCodeListForStory($account->id, $storyId);

		$result = array();
		foreach($records as $record){

			$result[] = array(
				'code' => $record['code'],
				'hasUsed' => isset($record['receiver_id']) ? 1 : 0
			);
		}

		return $this->dataResponse(
			array(
				'records' => $result
			)
		);
	}

	/**
	 * @Route("/extramessage/promotion_code/list")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="获取自己的兑换码列表，4.0以下版本使用",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true},
	 *     {"name"="appstore_product_id", "dataType"="string", "required"=true, "description"="苹果商品唯一标识"},
	 *     {"name"="nonce", "dataType"="string", "required"=true},
	 *     {"name"="sig", "dataType"="string", "required"=true},
	 *     {"name"="app_os_version", "dataType"="string", "required"=false,
	 *       "description"="手机os版本号"},
	 *     {"name"="app_ver", "dataType"="string", "required"=false,
	 *       "description"="app版本号"},
	 *     {"name"="channel", "dataType"="string", "required"=false,
	 *       "description"="渠道"},
	 *     {"name"="client", "dataType"="string", "required"=false,
	 *       "description"="客户端类型"},
	 *     {"name"="device_name", "dataType"="string", "required"=false,
	 *       "description"="客户端名称"},
	 *     {"name"="uuid", "dataType"="string", "required"=false,
	 *       "description"="客户端唯一标识"},
	 *     {"name"="nonce", "dataType"="string", "required"=true},
	 *     {"name"="sig", "dataType"="string", "required"=true}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function getPromotionCode(Request $request) {

		//$this->verifySignature( $request->request );

		$account   = $this->requireEMAuth( $request );
		$productId = $this->requireParam( $request->request, 'appstore_product_id' );
		$product = $this->productManager()->getProductByAppStoreId( $productId );

		$records = $this->extraMessageService()->getPromotionCodeList($account->id, $product->id);

		$result = array_map(function($promotionCode) {
			return [
				'code' => $promotionCode->code,
				'hasUsed' => isset($promotionCode->receiverId) ? 1 : 0
			];
		}, $records);

		return $this->dataResponse(
			array(
				'records' => $result
			)
		);

	}

	/**
	 * @Route("/extramessage/promotion_code/receive1")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="请求兑换兑换码，4.0版本使用 ",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true},
	 *     {"name"="code", "dataType"="string", "required"=true, "description"="兑换码"},
	 *     {"name"="nonce", "dataType"="string", "required"=true},
	 *     {"name"="sig", "dataType"="string", "required"=true},
	 *     {"name"="app_os_version", "dataType"="string", "required"=false,
	 *       "description"="手机os版本号"},
	 *     {"name"="app_ver", "dataType"="string", "required"=false,
	 *       "description"="app版本号"},
	 *     {"name"="channel", "dataType"="string", "required"=false,
	 *       "description"="渠道"},
	 *     {"name"="client", "dataType"="string", "required"=false,
	 *       "description"="客户端类型"},
	 *     {"name"="device_name", "dataType"="string", "required"=false,
	 *       "description"="客户端名称"},
	 *     {"name"="uuid", "dataType"="string", "required"=false,
	 *       "description"="客户端唯一标识"},
	 *     {"name"="nonce", "dataType"="string", "required"=true},
	 *     {"name"="sig", "dataType"="string", "required"=true}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function receivePromotionCode1(Request $request) {

		$this->verifySignature( $request->request );

		$account   = $this->requireEMAuth( $request );
		$code = $this->requireParam( $request->request, 'code' );

		/** @var EMPromotionCode | null $promotionCodeRecord */
		$promotionCodeRecord = $this->extraMessageService()->getPromotionCodeRecord($code);

		if (!isset( $promotionCodeRecord ) || $promotionCodeRecord->preGen == true) {
			return $this->errorsResponse( PromotionCodeError::wrongCode() );
		}

		if(isset($promotionCodeRecord->receiverId)){
			return $this->errorsResponse( PromotionCodeError::alreadyReceived() );
		}

		$productId = $promotionCodeRecord->productId;
		$receiverId = $account->id;

		$purchaseRecord = $this->purchaseRecorder()->getPurchaseRecord($receiverId, $productId);

		if(isset($purchaseRecord)){
			return $this->errorsResponse( PromotionCodeError::alreadyBuy() );
		}

		if($receiverId == $promotionCodeRecord->userId){
			return $this->errorsResponse( PromotionCodeError::selfReceive() );
		}

        if ($promotionCodeRecord->vendor) {
            /** @var AuthToken $accessToken */
            $accessToken = $this->emAuthenticationService()->getTokenByUserId($account->id);
            if ($accessToken->grantType == EMGrantType::BILIBILI || $accessToken->grantType == EMGrantType::DMZJ) {
                if ($promotionCodeRecord->vendor != $accessToken->grantType) {
                    return $this->errorsResponse( PromotionCodeError::wrongCode() );
                }
            }
            else if ($promotionCodeRecord->vendor == EMGrantType::BILIBILI || $promotionCodeRecord->vendor == EMGrantType::DMZJ) {
                return $this->errorsResponse( PromotionCodeError::wrongCode() );
            }
        }

		$this->extraMessageService()->makePromotionCodeReceive($promotionCodeRecord, $receiverId);

		//Get Story Id
		$config = $this->extraMessageService()->getPromotionConfig($productId);

		return $this->dataResponse(array(
			'story_id' => $config->storyId
		));
	}

	/**
	 * @Route("/extramessage/promotion_code/receive")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="请求兑换兑换码, 4.0以下版本使用",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true},
	 *     {"name"="code", "dataType"="string", "required"=true, "description"="兑换码"},
	 *     {"name"="nonce", "dataType"="string", "required"=true},
	 *     {"name"="sig", "dataType"="string", "required"=true},
	 *     {"name"="app_os_version", "dataType"="string", "required"=false,
	 *       "description"="手机os版本号"},
	 *     {"name"="app_ver", "dataType"="string", "required"=false,
	 *       "description"="app版本号"},
	 *     {"name"="channel", "dataType"="string", "required"=false,
	 *       "description"="渠道"},
	 *     {"name"="client", "dataType"="string", "required"=false,
	 *       "description"="客户端类型"},
	 *     {"name"="device_name", "dataType"="string", "required"=false,
	 *       "description"="客户端名称"},
	 *     {"name"="uuid", "dataType"="string", "required"=false,
	 *       "description"="客户端唯一标识"},
	 *     {"name"="nonce", "dataType"="string", "required"=true},
	 *     {"name"="sig", "dataType"="string", "required"=true}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function receivePromotionCode(Request $request) {

		$this->verifySignature( $request->request );

		$account   = $this->requireEMAuth( $request );
		$code = $this->requireParam( $request->request, 'code' );

		/** @var EMPromotionCode | null $promotionCodeRecord */
		$promotionCodeRecord = $this->extraMessageService()->getPromotionCodeRecord($code);

		if (!isset( $promotionCodeRecord )) {
			return $this->errorsResponse( PromotionCodeError::wrongCode() );
		}

		if(isset($promotionCodeRecord->receiverId)){
			return $this->errorsResponse( PromotionCodeError::alreadyReceived() );
		}

		$productId = $promotionCodeRecord->productId;
		$receiverId = $account->id;

		$purchaseRecord = $this->purchaseRecorder()->getPurchaseRecord($receiverId, $productId);

		if(isset($purchaseRecord)){
			return $this->errorsResponse( PromotionCodeError::alreadyBuy() );
		}

		if($receiverId == $promotionCodeRecord->userId){
			return $this->errorsResponse( PromotionCodeError::selfReceive() );
		}

		$this->extraMessageService()->makePromotionCodeReceive($promotionCodeRecord, $receiverId);

		return $this->sucessResponse();
	}

	/**
	 * @Route("/extramessage/heartbeat")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="定时检查登录是否有效",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
     * @throws ErrorsException
	 */
	public function getHeartBeat(Request $request) {

//		$this->requireEMAuth($request);

        $access_token = $request->get('access_token');
        if (!$access_token) {
            throw new ErrorsException(array(AuthenticationError::BadAuthentication()));
        }

        $user = $this->getEMUser($request);
        if ($user == null) {
            throw new ErrorsException(array(
                AuthenticationError::BadAuthentication(),
                AuthenticationError::AccessTokenInvalid(),
            ));
        }

		return $this->sucessResponse();
	}

	/**
	 * @Route("/extramessage/picture_record/get")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="获取图鉴信息",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true},
	 *     {"name"="id", "dataType"="string", "required"=true}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function getPictureRecordAction( Request $request ) {
		$user = $this->requireEMAuth($request);
		$picId = $this->requireParam($request->request, 'id');

		$dynamicUrl = 'http://ooldi5c9n.bkt.clouddn.com/ExtraMessage/Video/%s.mp4';
		/** @var EMPictureRecord $picRecords */
		$picRecords = $this->extraMessageService()->getPictureRecordByUserId($user->id);
		if ($picRecords && $picRecords->record) {
			$records = json_decode($picRecords->record, true);
			if (!in_array($picId, $records)) {
                $picId = str_replace('-', '_', $picId);
                if (!in_array($picId, $records)) {
                    $picId_lower = strtolower($picId);
                    if (!in_array($picId_lower, $records)) {
                        return $this->errorsResponse(CommonError::ParameterInvalid('id', $picId));
                    }
                }
			}
			$picId = strtolower($picId);
			$picId = str_replace('-', '_', $picId);
			$dynamicUrl = sprintf($dynamicUrl, $picId);
		}

		return $this->dataResponse([
			'dynamic' => $dynamicUrl
		]);
	}

	/**
	 * @Route("/extramessage/getversion")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="检查是否有新版本",
	 *   parameters={
	 *      {"name"="client", "dataType"="string", "required"=false,
	 *       "description"="客户端类型"},
	 *     {"name"="agent", "dataType"="string", "required"=false,
	 *       "description"="渠道"},
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function checkVersionAction(Request $request){

		$client = $this->requireParam($request, 'client');
		$agent = $request->get('agent');

		$type = EMClientVersion::TYPE_ANDROID;
		if($client == 'ios'){
			$type = EMClientVersion::TYPE_IOS;
		}
		else {
			if ($agent == 'bilibili') {
				$type = EMClientVersion::TYPE_ANDROID_BILIBILI;
			}
			else if ($agent == 'dmzj') {
				$type = EMClientVersion::TYPE_ANDROID_DMZJ;
			}
			else {
				$type = EMClientVersion::TYPE_ANDROID;
			}
		}

		$data = $this->getMemcache()->get('cache_extramessage_getversion' . $type);
		if ($data !== false) {
			return $this->dataResponse($data);
		}

		/** $result EMClientVersion */
		$result = $this->extraMessageService()->findClientVersion($type);
		$data = array(
			'version' => $result->version,
			'code' => $result->code,
			'url' => $result->url,
			'desc' => $result->desc,
			'force_update' => $result->forceUpdate
		);
		$this->getMemcache()->set('cache_extramessage_getversion' . $type, $data, 0, 3600);

		return $this->dataResponse($data);
	}

	/**
	 * @Route("/extramessage/payment/comment")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="投喂后添加评论",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true},
	 *     {"name"="comment", "dataType"="string", "required"=true, "description"="投喂评论"},
	 *     {"name"="transation_id", "dataType"="string", "required"=false, "description"="投喂记录id, 安卓必填"},
	 *     {"name"="app_store_transation_id", "dataType"="string", "required"=false, "description"="投喂记录id，iOS必填"},
	 *     {"name"="app_os_version", "dataType"="string", "required"=false,
	 *       "description"="手机os版本号"},
	 *     {"name"="app_ver", "dataType"="string", "required"=false,
	 *       "description"="app版本号"},
	 *     {"name"="channel", "dataType"="string", "required"=false,
	 *       "description"="渠道"},
	 *     {"name"="client", "dataType"="string", "required"=false,
	 *       "description"="客户端类型"},
	 *     {"name"="device_name", "dataType"="string", "required"=false,
	 *       "description"="客户端名称"},
	 *     {"name"="uuid", "dataType"="string", "required"=false,
	 *       "description"="客户端唯一标识"},
	 *     {"name"="nonce", "dataType"="string", "required"=true},
	 *     {"name"="sig", "dataType"="string", "required"=true}
	 *   }
	 * )
	 * @param Request $request
	 * @throws ErrorsException
	 * @return JsonResponse
	 */
	public function paymentCommentAction(Request $request){

		$account = $this->requireEMAuth($request);
		$comment = $this->requireParam($request, 'comment');

		if ($this->isValueValid($comment, array(new NotSensitiveWord())) === false ||
		    mb_strlen($comment, 'utf8') > 20) {
			return $this->errorsResponse(CommonError::ContainsSensitiveWords('comment', $comment));
		}

		$transactionId = $request->request->get('transation_id');
		$appStoreTransactionId = $request->request->get('app_store_transation_id');

		if(!empty($transactionId)){
			$purchaseRecord = $this->purchaseRecorder()->getRecordByTransactionId($transactionId);
		} else if(!empty($appStoreTransactionId)){
			$purchaseRecord = $this->purchaseRecorder()->getRecordByAppStoreTransactionId($appStoreTransactionId);
		} else {
			throw new ErrorsException(array(CommonError::ParameterMissing('transation_id')));
		}

		if(!empty($purchaseRecord)){

			$config = $this->extraMessageService()->getPromotionConfig($purchaseRecord->productId);
			$this->extraMessageService()->addPaymentComment($account->id, $purchaseRecord->id, $purchaseRecord->productId, $config->storyId, $comment);
		}else{
			return $this->errorsResponse(PaymentError::TransactionNotFound());
		}

		return $this->sucessResponse();
	}

	/**
	 * @Route("/extramessage/diary/save")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="保存日志",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true},
	 *     {"name"="diary", "dataType"="string", "required"=true, "description"="日志记录，JSON字符串"},
	 *     {"name"="app_os_version", "dataType"="string", "required"=false,
	 *       "description"="手机os版本号"},
	 *     {"name"="app_ver", "dataType"="string", "required"=false,
	 *       "description"="app版本号"},
	 *     {"name"="channel", "dataType"="string", "required"=false,
	 *       "description"="渠道"},
	 *     {"name"="client", "dataType"="string", "required"=false,
	 *       "description"="客户端类型"},
	 *     {"name"="device_name", "dataType"="string", "required"=false,
	 *       "description"="客户端名称"},
	 *     {"name"="uuid", "dataType"="string", "required"=false,
	 *       "description"="客户端唯一标识"},
	 *     {"name"="nonce", "dataType"="string", "required"=true},
	 *     {"name"="sig", "dataType"="string", "required"=true}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function saveDiaryAction(Request $request){

		$account = $this->requireEMAuth($request);
		$record = $this->requireParam($request, 'diary');

		$this->extraMessageService()->addDiaryRecord($account->id, $record);

		return $this->sucessResponse();
	}

	/**
	 * @Route("/extramessage/diary/get")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="查询日志记录",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function getDiaryRecords(Request $request) {

		$account = $this->requireEMAuth($request);

		$existingRecord = $this->extraMessageService()->getDiaryRecordByUserId($account->id);

		if($existingRecord != null){

			return $this->dataResponse(
				array(
					'record' => $existingRecord->record
				)
			);

		} else {
			return $this->dataResponse(
				array(
					'record' => ''
				)
			);
		}
	}

	/**
	 * @Route("/extramessage/dictionary/save")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="保存词典记录",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true},
	 *     {"name"="dictionary", "dataType"="string", "required"=true, "description"="词典记录，JSON字符串"},
	 *     {"name"="app_os_version", "dataType"="string", "required"=false,
	 *       "description"="手机os版本号"},
	 *     {"name"="app_ver", "dataType"="string", "required"=false,
	 *       "description"="app版本号"},
	 *     {"name"="channel", "dataType"="string", "required"=false,
	 *       "description"="渠道"},
	 *     {"name"="client", "dataType"="string", "required"=false,
	 *       "description"="客户端类型"},
	 *     {"name"="device_name", "dataType"="string", "required"=false,
	 *       "description"="客户端名称"},
	 *     {"name"="uuid", "dataType"="string", "required"=false,
	 *       "description"="客户端唯一标识"},
	 *     {"name"="nonce", "dataType"="string", "required"=true},
	 *     {"name"="sig", "dataType"="string", "required"=true}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function saveDictionaryAction(Request $request){

		$account = $this->requireEMAuth($request);
		$record = $this->requireParam($request, 'dictionary');
		$record = stripslashes($record);

		$this->extraMessageService()->addDictionaryRecord($account->id, $record);

		return $this->sucessResponse();
	}

	/**
	 * @Route("/extramessage/dictionary/get")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="查询词典记录",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function getDictionaryRecords(Request $request) {

		$account = $this->requireEMAuth($request);

		$existingRecord = $this->extraMessageService()->getDictionaryRecordByUserId($account->id);

		if($existingRecord != null){

			return $this->dataResponse(
				array(
					'record' => $existingRecord->record
				)
			);

		} else {
			return $this->dataResponse(
				array(
					'record' => ''
				)
			);
		}
	}

	/**
	 * @Route("/extramessage/ranking/story/recent")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="获取某个章节的投喂，按照投喂时间倒序返回前100个",
	 *   parameters={
	 *     {"name"="story_id", "dataType"="integer", "required"=true}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function recentRankingStoryAction(Request $request){

		$storyId = $this->requireParam($request, 'story_id');
		$data = $this->getMemcache()->get('cache_extramessage_payment_ranking_recent_story_' . $storyId);
		if ($data === false) {
			return $this->dataResponse([]);
		}
		return $this->dataResponse($data);
	}

	/**
	 * @Route("/extramessage/ranking/all")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="获取所有章节的投喂排名，按照投喂时间倒序返回前3个",
	 *   parameters={
	 *
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function rankingAllAction(Request $request){

		$data = $this->getMemcache()->get('cache_extramessage_payment_ranking_all');
		if ($data === false) {
			return $this->dataResponse([]);
		}
		return $this->dataResponse($data);
	}

	/**
	 * @Route("/extramessage/ranking/story")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="获取某个章节的投喂排名，按照投喂数额倒序返回前100个",
	 *   parameters={
	 *     {"name"="story_id", "dataType"="integer", "required"=true}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function rankingStoryAction(Request $request){

		$storyId = $this->requireParam($request, 'story_id');
		$data = $this->getMemcache()->get('cache_extramessage_payment_ranking_story_' . $storyId);
		if ($data === false) {
			return $this->dataResponse([]);
		}
		return $this->dataResponse($data);
	}


	/**
	 * @param ParameterBag $params
	 * @return string
	 * @throws ErrorsException
	 */
	private function requireDeviceId($params) {
		$deviceId = $this->requireParam($params, 'device_id');
		if (strlen($deviceId) > 64 || strlen($deviceId) == 0) {
			throw new ErrorsException(CommonError::ParameterInvalid('device_id', $deviceId));
		}
		return $deviceId;
	}

	/**
	 * @Route("/extramessage/profile/update")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="extramessage",
	 *   description="异次元通讯修改用户信息",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true},
	 *     {"name"="nickname", "dataType"="string", "required"=false},
	 *     {"name"="avatar_url", "dataType"="string", "required"=false},
	 *     {"name"="gender", "dataType"="string", "required"=false,
	 *       "description"="允许的值，female, male, none"},
	 *     {"name"="signature", "dataType"="string", "required"=false, "description"="签名，最长200个字符"},
	 *     {"name"="age", "dataType"="string", "required"=false, "description"="年龄，非负整数"},
	 *     {"name"="birthday", "dataType"="string", "required"=false, "description"="生日，格式为xxx-xx-xx"},
	 *     {"name"="location", "dataType"="string", "required"=false, "description"="所在地，最长200个字符"},
	 *     {"name"="app_os_version", "dataType"="string", "required"=false,
	 *       "description"="手机os版本号"},
	 *     {"name"="app_ver", "dataType"="string", "required"=false,
	 *       "description"="app版本号"},
	 *     {"name"="channel", "dataType"="string", "required"=false,
	 *       "description"="渠道"},
	 *     {"name"="client", "dataType"="string", "required"=false,
	 *       "description"="客户端类型"},
	 *     {"name"="device_name", "dataType"="string", "required"=false,
	 *       "description"="客户端名称"},
	 *     {"name"="uuid", "dataType"="string", "required"=false,
	 *       "description"="客户端唯一标识"},
	 *     {"name"="nonce", "dataType"="string", "required"=true},
	 *     {"name"="sig", "dataType"="string", "required"=true}
	 *   }
	 * )
	 */
	public function updateProfileAction(Request $request) {

		$this->verifySignature($request->request);
		$account = $this->requireEMAuth($request);

		if ($request->request->has('nickname')) {
			$nickname = $request->request->get('nickname');
//			if ($this->isValueValid($nickname, array(new NotSensitiveWord())) === false) {
//				return $this->errorsResponse(CommonError::ContainsSensitiveWords());
//			}
            if ($nickname) {
                $account->nickname = $nickname;
            }
		}

		if ($request->request->has('avatar_url')) {
			$avatarUrl = $request->request->get('avatar_url');
			if ($account->avatarUrl != $avatarUrl) {
				$account->avatarUrl = $avatarUrl;
			}
		}

		if ($request->request->has('gender')) {
			$genderString = $request->request->get('gender');
			$gender = $this->getGenderFromString($genderString);
			if ($gender === false) {
				return $this->errorsResponse(CommonError::ParameterInvalid('gender', $genderString));
			}
			if ($account->gender != $gender) {
				$account->gender = $gender;
			}
		}

		if ($request->request->has('signature')) {
			$signatureString = $request->request->get('signature');
			if ($this->isValueValid($signatureString, array(new NotSensitiveWord())) === false) {
				return $this->errorsResponse(CommonError::ContainsSensitiveWords());
			}
			$account->signature = $signatureString;
		}

		if ($request->request->has('age')) {
			$age = $request->request->getInt('age');
			$account->age = $age < 0 ? 0 : $age;
		}

		if ($request->request->has('birthday')) {
			$birthday = $request->request->get('birthday');
			$datetime = \DateTime::createFromFormat('Y-m-d h:i:s', $birthday.' 00:00:00');
			$dateErrors = \DateTime::getLastErrors();
			if ($dateErrors['warning_count'] == 0 && $dateErrors['error_count'] == 0) {
				$account->birthday = $datetime;
			}
		}

		if ($request->request->has('location')) {
			$location = $request->request->get('location');
			$account->location = $location;
		}

		$this->emAuthenticationService()->updateUserInfo($account);
		return $this->sucessResponse();
	}

	private function getGenderFromString($string) {
		switch (strtolower($string)) {
			case 'female':
				return EMUser::GENDER_FEMALE;
				break;
			case 'male':
				return EMUser::GENDER_MALE;
				break;
			case 'none':
				return null;
				break;
			default:
				return false;
		}
	}

	/**
	 * @return MemcacheInterface
	 */
	private function getMemcache() {
		return $this->container()->get('memcache.default');
	}
}