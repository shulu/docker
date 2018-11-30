<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Bundle\ApiBundle\Error\AccountError;
use Lychee\Bundle\ApiBundle\Error\CommonError;
use Lychee\Bundle\ApiBundle\Error\ErrorsException;
use Lychee\Bundle\ApiBundle\SmsRecoder\SmsRecorder;
use Lychee\Bundle\CoreBundle\Entity\User;
use Lychee\Bundle\CoreBundle\Validator\Constraints\Nickname;
use Lychee\Bundle\CoreBundle\Validator\Constraints\NotSensitiveWord;
use Lychee\Bundle\CoreBundle\Validator\Constraints\Password;
use Lychee\Bundle\ApiBundle\Error\AuthenticationError;
use Lychee\Constant;
use Lychee\Module\Authentication\AuthenticationService;
use Lychee\Module\Account\Exception\EmailAndNicknameDuplicateException;
use Lychee\Module\Account\Exception\EmailDuplicateException;
use Lychee\Module\Account\Exception\PhoneAndNicknameDuplicateException;
use Lychee\Module\Account\Exception\PhoneDuplicateException;
use Lychee\Module\Account\Exception\NicknameDuplicateException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Lychee\Module\Authentication\PhoneVerifier;
use Lychee\Bundle\ApiBundle\AntiSpam\SpamChecker;

/**
 * @Route("/auth")
 */
class AuthenticationController extends Controller {

    /**
     * @Route("/signup/mobile")
     * @Method("POST")
     * @ApiDoc(
     *   section="authentication",
     *   parameters={
     *     {"name"="area_code", "dataType"="string", "required"=true,
     *       "description"="区号，请以纯数字提交，且没有0前缀。例如中国，86"},
     *     {"name"="phone", "dataType"="string", "required"=true,
     *       "description"="电话号码，请以纯数字提交"},
     *     {"name"="password", "dataType"="string", "required"=true},
     *     {"name"="code", "dataType"="string", "required"=false}
     *   }
     * )
     */
    public function signupWithPhoneAction(Request $request) {
        if ($this->isDeviceBlocked($request)) {
            return $this->errorsResponse(CommonError::DeviceBlocked());
        }

        $areaCode = $this->requireParam($request->request, 'area_code');
        $phone = $this->requireParam($request->request, 'phone');
        $password = $this->requireParam($request->request, 'password');

        if (!$this->isPhoneValid($areaCode, $phone)) {
            return $this->errorsResponse(AuthenticationError::PhoneInvalid());
        }

        $code = $this->requireParam($request->request, 'code');
        if ($this->getPhoneVerifier()->verify($areaCode, $phone, $code) === false) {
            return $this->errorsResponse(AuthenticationError::PhoneVerifyFail());
        }

        if (!$this->isValueValid($password, new Password())) {
            return $this->errorsResponse(AuthenticationError::PasswordInvalid());
        }

        try {
            $user = $this->account()->createWithPhone($phone, $areaCode);
        } catch (PhoneDuplicateException $e) {
            return $this->errorsResponse(AuthenticationError::PhoneUsed());
        } catch (NicknameDuplicateException $e) {
            return $this->errorsResponse(AuthenticationError::NicknameUsed());
        }

        try {
            $this->authentication()->createPasswordForUser($user->id, $password);
        } catch (\Exception $e) {
//            $this->account()->removeUser($user);
            throw $e;
        }


        $this->afterSignup($request, $user);

        $this->updateUserDevice($request, $user->id);

        return $this->sucessResponse();
    }

    /**
     * @param $areaCode
     * @param $phone
     *
     * @return bool
     */
    private function isPhoneValid($areaCode, $phone) {
        if (ctype_digit($areaCode) === false || ctype_digit($phone) === false) {
            return false;
        }

        $areaCodeLength = strlen($areaCode);
        $phoneLength = strlen($phone);

        if ($areaCodeLength != 2 || $phoneLength != 11) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @Route("/signin/password")
     * @Method("POST")
     * @ApiDoc(
     *   section="authentication",
     *   description="name和email至少有一个非空，优先以email签入",
     *   parameters={
     *     {"name"="email", "dataType"="email", "required"=false},
     *     {"name"="name", "dataType"="string", "required"=false},
     *     {"name"="password", "dataType"="string", "required"=true}
     *   }
     * )
     */
    public function signinWithEmailAction(Request $request) {
        if ($this->isDeviceBlocked($request)) {
            return $this->errorsResponse(CommonError::DeviceBlocked());
        }

        $name = $request->request->get('name');
        $email = $request->request->get('email');
        $password = $request->request->get('password');

        if (empty($email) == false) {
            $user = $this->account()->fetchOneByEmail($email);
            if ($user == null) {
                return $this->errorsResponse(AuthenticationError::EmailNonexist());
            }
        } else if (empty($name) == false) {
            $user = $this->account()->fetchOneByNickname($name);
            if ($user == null) {
                return $this->errorsResponse(AuthenticationError::NicknameNonexist());
            }
        } else {
            return $this->errorsResponse(CommonError::ParameterMissing('email'));
        }

        if ($user->frozen) {
            return $this->errorsResponse(AccountError::UserFrozen());
        }

        $passwordValid = $this->authentication()->isUserPasswordValid($user->id, $password);
        if ($passwordValid == false) {
            return $this->errorsResponse(AuthenticationError::AccountOrPasswordError());
        } else {
            return $this->createAuthenticationResponse($user, AuthenticationService::GRANT_TYPE_EMAIL, false);
        }
    }

    /**
     * @Route("/signin/mobile")
     * @Method("POST")
     * @ApiDoc(
     *   section="authentication",
     *   parameters={
     *     {"name"="area_code", "dataType"="string", "required"=true,
     *       "description"="区号，请以纯数字提交，且没有0前缀。例如中国，86"},
     *     {"name"="phone", "dataType"="string", "required"=true},
     *     {"name"="password", "dataType"="string", "required"=true}
     *   }
     * )
     */
    public function signinWithPhoneAction(Request $request) {
        if ($this->isDeviceBlocked($request)) {
            return $this->errorsResponse(CommonError::DeviceBlocked());
        }

        $areaCode = $this->requireParam($request->request, 'area_code');
        $phone = $this->requireParam($request->request, 'phone');
        $password = $this->requireParam($request->request, 'password');

        $user = $this->account()->fetchOneByPhone($areaCode, $phone);
        if ($user == null) {
            return $this->errorsResponse(AuthenticationError::PhoneNonexist());
        }
        if ($user->frozen) {
            return $this->errorsResponse(AccountError::UserFrozen());
        }

        $passwordValid = $this->authentication()->isUserPasswordValid($user->id, $password);
        if ($passwordValid == false) {
            return $this->errorsResponse(AuthenticationError::AccountOrPasswordError());
        } else {
            $this->updateUserDevice($request, $user->id);
            return $this->createAuthenticationResponse($user, AuthenticationService::GRANT_TYPE_PHONE, false);
        }
    }

    /**
     * @Route("/signin/sina_weibo")
     * @Method("POST")
     * @ApiDoc(
     *   section="authentication",
     *   description="新浪微博登录",
     *   parameters={
     *     {"name"="weibo_token", "dataType"="string", "required"=true,
     *       "description"="微博的access_token"},
     *     {"name"="weibo_uid", "dataType"="string", "required"=true,
     *       "description"="微博的用户id"}
     *   }
     * )
     */
    public function signinWithWeibo(Request $request) {
        if ($this->isDeviceBlocked($request)) {
            return $this->errorsResponse(CommonError::DeviceBlocked());
        }

        $token = $this->requireParam($request->request, 'weibo_token');
        $weiboUid = $this->requireInt($request->request, 'weibo_uid');

        if ($this->authentication()->weiboTokenIsValid($token, $weiboUid) === false) {
            return $this->errorsResponse(AuthenticationError::WeiboTokenInvalid());
        }

        $isNewUser = false;
        $userId = $this->authentication()->getUserIdByWeiboUid($weiboUid);
        if ($userId === null) {
            $user = $this->account()->createWithPhone();
            $this->authentication()->registerWeiboUidWithUserId($weiboUid, $user->id);

            $this->afterSignup($request, $user);
            $isNewUser = true;
        } else {
            $user = $this->account()->fetchOne($userId);
            if ($user->frozen) {
                return $this->errorsResponse(AccountError::UserFrozen());
            }
        }

        $this->updateUserDevice($request, $user->id);
        return $this->createAuthenticationResponse($user, AuthenticationService::GRANT_TYPE_WEIBO, $isNewUser);
    }

    /**
     * @Route("/signin/qq")
     * @Method("POST")
     * @ApiDoc(
     *   section="authentication",
     *   description="QQ登录",
     *   parameters={
     *     {"name"="token", "dataType"="string", "required"=true,
     *       "description"="qq api的access_token"},
     *     {"name"="open_id", "dataType"="string", "required"=true,
     *       "description"="qq api的open id"}
     *   }
     * )
     */
    public function signinWithQQ(Request $request) {
        if ($this->isDeviceBlocked($request)) {
            return $this->errorsResponse(CommonError::DeviceBlocked());
        }

        $token = $this->requireParam($request->request, 'token');
        $openId = $this->requireParam($request->request, 'open_id');
        $deviceName = $request->request->get('device_name');

        if(!isset($deviceName)){
        	$deviceName = 'unknown';
        }

        if (preg_match('/^[0-9A-F]{32}$/i', $openId) == false) {
            return $this->errorsResponse(AuthenticationError::QQOpenIdInvalid());
        }
        if ($this->authentication()->qqOpenIdIsValid($token, $openId, $deviceName) === false) {
            return $this->errorsResponse(AuthenticationError::QQTokenInvalid());
        }

        $userId = $this->authentication()->getUserIdByQQOpenId($openId);

        $isNewUser = false;
        if ($userId === null) {

//	        $ip = $request->getClientIp();
//	        $ipBlocker = $this->get('lychee_api.ip_blocker');
//	        if ($ipBlocker->checkAndUpdate($ip, SpamChecker::ACTION_QQ_SIGNIN, null, 10, 5) == false) {
//		        throw new ErrorsException(CommonError::SystemBusy());
//	        }

            $user = $this->account()->createWithPhone();
            $this->authentication()->registerQQOpenIdWithUserId($openId, $user->id);

            $this->afterSignup($request, $user);
            $isNewUser = true;
        } else {
            $user = $this->account()->fetchOne($userId);
            if ($user->frozen) {
                return $this->errorsResponse(AccountError::UserFrozen());
            }
        }

        $this->updateUserDevice($request, $user->id);
        return $this->createAuthenticationResponse($user, AuthenticationService::GRANT_TYPE_QQ, $isNewUser);
    }

    /**
     * @Route("/signin/wechat")
     * @Method("POST")
     * @ApiDoc(
     *   section="authentication",
     *   description="微信登录",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true,
     *       "description"="微信的access_token"},
     *     {"name"="open_id", "dataType"="string", "required"=true,
     *       "description"="微信的open id"}
     *   }
     * )
     */
    public function signinWithWechat(Request $request) {
        if ($this->isDeviceBlocked($request)) {
            return $this->errorsResponse(CommonError::DeviceBlocked());
        }

        $token = $this->requireParam($request->request, 'access_token');
        $openId = $this->requireParam($request->request, 'open_id');

        if ($this->authentication()->wechatTokenIsValid($token, $openId) === false) {
            $this->getLogger()->error('wechat auth fail.', array(
                'client_version' => $request->get(self::CLIENT_APP_VERSION_KEY),
                'client_platform' => $request->get(self::CLIENT_PLATFORM_KEY),
                'client_os_version' => $request->get(self::CLIENT_OS_VERSION_KEY),
            ));
            return $this->errorsResponse(AuthenticationError::WechatTokenInvalid());
        }

        $isNewUser = false;
        $userId = $this->authentication()->getUserIdByWechatOpenId($openId);
        if ($userId === null) {
            $user = $this->account()->createWithPhone();
            $this->authentication()->registerWechatOpenIdWithUserId($openId, $user->id);

            $this->afterSignup($request, $user);
            $isNewUser = true;
        } else {
            $user = $this->account()->fetchOne($userId);
            if ($user->frozen) {
                return $this->errorsResponse(AccountError::UserFrozen());
            }
        }

        $this->updateUserDevice($request, $user->id);
        return $this->createAuthenticationResponse($user, AuthenticationService::GRANT_TYPE_WECHAT, $isNewUser);
    }

    /**
     * @param User $user
     * @param string $grantType
     * @param bool $isNewUser
     * @return JsonResponse
     */
    private function createAuthenticationResponse($user, $grantType, $isNewUser) {
        $token = $this->authentication()->createTokenForUser($user->id, $grantType);

        if ($token) {
            // 新业务接该事件
            $event = [];
            $event['userId'] = $user->id;
            $event['time'] = time();
            $this->get('lychee.dynamic_dispatcher_async')->dispatch('user.login', $event);
        }

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildUserSynthesizer(array($user), $user->id);
        return $this->dataResponse(array(
            'access_token' => $token->accessToken,
            'expires_in' => $token->ttl,
            'expires_at' => time() + $token->ttl - 1,
            'account' => $synthesizer->synthesizeOne($user->id),
            'is_new' => $isNewUser
        ));
    }

    /**
     *
     *
     * @Route("/signout")
     * @Method("POST")
     * @ApiDoc(
     *   section="authentication",
     *   description="",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *   }
     * )
     */
    public function signoutAction(Request $request) {
        $accessToken = $request->request->get('access_token');
        $this->authentication()->revokeToken($accessToken);
        return $this->sucessResponse();
    }

    /**
     * @Route("/send_sms_code")
     * @Method("POST")
     * @ApiDoc(
     *   section="authentication",
     *   description="",
     *   parameters={
     *     {"name"="area_code", "dataType"="string", "required"=true,
     *       "description"="区号，请以纯数字提交，且没有0前缀。例如中国，86"},
     *     {"name"="phone", "dataType"="string", "required"=true},
     *     {"name"="verify_used", "dataType"="number", "required"=false, "description"="1：手机号已存在即抛异常，0：不判断手机号是否已存在"},
     *     {"name"="nonce", "dataType"="string", "required"=true},
     *     {"name"="sig", "dataType"="string", "required"=true, "description"="数字签名"}
     *   }
     * )
	 * @param Request $request
	 *
	 * @return JsonResponse|Response
	 */
    public function sendCodeViaSMS(Request $request) {
//    	$signature = $request->request->get('sig');
//	    if ($signature) {
		    $this->verifySignature($request->request);
//	    }
        $areaCode = $this->requireParam($request->request, 'area_code');
        $phone = $this->requireParam($request->request, 'phone');

        if (!$this->isPhoneValid($areaCode, $phone)) {
            return $this->errorsResponse(CommonError::ParameterInvalid('area_code', $areaCode));
        }

        $ip = $request->getClientIp();

        /** @var SmsRecorder $recorder */
        $recorder = $this->get('lychee_api.sms_recorder');
        $platform = $request->request->get(self::CLIENT_PLATFORM_KEY);
        $osVersion = $request->request->get(self::CLIENT_OS_VERSION_KEY);
        $appVersion = $request->request->get(self::CLIENT_APP_VERSION_KEY);
        $deviceId = $request->request->get(self::CLIENT_DEVICE_ID_KEY);

        if (version_compare($appVersion, '1.9', '=') == true && $platform == 'ios') {
            //ios 1.9版本有bug!,会不断地发短信
            return $this->errorsResponse(CommonError::TooManyRequest());
        }

        // 60s内不允许同一个手机号重复发短信验证
        if (empty($recorder->tryRecord($phone, $ip))) {
            return $this->errorsResponse(CommonError::TooManyRequest());
        }

        $recorder->record($ip, $areaCode, $phone, $platform, $osVersion, $appVersion, $deviceId);
        
        if ($ip == null) {
            return $this->errorsResponse(CommonError::TooManyRequest());
        }
//        $ipBlocker = $this->get('lychee_api.ip_blocker');
//        if ($ipBlocker->checkAndUpdate($ip, 'sms') == false) {
//            return $this->errorsResponse(CommonError::TooManyRequest());
//        }

        $verifyUsed = $request->request->getInt('verify_used', 0);
        if ($verifyUsed > 0) {
            $user = $this->account()->fetchOneByPhone($areaCode, $phone);
            if ($user) {
                return $this->errorsResponse(AuthenticationError::PhoneUsed());
            }
        }

        $this->getPhoneVerifier()->sendCode($areaCode, $phone);
        return $this->sucessResponse();
    }

    /**
     * @return PhoneVerifier
     */
    private function getPhoneVerifier() {
        return $this->get('lychee.module.authentication.phone_verifier');
    }

    private function signatureVerifier(ParameterBag $parameterBag) {
    	$params = $parameterBag->all();
	    $signature = $params['signature'];
	    unset($params['signature']);
	    ksort($params);
	    $paramsArr = '';
	    foreach ($params as $key => $value) {
	    	$paramsArr[] = $key . '=' . $value;
	    }
	    $paramsStr = implode('&', $paramsArr);
	    return strcmp(sha1($paramsStr . $this->getParameter('sms_signature_secret')), $signature);
    }


    /**
     *
     * ###返回内容
     *
     * ```json
     *
     * {
     * "access_token": "82374b6aa1e3bbfae8c045c9ab12b0d8247afbbb",
     * "expires_in": 2592000,
     * "expires_at": 1534496800,
     * "account": {
     * "id": "33227",
     * "nickname": "欠揍のJK9966730219",
     * "avatar_url": null,
     * "gender": null,
     * "level": 1,
     * "signature": null,
     * "ciyoCoin": 0,
     * "my_follower": false,
     * "my_followee": true,
     * "followers_count": 0,
     * "followees_count": 0,
     * "following_topics_count": 0,
     * "post_count": 0,
     * "image_comment_count": 0,
     * "favourites_count": 0
     * },
     * "is_new": true
     * }
     *
     * ```
     *
     * [错误码说明](http://gitlab.ciyo.cn/ciyocon/lychee-server/wikis/服务/接口#接口公共错误码)
     *
     * @Route("/signin/captcha")
     * @Method("POST")
     * @ApiDoc(
     *   section="authentication",
     *   parameters={
     *     {"name"="area_code", "dataType"="string", "required"=true,
     *       "description"="区号，请以纯数字提交，且没有0前缀。例如中国，86"},
     *     {"name"="phone", "dataType"="string", "required"=true, "description"="电话号码，请以纯数字提交"},
     *     {"name"="code", "dataType"="string", "required"=true, "description"="验证码"}
     *   }
     * )
     */
    public function signinWithCaptchaAction(Request $request) {
        if ($this->isDeviceBlocked($request)) {
            return $this->errorsResponse(CommonError::DeviceBlocked());
        }

        $areaCode = $this->requireParam($request->request, 'area_code');
        $phone = $this->requireParam($request->request, 'phone');
        $code = $this->requireParam($request->request, 'code');

        if ($this->getPhoneVerifier()->verify($areaCode, $phone, $code) === false) {
            return $this->errorsResponse(AuthenticationError::PhoneVerifyFail());
        }

        $user = $this->account()->fetchOneByPhone($areaCode, $phone);
        if ($user == null) {
            return $this->errorsResponse(AuthenticationError::PhoneNonexist());
        }
        if ($user->frozen) {
            return $this->errorsResponse(AccountError::UserFrozen());
        }

        $this->updateUserDevice($request, $user->id);
        return $this->createAuthenticationResponse($user, AuthenticationService::GRANT_TYPE_PHONE, false);
    }


    /**
     *
     * ###返回内容
     *
     * ```json
     *
     * {
     * "access_token": "82374b6aa1e3bbfae8c045c9ab12b0d8247afbbb",
     * "expires_in": 2592000,
     * "expires_at": 1534496800,
     * "account": {
     * "id": "33227",
     * "nickname": "欠揍のJK9966730219",
     * "avatar_url": null,
     * "gender": null,
     * "level": 1,
     * "signature": null,
     * "ciyoCoin": 0,
     * "my_follower": false,
     * "my_followee": true,
     * "followers_count": 0,
     * "followees_count": 0,
     * "following_topics_count": 0,
     * "post_count": 0,
     * "image_comment_count": 0,
     * "favourites_count": 0
     * },
     * "is_new": true
     * }
     *
     * ```
     *
     * [错误码说明](http://gitlab.ciyo.cn/ciyocon/lychee-server/wikis/服务/接口#接口公共错误码)
     *
     * @Route("/signup/captcha")
     * @Method("POST")
     * @ApiDoc(
     *   section="authentication",
     *   parameters={
     *     {"name"="area_code", "dataType"="string", "required"=true,
     *       "description"="区号，请以纯数字提交，且没有0前缀。例如中国，86"},
     *     {"name"="phone", "dataType"="string", "required"=true,
     *       "description"="电话号码，请以纯数字提交"},
     *     {"name"="code", "dataType"="string", "required"=false, "description"="验证码"}
     *   }
     * )
     */
    public function signupWithCaptchaAction(Request $request) {
        if ($this->isDeviceBlocked($request)) {
            return $this->errorsResponse(CommonError::DeviceBlocked());
        }

        $areaCode = $this->requireParam($request->request, 'area_code');
        $phone = $this->requireParam($request->request, 'phone');
        if (!$this->isPhoneValid($areaCode, $phone)) {
            return $this->errorsResponse(AuthenticationError::PhoneInvalid());
        }

        $code = $this->requireParam($request->request, 'code');
        if ($this->getPhoneVerifier()->verify($areaCode, $phone, $code) === false) {
            return $this->errorsResponse(AuthenticationError::PhoneVerifyFail());
        }

        try {
            $user = $this->account()->createWithPhone($phone, $areaCode);
        } catch (PhoneDuplicateException $e) {
            return $this->errorsResponse(AuthenticationError::PhoneUsed());
        } catch (NicknameDuplicateException $e) {
            return $this->errorsResponse(AuthenticationError::NicknameUsed());
        }

        $this->afterSignup($request, $user);

        $this->updateUserDevice($request, $user->id);
        return $this->createAuthenticationResponse($user, AuthenticationService::GRANT_TYPE_PHONE, true);
    }

    private function afterSignup(Request $request, $user)
    {
        if (empty($user)) {
            return false;
        }
        // 新业务接该事件
        $event = [];
        $event['userId'] = $user->id;
        $event['time'] = time();
        $this->get('lychee.dynamic_dispatcher_async')->dispatch('user.register', $event);
    }


    /**
     *
     * ###返回内容
     *
     * ```json
     *
     * {
     * "result": "123456"
     * }
     *
     * ```
     *
     * [错误码说明](http://gitlab.ciyo.cn/ciyocon/lychee-server/wikis/服务/接口#接口公共错误码)
     *
     * @Route("/get_sms_code")
     * @Method("GET")
     * @ApiDoc(
     *   section="authentication",
     *   parameters={
     *     {"name"="area_code", "dataType"="string", "required"=true,
     *       "description"="区号，请以纯数字提交，且没有0前缀。例如中国，86"},
     *     {"name"="phone", "dataType"="string", "required"=true,
     *       "description"="电话号码，请以纯数字提交"}
     *   }
     * )
     */
    public function getSmsCode(Request $request) {

        $env = $this->get('kernel')->getEnvironment();
        if ('dev'!=$env) {
            return $this->errorsResponse(CommonError::PermissionDenied());
        }

        $areaCode = $this->requireParam($request->query, 'area_code');
        $phone = $this->requireParam($request->query, 'phone');

        $code = $this->getPhoneVerifier()->getCode($areaCode, $phone);
        return $this->dataResponse(['result'=>$code]);
    }

} 