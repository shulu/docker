<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Bundle\ApiBundle\Error\AccountError;
use Lychee\Bundle\ApiBundle\Error\AuthenticationError;
use Lychee\Bundle\ApiBundle\Error\CommonError;
use Lychee\Bundle\ApiBundle\Error\ErrorsException;
use Lychee\Bundle\CoreBundle\Controller\Controller as BaseController;
use Lychee\Constant;
use Lychee\Module\ContentManagement\ContentBlockingService;
use Lychee\Module\Topic\Entity\Topic;
use Lychee\Bundle\CoreBundle\Entity\User;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\HttpFoundation\ParameterBag;
use Lychee\Bundle\ApiBundle\DataSynthesizer\SynthesizerBuilder;
use Psr\Log\LoggerInterface;
use Lychee\Bundle\ApiBundle\Error\Error;
use Lychee\Module\Measurement\ActiveUser\ActiveUserRecorder;
use Lychee\Module\Account\Mission\MissionResult;
use Lychee\Module\Account\DeviceBlocker;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Bundle\ApiBundle\AntiSpam\SpamChecker;
use Lychee\Module\ExtraMessage\Entity\EMUser;

use think\Request;
use think\Response;

class Controller extends BaseController {

    const CLIENT_OS_VERSION_KEY = 'app_os_version';
    const CLIENT_APP_VERSION_KEY = 'app_ver';
    const CLIENT_CHANNEL_KEY = 'channel';
    const CLIENT_DEVICE_ID_KEY = 'uuid';
    const CLIENT_PLATFORM_KEY = 'client';
    protected static $jsonpcallbackfunction = '';

    /**
     * @param Request $request
     *
     * @return User|null
     * @throws ErrorsException
     */
    public function getAuthUser($request) {
        $token = $request->get('access_token');
        if ($token === null) {
            return null;
        } else {
            $userId = $this->authentication()->getUserIdByToken($token);
            if ($userId == null) {
                throw new ErrorsException(array(AuthenticationError::BadAuthentication()));
            }
            $user = $this->account()->fetchOne($userId);
            if ($user == null) {
                throw new ErrorsException(array(AuthenticationError::BadAuthentication()));
            }
            if ($user->frozen) {
                throw new ErrorsException(AccountError::UserFrozen());
            }
            $this->getActiveUserRecorder()->record($user->id);

            return $user;
        }
    }

    /**
     * @param Request $request
     *
     * @return User
     * @throws ErrorsException
     */
    public function requirePhoneAuth($request, $isEnable=false) {
        $appVersion = $request->get(self::CLIENT_APP_VERSION_KEY);
        $user = $this->requireAuth($request);
        if (empty($isEnable)
            && $appVersion
            && version_compare($appVersion, '4.0.10', '<')) {
            return $user;
        }
        // vip 不受限
        if($this->account()->isUserInVip($user->id)){
            return $user;
        }
        if (empty($user->phone)) {
            throw new ErrorsException(array(AuthenticationError::RequirePhone()));
        }
        return $user;
    }

    /**
     * @param Request $request
     *
     * @return User
     * @throws ErrorsException
     */
    public function requireAuth($request) {
        $token = $request->get('access_token');
        if ($token === null) {
            throw new ErrorsException(array(AuthenticationError::BadAuthentication()));
        } else {
            $userId = $this->authentication()->getUserIdByToken($token);
        }

        if ($userId == null) {
            throw new ErrorsException(array(AuthenticationError::BadAuthentication()));
        }
        $user = $this->account()->fetchOne($userId);
        if ($user == null) {
            throw new ErrorsException(array(AuthenticationError::BadAuthentication()));
        }
        if ($user->frozen) {
            throw new ErrorsException(AccountError::UserFrozen());
        }
        $this->getActiveUserRecorder()->record($user->id);

        return $user;
    }

	/**
	 * @param ParameterBag $params
	 * @return EMUser
	 * @throws ErrorsException
	 */
	public function requireEMAuth($request) {

		$token = $request->get('access_token');
		if ($token === null) {
			if ($this->container()->getParameter('kernel.environment') != 'dev') {
				throw new ErrorsException(array(AuthenticationError::BadAuthentication()));
			}
			//测试环境中,默认使用的账号.
			if (intval($request->get('account_id')) > 0) {
				$userId = intval($request->get('_account_id'));
			} else {
				$userId = 35;
			}

		} else {
			$userId = $this->emAuthenticationService()->getUserIdByToken($token);
		}

		if ($userId == null) {
			throw new ErrorsException(array(AuthenticationError::BadAuthentication()));
		}

		$user = $this->emAuthenticationService()->fetchAccount($userId);
		if ($user == null) {
			throw new ErrorsException(array(AuthenticationError::BadAuthentication()));
		}

		return $user;
	}

    public function getEMUser($request) {

        $token = $request->get('access_token');
        if ($token === null) {
            if ($this->container()->getParameter('kernel.environment') != 'dev') {
                return null;
            }
            //测试环境中,默认使用的账号.
            if (intval($request->get('account_id')) > 0) {
                $userId = intval($request->get('_account_id'));
            } else {
                $userId = 35;
            }

        } else {
            $userId = $this->emAuthenticationService()->getUserIdByToken($token);
        }

        if ($userId == null) {
            return null;
        }

        $user = $this->emAuthenticationService()->fetchAccount($userId);
        if ($user == null) {
            return null;
        }

        return $user;
    }

    /**
     * @return ActiveUserRecorder
     */
    private function getActiveUserRecorder() {
        return $this->get('lychee.module.measurement.active_user_recorder');
    }

    /**
     * @param Request|ParameterBag $parameters
     * @param string $paramName
     *
     * @return string
     * @throws ErrorsException
     */
    public function requireParam($parameters, $paramName) {
        $value = $parameters->get($paramName, null);
        if ($value === null) {
            throw new ErrorsException(array(CommonError::ParameterMissing($paramName)));
        }
        return $value;
    }

    /**
     * @param Request|ParameterBag $parameters
     * @param string $paramName
     *
     * @return int
     * @throws ErrorsException
     */
    public function requireInt($parameters, $paramName) {
        return intval($this->requireParam($parameters, $paramName));
    }

    /**
     * @param Request|ParameterBag $parameters
     * @param string $paramName
     *
     * @return int
     * @throws ErrorsException
     */
    public function requireId($parameters, $paramName) {
        $id = intval($this->requireParam($parameters, $paramName));
        if ($id == 0) {
            throw new ErrorsException(array(CommonError::ParameterInvalid($paramName, $id)));
        }
        return $id;
    }

    /**
     * @param ParameterBag $paramers
     * @param string $name
     * @param int $limit
     * @param bool $required
     * @return array
     * @throws ErrorsException
     */
    public function getRequestIds($paramers, $name, $limit, $required = true) {
        $idParam = $paramers->get($name, null);
        if ($idParam === null) {
            if ($required) {
                throw new ErrorsException(array(CommonError::ParameterMissing($name)));
            } else {
                return array();
            }
        }
        $idStrings = explode(',', $idParam);

        $ids = array();
        foreach($idStrings as $idString) {
            $id = intval($idString);
            if ($id === 0) {
                throw new ErrorsException(array(CommonError::ParameterInvalid($name, $idParam)));
            } else {
                $ids[] = $id;
            }
        }
        if (count($ids) > $limit) {
            throw new ErrorsException(array(CommonError::ParameterInvalid($name, $idParam)));
        }

        return $ids;
    }

    /**
     * @param Request|ParameterBag $parameters
     * @param string $cursorName
     * @param string $countName
     * @param int    $defaultCount
     * @param int    $maxCount
     *
     * @return array
     * @throws ErrorsException
     */
    public function getCursorAndCount($parameters,
        $defaultCount = 20, $maxCount = 100,
        $cursorName = 'cursor', $countName = 'count'
    ) {
        $cursor = $parameters->getInt($cursorName);
        $count = $parameters->getInt($countName, $defaultCount);
        if ($count < 1 || $maxCount < $count) {
            throw new ErrorsException(array(CommonError::ParameterInvalid($countName, $count)));
        }
        return array($cursor, $count);
    }

    /**
     * @param Request|ParameterBag $parameters
     * @param string $cursorName
     * @param string $countName
     * @param int    $defaultCount
     * @param int    $maxCount
     *
     * @return array
     * @throws ErrorsException
     */
    public function getStringCursorAndCount($parameters,
        $defaultCount = 20, $maxCount = 100,
        $cursorName = 'cursor', $countName = 'count'
    ) {
        $cursor = $parameters->get($cursorName);
        $count = $parameters->getInt($countName, $defaultCount);
        if ($count < 1 || $maxCount < $count) {
            throw new ErrorsException(array(CommonError::ParameterInvalid($countName, $count)));
        }
        return array($cursor, $count);
    }

    /**
     * @param Request|ParameterBag $parameters
     * @param string $cursorName
     * @param string $countName
     * @param int    $defaultCount
     * @param int    $maxCount
     *
     * @return array
     * @throws ErrorsException
     */
    public function getArrayCursorAndCount($parameters,
                                            $defaultCount = 20, $maxCount = 100,
                                            $cursorName = 'cursor', $countName = 'count'
    ) {
        $cursorStrs = explode(',', $parameters->get($cursorName));
        $cursor = array();
        foreach ($cursorStrs as $str) {
            $cursor[] = intval($str);
        }
        $count = $parameters->getInt($countName, $defaultCount);
        if ($count < 1 || $maxCount < $count) {
            throw new ErrorsException(array(CommonError::ParameterInvalid($countName, $count)));
        }
        return array($cursor, $count);
    }

    /**
     * @param mixed $value
     * @param Constraint|array $constraints
     * @return bool
     */
    public function isValueValid($value, $constraints) {
        $errors = $this->get('validator')->validate($value, $constraints);
        if (count($errors) > 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @return SynthesizerBuilder
     */
    protected function getSynthesizerBuilder() {
        return $this->container->get('lychee_api.synthesizer_builder');
    }

    /**
     * @return SpamChecker
     */
    protected function getSpamChecker() {
        return $this->container->get('lychee_api.spam_checker');
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger() {
        return $this->get('logger');
    }

    public function dumpResponse() {
        ob_start();
        $args = func_get_args();
        foreach ($args as $arg) {
            var_dump($arg);
        }
        $output = ob_get_clean();
        return new Response($output);
    }

    public function sucessResponse() {
        self::checkCallbackFunction();
        $jsonpResponse = new JsonResponse(array('result' => true));
        if(self::$jsonpcallbackfunction){
            $jsonpResponse->setCallback(self::$jsonpcallbackfunction);
        }
        return $jsonpResponse;
    }

    public function failureResponse() {
        self::checkCallbackFunction();
        $jsonpResponse = new JsonResponse(array('result' => false));
        if(self::$jsonpcallbackfunction){
            $jsonpResponse->setCallback(self::$jsonpcallbackfunction);
        }
        return $jsonpResponse;
    }

    public function errorsResponse($errors) {
        return self::buildErrorsResponse($errors);
    }

    /**
     * @param $errors
     *
     * @return Response
     */
    public static function buildErrorsResponse($errors) {
        if (is_array($errors) == false) {
            $errors = array($errors);
        }

        $result = array();
        foreach ($errors as $error) {
            /** @var Error $error */
            $errorInfo = array('code' => $error->getCode(), 'message' => $error->getMessage());
            if ($error->getExtra() !== null) {
                $errorInfo['extra'] = $error->getExtra();
            }
            if ($error->getDisplayMessage() !== null) {
                $errorInfo['display_message'] = $error->getDisplayMessage();
            }
            $result[] = $errorInfo;
        }
        self::checkCallbackFunction();
        $jsonpResponse = new JsonResponse(array('errors' => $result));
        if(self::$jsonpcallbackfunction){
            $jsonpResponse->setCallback(self::$jsonpcallbackfunction);
        }
        return $jsonpResponse;
    }

    public function dataResponse($data , $isSetPublic = false ,  $ttlSeconds = null) {
        self::checkCallbackFunction();
        $jsonpResponse = new JsonResponse($data);
        if(self::$jsonpcallbackfunction){
            $jsonpResponse->setCallback(self::$jsonpcallbackfunction);
        }
        if($isSetPublic){
            $jsonpResponse->setPublic();
        }
        if( ! is_null($ttlSeconds)){
            $jsonpResponse->setTtl($ttlSeconds);
        }

        return $jsonpResponse;
    }

    /**
     * @param string $name
     * @param array $list
     * @param int $nextCursor
     *
     * @return JsonResponse
     */
    public function arrayResponse($name, $list, $nextCursor) {
        if (is_array($nextCursor)) {
            $allZero = true;
            foreach ($nextCursor as $c) {
                if ($c != 0) {
                    $allZero = false;
                    break;
                }
            }
            if ($allZero) {
                $nextCursorStr = '0';
            } else {
                $nextCursorStr = implode(',', $nextCursor);
            }
        } else {
            $nextCursorStr = strval($nextCursor);
        }
        self::checkCallbackFunction();
        $jsonpResponse = new JsonResponse(array($name => $list, 'next_cursor' => $nextCursorStr));
        if(self::$jsonpcallbackfunction){
            $jsonpResponse->setCallback(self::$jsonpcallbackfunction);
        }
        return $jsonpResponse;
    }

    /**
     * @param array $data
     * @param MissionResult $missionResult
     */
    public function injectMissionResult(&$data = array(), $missionResult = null) {
        if ($missionResult && $missionResult->getExperience()) {
            $data['gain_experience'] = $missionResult->getExperience();
            if ($missionResult->isLevelUp()) {
                $data['upgrade_level'] = $missionResult->getLevel();
            }
        }
    }

    /**
     * @param Request $request
     * @param int[]|Topic[] $topics
     *
     * @return int[]
     */
    protected function filterBlockingTopics($request, $topics) {
        $channel = $request->get(self::CLIENT_CHANNEL_KEY);
        $version = $request->get(self::CLIENT_APP_VERSION_KEY);

        if (!$channel || !$version) {
            return $topics;
        }

        /** @var ContentBlockingService $contentBlocking */
        $contentBlocking = $this->get('lychee.module.content_management.content_blocking');
        $blockingTopicIds = $contentBlocking->getBlockingTopicIdsByChannel($channel, $version);
        if (count($blockingTopicIds) == 0) {
            return $topics;
        } else {
            if (is_object(current($topics))) {
                return array_filter($topics, function($topic) use ($blockingTopicIds) {
                    return !in_array($topic->id, $blockingTopicIds);
                });
            } else {
                //id list
                return ArrayUtility::diffValue($topics, $blockingTopicIds);
            }
        }
    }

    /**
     * @param Request $request
     * @param int $topicId
     *
     * @return bool
     */
    protected function isTopicBlocking($request, $topicId) {
        $channel = $request->get(self::CLIENT_CHANNEL_KEY);
        $version = $request->get(self::CLIENT_APP_VERSION_KEY);

        if (!$channel || !$version) {
            return false;
        }

        /** @var ContentBlockingService $contentBlocking */
        $contentBlocking = $this->get('lychee.module.content_management.content_blocking');
        $blockingTopicIds = $contentBlocking->getBlockingTopicIdsByChannel($channel, $version);
        if (count($blockingTopicIds) == 0) {
            return false;
        } else {
            return in_array($topicId, $blockingTopicIds);
        }
    }

    /**
     * @param Request $request
     * @param int $userId
     */
    protected function updateUserDevice($request, $userId) {
        $platform = $request->get(self::CLIENT_PLATFORM_KEY);
        $deviceId = $request->get(self::CLIENT_DEVICE_ID_KEY);

        if ($platform && $deviceId) {
            /** @var DeviceBlocker $deviceBlocker */
            $deviceBlocker = $this->get('lychee.module.account.device_blocker');
            $deviceBlocker->updateUserDeviceId($userId, $platform, $deviceId);
        }
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    protected function isDeviceBlocked($request) {
        $platform = $request->get(self::CLIENT_PLATFORM_KEY);
        $deviceId = $request->get(self::CLIENT_DEVICE_ID_KEY);

        if ($platform && $deviceId) {
            /** @var DeviceBlocker $deviceBlocker */
            $deviceBlocker = $this->get('lychee.module.account.device_blocker');
            return $deviceBlocker->isDeviceBlocked($platform, $deviceId);
        } else {
            return false;
        }
    }

	/**
	 * @param array $parameterBag
	 * @param $secretKey
	 * @param string $signatureKey
	 * @param string $algo
	 * @param string $nonceKey
	 *
	 * @return bool
	 * @throws ErrorsException
	 */
	public function verifyBilibiliSignature(
		array $parameterBag,
		$signatureKey = 'sign',
		$algo = 'md5'
	) {

		$secretKey = $this->getParameter('bilibili_secrect_key');
		$signature = $parameterBag[$signatureKey];

		$params = $parameterBag;
		unset($params[$signatureKey]);

		if (0 === strcmp($signature, $this->requestBilibiliSignature($params, $secretKey, $algo))) {
			return true;
		} else {
			throw new ErrorsException(CommonError::SignatureInvalid());
		}
	}

	/**
	 * @param array $param
	 *
	 * @return array
	 */
	public function buildAndSignBilibiliParams($param){

		$gameId = $this->getParameter('bilibili_game_id');
		$merchant_id = $this->getParameter('bilibili_merchant_id');
		$secretKey = $this->getParameter('bilibili_secrect_key');
		$apiVersion = 1;
		$timestamp = time() * 1000;

		$param['game_id'] = $gameId;
		$param['merchant_id'] = $merchant_id;
		$param['version'] = $apiVersion;
		$param['timestamp'] = $timestamp;

		$sign = $this->requestBilibiliSignature($param, $secretKey);
		$param['sign'] = $sign;

		return $param;

	}

	/**
	 * @param $params
	 * @param $secret
	 * @param string $algo
	 *
	 * @return string
	 */
	protected function requestBilibiliSignature($params, $secret, $algo = 'md5') {
		ksort($params);
		$paramsArr = [];
		foreach ($params as $key => $p) {
			if ($p || $p == '0') {

				if($key == '_format'){
					continue;
				}

				$paramsArr[] = $p;
			}
		}
		$paramsArr[] = $secret;

		return strtolower(hash($algo, implode('', $paramsArr)));
	}

	/**
	 * @param ParameterBag $parameterBag
	 * @param $secretKey
	 * @param string $signatureKey
	 * @param string $algo
	 * @param string $nonceKey
	 *
	 * @return bool
	 * @throws ErrorsException
	 */
    protected function verifySignature(
    	ParameterBag $parameterBag,
	    $secretKey = null,
	    $signatureKey = 'sig',
	    $algo = 'md5',
		$nonceKey = 'nonce'
    ) {
    	if (!$secretKey) {
    		$secretKey = $this->getParameter('request_signature_key');
	    }
    	$signature = $parameterBag->get($signatureKey);
	    /**
	     * @todo 临时兼容数字签名参数名的冲突，待客户端新版本都发布后需要将这个判断删除，统一用sig。
	     */
    	if (!$signature) {
    		$signature = $parameterBag->get('signature');
	    }
    	$nonce = $parameterBag->get($nonceKey);
    	if (!$nonce) {
    		throw new ErrorsException(CommonError::ParameterMissing($nonceKey));
	    }
    	$params = $parameterBag->all();
    	unset($params[$signatureKey]);

	    if (0 === strcmp($signature, $this->requestSignature($params, $secretKey, $algo))) {
	    	return true;
	    } else {
	    	throw new ErrorsException(CommonError::SignatureInvalid());
	    }
    }

	/**
	 * @param $params
	 * @param $secret
	 * @param string $algo
	 *
	 * @return string
	 */
	protected function requestSignature($params, $secret, $algo = 'md5') {
	    ksort($params);
	    $paramsArr = [];
	    foreach ($params as $key => $p) {
	    	if ($p || $p == '0') {

	    		if($key == '_format'){
	    			continue;
			    }

			    $paramsArr[] = "$key=$p";
		    }
	    }
	    $paramsArr[] = "key=$secret";

	    return strtoupper(hash($algo, implode('&', $paramsArr)));
    }

	/**
	 * 生成随机字符串
	 * @return string
	 */
    protected function genNonceStr() {
    	return substr(base64_encode(md5(uniqid(mt_rand(100000, 999999)))), 0, 32);
    }



	/**
	 * @param array $parameterBag
	 * @param $secretKey
	 * @param string $signatureKey
	 * @param string $algo
	 * @param string $nonceKey
	 *
	 * @return bool
	 * @throws ErrorsException
	 */
	public function verifyDmzjSignature( array $parameterBag, $signatureKey = 'sign', $algo = 'md5') {

		$secretKey = $this->getParameter('dmzj_merchant_key');
		$signature = $parameterBag[$signatureKey];

		$params = $parameterBag;
		unset($params[$signatureKey]);


		if (0 === strcmp($signature, $this->requestDmzjSignature($params, $secretKey, $algo))) {
			return true;
		} else {
			throw new ErrorsException(CommonError::SignatureInvalid());
		}
	}


	/**
	 * @param $params
	 * @param $secret
	 * @param string $algo
	 *
	 * @return string
	 */
	protected function requestDmzjSignature($params, $secret, $algo = 'md5') {

		$params = array_filter($params, function($val){
			return (!is_null($val)) && ($val !== '');
		});

		$params['merc_key'] = $secret;
		ksort($params);

		$params_str = '';
		foreach ($params as $key => $value) {
			$params_str .= $key . '=' . $value . '&';
		}

		if (strlen($params_str)) {
			$length = strlen($params_str) - 1;
			$params_str = substr($params_str, 0, $length);
		}

		$sign = strtolower(hash($algo, $params_str));

		return $sign;
	}

    /**
     * checkCallbackFunction
     *
     * @param Request $request
     */
	protected static function checkCallbackFunction(){
        $jsonpcallback = isset($_GET['callback'])?trim($_GET['callback']):'';
        if ($jsonpcallback){
            self::$jsonpcallbackfunction = $jsonpcallback;
        }
    }
}