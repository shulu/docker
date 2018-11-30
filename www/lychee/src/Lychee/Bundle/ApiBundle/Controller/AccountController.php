<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Bundle\ApiBundle\Error\AccountError;
use Lychee\Bundle\ApiBundle\Error\AuthenticationError;
use Lychee\Bundle\ApiBundle\Error\CommonError;
use Lychee\Bundle\ApiBundle\Error\ErrorsException;
use Lychee\Bundle\CoreBundle\Entity\User;
use Lychee\Bundle\CoreBundle\Entity\UserProfile;
use Lychee\Bundle\CoreBundle\Validator\Constraints\Nickname;
use Lychee\Bundle\CoreBundle\Validator\Constraints\NotSensitiveWord;
use Lychee\Bundle\CoreBundle\Validator\Constraints\Password;
use Lychee\Bundle\CoreBundle\Validator\Constraints\ReservedWord;
use Lychee\Module\Account\Exception\NicknameDuplicateException;
use Lychee\Module\Account\Mission\MissionResult;
use Lychee\Module\Account\Mission\MissionType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Lychee\Module\Authentication\PhoneVerifier;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\ConstraintViolation;
use Lychee\Component\Foundation\StringUtility;

class AccountController extends Controller {

    /**
     *
     * ### 返回内容 ###
     *
     * ```json
     *{
     * "id": "2249256",
     * "nickname": "疯狂的茶几",
     * "avatar_url": "http://qn.ciyocon.com/upload/FlmIIMAMUKt7ci-Q9RLh3Di4hz4H",
     * "gender": "male",
     * "level": 13,
     * "signature": "人生是一张茶几",
     * "ciyoCoin": "0.00",
     * "my_follower": false,
     * "my_followee": false,
     * "followers_count": "1",
     * "followees_count": "4",
     * "following_topics_count": 12,
     * "post_count": 12,
     * "image_comment_count": 0,
     * "cover_url": null,
     * "honmei": "hhhaa",
     * "attributes": null,
     * "skills": null,
     * "constellation": null,
     * "blood_type": null,
     * "age": 29,
     * "birthday": "1988-02-06",
     * "location": "广东省 广州市",
     * "school": null,
     * "community": null,
     * "fancy": null,
     * "favourites_count": 0,  //收藏数
     * }
     *
     * ```
     *
     * @Route("/account/get")
     * @Method("GET")
     * @ApiDoc(
     *   section="account",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="uid", "dataType"="string", "required"=false, "description"="uid与nickname只能提供一个"},
     *     {"name"="nickname", "dataType"="string", "required"=false, "description"="uid与nickname只能提供一个"}
     *   }
     * )
     */
    public function getAction(Request $request) {
        $account = $this->getAuthUser($request);

        if ($request->query->has('uid')) {
            $uid = $request->query->getInt('uid');
            $user = $this->account()->fetchOne($uid);
        } else if ($request->query->has('nickname')) {
            $nickname = $request->query->get('nickname');
            $user = $this->account()->fetchOneByNickname($nickname);
        } else {
            return $this->errorsResponse(CommonError::ParameterMissing('uid'));
        }

        if ($user === null) {
            return $this->errorsResponse(AccountError::UserNotExist(0));
        }

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildProfiledUserSynthesizer(array($user), $account ? $account->id : 0);
        $data = $synthesizer->synthesizeOne($user->id);

        return $this->dataResponse($data);
    }

    /**
     * @Route("/account/search")
     * @Method("GET")
     * @ApiDoc(
     *   section="account",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="keyword", "dataType"="string", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"
     *     }
     *   }
     * )
     */
    public function searchAction(Request $request) {
        $account = $this->getAuthUser($request);
        $keyword = $this->requireParam($request->query, 'keyword');
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);

        $users = $this->account()->fetchByKeyword($keyword, $cursor, $count, $nextCursor);
        $synthesizer = $this->getSynthesizerBuilder()
            ->buildUserSynthesizer($users, $account ? $account->id : 0);
        return $this->arrayResponse(
            'users', $synthesizer->synthesizeAll(), $nextCursor
        );
    }

    /**
     * @Route("/account/password/update")
     * @Method("POST")
     * @ApiDoc(
     *   section="account",
     *   description="更新密码",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="old_password", "dataType"="string", "required"=true},
     *     {"name"="new_password", "dataType"="string", "required"=true}
     *   }
     * )
     */
    public function updatePasswordAction(Request $request) {
        $account = $this->requireAuth($request);

        $oldPassword = $request->request->get('old_password');
        $passwordValid = $this->authentication()->isUserPasswordValid($account->id, $oldPassword);
        if ($passwordValid === false) {
            return $this->errorsResponse(AuthenticationError::PasswordWrong());
        }

        $newPassword = $request->request->get('new_password');
        if (!$this->isValueValid($newPassword, new Password())) {
            return $this->errorsResponse(AuthenticationError::PasswordInvalid());
        }

        $this->authentication()->updatePasswordForUser($account->id, $newPassword);

        return $this->sucessResponse();
    }


    /**
     *
     * ### 返回内容
     *
     * ```json
     *
     * {
     * "result": true // true：成功，false：失败
     * }
     *
     * ```
     * [错误码说明](http://gitlab.ciyo.cn/ciyocon/lychee-server/wikis/服务/接口#接口公共错误码)
     *
     * @Route("/account/password/create")
     * @Method("POST")
     * @ApiDoc(
     *   section="account",
     *   description="创建密码",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="password", "dataType"="string", "required"=true}
     *   }
     * )
     */
    public function createPasswordAction(Request $request) {
        $account = $this->requireAuth($request);
        $newPassword = $request->request->get('password');
        if (!$this->isValueValid($newPassword, new Password())) {
            return $this->errorsResponse(AuthenticationError::PasswordInvalid());
        }
        try {
            $this->authentication()->createPasswordForUser($account->id, $newPassword);
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
              return $this->errorsResponse(AuthenticationError::PasswordIsExist());
        }
        return $this->sucessResponse();
    }

    /**
     * @Route("/account/password/reset")
     * @Method("POST")
     * @ApiDoc(
     *   section="account",
     *   description="重置密码",
     *   parameters={
     *     {"name"="area_code", "dataType"="string", "required"=true,
     *       "description"="区号，请以纯数字提交，且没有0前缀。例如中国，86"},
     *     {"name"="phone", "dataType"="string", "required"=true,
     *       "description"="电话号码，请以纯数字提交"},
     *     {"name"="code", "dataType"="string", "required"=true},
     *     {"name"="password", "dataType"="string", "required"=true}
     *   }
     * )
     */
    public function resetPasswordAction(Request $request) {
        $areaCode = $this->requireInt($request->request, 'area_code');
        $phone = $this->requireParam($request->request, 'phone');
        $code = $this->requireParam($request->request, 'code');
        $password = $this->requireParam($request->request, 'password');

        if ($this->isValueValid($password, array(new Password())) === false) {
            return $this->errorsResponse(AuthenticationError::PasswordInvalid());
        }

        /** @var PhoneVerifier  $smsVerifier */
        $smsVerifier = $this->get('lychee.module.authentication.phone_verifier');
        if ($smsVerifier->verify($areaCode, $phone, $code) === false) {
            return $this->errorsResponse(AuthenticationError::PhoneVerifyFail());
        }

        $account = $this->account()->fetchOneByPhone($areaCode, $phone);
        if ($account === null) {
            return $this->errorsResponse(AuthenticationError::PhoneNonexist());
        }
        $this->authentication()->updatePasswordForUser($account->id, $password);

        return $this->sucessResponse();
    }

    /**
     * @Route("/account/profile/update")
     * @Method("POST")
     * @ApiDoc(
     *   section="account",
     *   description="此接口只修改提供的参数，不提供的参数不作修改",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="nickname", "dataType"="string", "required"=false},
     *     {"name"="avatar_url", "dataType"="string", "required"=false},
     *     {"name"="gender", "dataType"="string", "required"=false,
     *       "description"="允许的值，female, male, none"},
     *     {"name"="cover_url", "dataType"="string", "required"=false, "description"="封面图url"},
     *     {"name"="signature", "dataType"="string", "required"=false, "description"="签名，最长200个字符"},
     *     {"name"="honmei", "dataType"="string", "required"=false, "description"="本命，最长200个字符"},
     *     {"name"="attributes", "dataType"="string", "required"=false, "description"="属性，最长1000个字符，json字符串"},
     *     {"name"="skills", "dataType"="string", "required"=false, "description"="技能，最长200个字符"},
     *     {"name"="constellation", "dataType"="string", "required"=false, "description"="星座，最长20个字符"},
     *     {"name"="blood_type", "dataType"="string", "required"=false, "description"="血型，最长10个字符"},
     *     {"name"="age", "dataType"="string", "required"=false, "description"="年龄，非负整数"},
     *     {"name"="birthday", "dataType"="string", "required"=false, "description"="生日，格式为xxx-xx-xx"},
     *     {"name"="location", "dataType"="string", "required"=false, "description"="所在地，最长200个字符"},
     *     {"name"="school", "dataType"="string", "required"=false, "description"="学校，最长100个字符"},
     *     {"name"="community", "dataType"="string", "required"=false, "description"="社区，最长100个字符"},
     *     {"name"="fancy", "dataType"="string", "required"=false, "description"="喜好，最长200个字符"},
     *   }
     * )
     */
    public function updateProfileAction(Request $request) {
        $account = $this->requireAuth($request);

        if ($request->request->has('nickname')) {
            $nickname = $request->request->get('nickname');
            $this->checkNickname($nickname);
            if ($account->nickname != $nickname) {
                $response = $this->updateNickname($account->id, $nickname);
	            if (null !== $response) {
	            	return $response;
	            }
                $account->nickname = $nickname;
            }
        }
        $accountUpdated = false;
        if ($request->request->has('avatar_url')) {
            $avatarUrl = $request->request->get('avatar_url');
            if ($account->avatarUrl != $avatarUrl) {
                $account->avatarUrl = $avatarUrl;
                $accountUpdated = true;
            }
        }
        if ($request->request->has('gender')) {
            $genderString = $request->request->get('gender');
            $gender = $this->getGenderFromString($genderString);
            if ($gender === false) {
                $this->getLogger()->error(__FILE__.":".__line__.", account: ".$account->id.", gender = ".$genderString);
                return $this->errorsResponse(CommonError::ParameterInvalid('gender', $genderString));
            }
            if ($account->gender != $gender) {
                $account->gender = $gender;
                $accountUpdated = true;
            }
        }

        $profile = $this->account()->fetchOneUserProfile($account->id);
        $profileUpdated = false;
        $properties = array(
            'signature' => 200,
//            'honmei' => 200,
            'attributes' => 1000,
            'skills' => 200,
            'constellation' => 20,
//            'blood_type' => 10,
            'location' => 200,
//            'school' => 100,
//            'community' => 100,
            'fancy' => 200
        );
        foreach ($properties as $property => $maxLength) {
            if ($request->request->has($property)) {
                $value = $request->request->get($property);
                if ($this->isValueValid($value, array(new NotSensitiveWord())) === false ||
                    mb_strlen($value, 'utf8') > $maxLength) {
                    $this->getLogger()->error(__FILE__.":".__line__.", account: ".$account->id.", ".$property.' = '.$value);
                    return $this->errorsResponse(CommonError::ParameterInvalid($property, $value));
                }
                if ($property == 'blood_type') {
                    $profile->bloodType = $value;
                } else if (property_exists($profile, $property)) {
                    $profile->$property = $value;
                }
                $profileUpdated = true;
            }
        }
        if ($request->request->has('signature')) {
            $account->signature = $profile->signature;
            $accountUpdated = true;
        }

        if ($request->request->has('age')) {
            $age = $request->request->getInt('age');
            $profile->age = $age < 0 ? 0 : $age;
            $profileUpdated = true;
        }
        if ($request->request->has('birthday')) {
            $birthday = $request->request->get('birthday');
            $datetime = \DateTime::createFromFormat('Y-m-d h:i:s', $birthday.' 00:00:00');
            $dateErrors = \DateTime::getLastErrors();
            if ($dateErrors['warning_count'] == 0 && $dateErrors['error_count'] == 0) {
                $profile->birthday = $datetime;
            }
            $profileUpdated = true;
        }
        if ($request->request->has('cover_url')) {
            $value = $request->request->get('cover_url');
            $profile->coverUrl = $value;
            $profileUpdated = true;
        }

        if ($accountUpdated) {
            $this->account()->updateInfo($account->id, $account->gender, $account->avatarUrl, $account->signature);
        }
        if ($profileUpdated) {
            $this->account()->updateUserProfile($profile);
        }

        $fillProfile = strlen($account->avatarUrl) > 0 && strlen($profile->signature) > 0 && $profile->age > 0;
        $setAttributes = strlen($profile->attributes) > 0;
        if ($fillProfile || $setAttributes) {
            $missionResult = new MissionResult(0, 0, false);
            if ($fillProfile) {
                $fillProfileMR = $this->missionManager()->userAccomplishMission($account->id, MissionType::FILL_PROFILE);
                if ($fillProfileMR) {
                    $missionResult->add($fillProfileMR);
                }
            }
            if ($setAttributes) {
                $setAttributesMR = $this->missionManager()->userAccomplishMission($account->id, MissionType::SET_ATTRIBUTES);
                if ($setAttributesMR) {
                    $missionResult->add($setAttributesMR);
                }
            }

            $response = array('result' => true);
            $this->injectMissionResult($response, $missionResult);
            return $this->dataResponse($response);
        } else {
            return $this->sucessResponse();
        }
    }

    private function checkNickname($nickname) {
        $constraints = [new Nickname(), new NotSensitiveWord(), new ReservedWord()];
        $violations = $this->get('validator')->validate($nickname, $constraints);
        if (count($violations) == 0) {
            return;
        }
        $errors = [];
        foreach ($violations as $v) {
            /** @var ConstraintViolation $v */
            $c = $v->getConstraint();
            if ($c instanceof Nickname || $c instanceof NotSensitiveWord) {
                $errors[] = AuthenticationError::NicknameInvalid();
            } else if ($c instanceof ReservedWord) {
                $errors[] = CommonError::ContainsReservedWords();
            }
        }
        throw new ErrorsException($errors);
    }

	/**
	 * @param $userId
	 * @param $nickname
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
    private function updateNickname($userId, $nickname) {
        /** @var \Redis $redis */
        $redis = $this->get('snc_redis.spam');
        $updateNicknameKey = 'nickname_update:'.$userId;
        $hasUpdatedInTwentyFourHour = $redis->get($updateNicknameKey);
        if ($hasUpdatedInTwentyFourHour > 0) {
            return $this->errorsResponse(AccountError::RenameOnceInTwentyFourHour());
        }
        try {
            $this->account()->updateNickname($userId, $nickname);
        } catch (NicknameDuplicateException $e) {
            return $this->errorsResponse(AuthenticationError::NicknameUsed());
        }
        $redis->setex($updateNicknameKey, 24 * 3600, 1);
    }

    private function getGenderFromString($string) {
        switch (strtolower($string)) {
            case 'female':
                return User::GENDER_FEMALE;
                break;
            case 'male':
                return User::GENDER_MALE;
                break;
            case 'none':
                return null;
                break;
            default:
                return false;
        }
    }


    /**
     *
     * ### 返回内容 ###
     *
     * ```json
     *{
     * "result": 1,
     * }
     *
     * ```
     *
     * @Route("/account/phone/is_exist")
     * @Method("GET")
     * @ApiDoc(
     *   section="account",
     *   description="判断手机号是否存在",
     *   parameters={
     *     {"name"="area_code", "dataType"="string", "required"=true,
     *       "description"="区号，请以纯数字提交，且没有0前缀。例如中国，86"},
     *     {"name"="phone", "dataType"="string", "required"=true, "description"="手机号"}
     *   }
     * )
     */
    public function isExistPhone(Request $request) {
        $phone  = $this->requireParam($request->query, 'phone');
        $areaCode  = $this->requireParam($request->query, 'area_code');

        if (!StringUtility::isPhoneValid($areaCode, $phone)) {
            return $this->dataResponse(['result'=>1]);
        }

        $result = 0;
        $user = $this->account()->fetchOneByPhone($areaCode, $phone);
        if ($user) {
            $result = 1;
        }
        return $this->dataResponse(['result'=>$result]);
    }


    /**
     *
     * ### 返回内容 ###
     *
     * ```json
     *{
     * "result": true, //true：成功，false：失败
     * }
     *
     * ```
     * [错误码说明](http://gitlab.ciyo.cn/ciyocon/lychee-server/wikis/服务/接口#接口公共错误码)
     *
     * @Route("/account/phone/update")
     * @Method("POST")
     * @ApiDoc(
     *   section="account",
     *   description="修改手机号",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="area_code", "dataType"="string", "required"=true,
     *       "description"="区号，请以纯数字提交，且没有0前缀。例如中国，86"},
     *     {"name"="phone", "dataType"="string", "required"=true, "description"="手机号"},
     *     {"name"="code", "dataType"="string", "required"=true, "description"="验证码"}
     *   }
     * )
     */
    public function updatePhone(Request $request) {
        $account = $this->requireAuth($request);
        $areaCode  = $this->requireParam($request->request, 'area_code');
        $phone  = $this->requireParam($request->request, 'phone');
        $code = $this->requireParam($request->request, 'code');

        if (!StringUtility::isPhoneValid($areaCode, $phone)) {
            return $this->errorsResponse(AuthenticationError::PhoneInvalid());
        }
        if ($this->getPhoneVerifier()->verify($areaCode, $phone, $code) === false) {
            return $this->errorsResponse(AuthenticationError::PhoneVerifyFail());
        }

        try {
            $this->account()->updatePhone($account->id, $areaCode, $phone);
        } catch (\Lychee\Module\Account\Exception\PhoneDuplicateException $e) {
            return $this->errorsResponse(AuthenticationError::PhoneUsed());
        }

        return $this->sucessResponse();
    }

    /**
     * @return PhoneVerifier
     */
    private function getPhoneVerifier() {
        return $this->get('lychee.module.authentication.phone_verifier');
    }

    /**
     *
     * ### 返回内容 ###
     *
     * ```json
     * {
     * "result": "short_video" //short_video: 短视频tab；pic：图文tab
     * }
     *
     * ```
     *
     * @Route("/account/page/default_tab")
     * @Method("GET")
     * @ApiDoc(
     *   section="account",
     *   description="获取个人页默认tab",
     *   parameters={
     *     {"name"="uid", "dataType"="integer", "required"=true, "description"="用户id"},
     *     {"name"="access_token", "dataType"="string", "required"=false}
     *   }
     * )
     */
    public function getDefaultPageTab(Request $request)
    {
        $authorId = $this->requireId($request->query, 'uid');
        $client = $request->query->get(self::CLIENT_PLATFORM_KEY, 'android');
        $nextPicCursor = 0;
        $picIds = $this->post()->fetchPlainIdsByAuthorIdInPublicTopicForClient(
            $authorId, 0, 50, $nextPicCursor, $client);

        $nextShortVideosCursor = 0;
        $shortVideoIds = $this->post()->fetchShortVideoIdsByAuthorIdForClient(
            $authorId, 0, 50, $nextShortVideosCursor, $client);

        $tab = 'pic';
        if (count($shortVideoIds)>count($picIds)) {
            $tab = 'short_video';
        }

        return $this->dataResponse(['result'=>$tab]);
    }
} 
