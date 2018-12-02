<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Bundle\ApiBundle\Error\ErrorsException;
use Lychee\Module\Account\AccountSignInRecorder;
use Lychee\Module\Account\Mission\MissionType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Lychee\Module\Account\Mission\LevelCalculator;

class MissionController extends Controller {

    /**
     *
     * @Route("/mission/{type}", requirements={"type": "(signin|follow_topic)"})
     * @Method("POST")
     * @ApiDoc(
     *   section="mission",
     *   description="由客户端调用的任务，调用即为完成指定任务",
     *   requirements={
     *     {"name"="type", "dataType"="string", "description"="可以为'signin', 'follow_topic'"}
     *   },
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true}
     *   }
     * )
     */
    public function finishAction(Request $request, $type) {
        $account = $this->requireAuth($request);

        $missionType = array(
            'signin' => MissionType::DAILY_SIGNIN,
            'follow_topic' => MissionType::FOLLOW_TOPIC
        )[$type];

        //兼容旧版本，以前是通过客户端来触发入驻次元任务的
        if ($missionType == MissionType::FOLLOW_TOPIC) {
            return $this->failureResponse();
        }

        $missionResult = $this->missionManager()->userAccomplishMission($account->id, $missionType);
        if ($missionResult) {
            if ($missionType == MissionType::DAILY_SIGNIN) {
                /** @var AccountSignInRecorder $signInRecorder */
                $signInRecorder = $this->get('lychee.module.account.sign_in_recorder');
                $os = $request->request->get(self::CLIENT_PLATFORM_KEY);
                $osVersion = $request->request->get(self::CLIENT_OS_VERSION_KEY);
                $deviceId = $request->request->get(self::CLIENT_DEVICE_ID_KEY);
                $clientVersion = $request->request->get(self::CLIENT_APP_VERSION_KEY);
                $signInRecorder->record($account->id, $os, $osVersion, null, $deviceId, $clientVersion);
            }
            
            $response = array('result' => true);
            $this->injectMissionResult($response, $missionResult);

            return $this->dataResponse($response);
        } else {
            return $this->failureResponse();
        }

    }

    /**
     * @Route("/mission/summary")
     * @Method("GET")
     * @ApiDoc(
     *   section="mission",
     *   description="未完成任务情况",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true}
     *   }
     * )
     */
    public function summaryAction(Request $request) {
        $account = $this->requireAuth($request);
        list($count, $experience) = $this->missionManager()->summarizeUserUncompletedMissions($account->id);
        return $this->dataResponse(array('count' => $count, 'experience' => $experience));
    }

    /**
     * @Route("/mission/web")
     * @Method("GET")
     * @ApiDoc(
     *   section="mission",
     *   description="客户端用户任务详情页的页面",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true}
     *   }
     * )
     */
    public function webAction(Request $request) {
        try {
            $account = $this->requireAuth($request);
        } catch (ErrorsException $e) {
            return new Response('请重新登录。');
        }

        $missionManager = $this->missionManager();
        $filledProfile = $missionManager->userHasCompletedMission($account->id, MissionType::FILL_PROFILE);
        $invitedFriends = $missionManager->userHasCompletedMission($account->id, MissionType::INVITE);
        $followTopics = $missionManager->userHasCompletedMission($account->id, MissionType::FOLLOW_TOPIC);
        $setFavoriteTopics = $missionManager->userHasCompletedMission($account->id, MissionType::SET_FAVORITE_TOPIC);
        $setAttributes = $missionManager->userHasCompletedMission($account->id, MissionType::SET_ATTRIBUTES);

        $args = array(
            'user' => $account,
            'access_token' => $request->get('access_token'),
            'filled_profile' => $filledProfile,
            'invited_friends' => $invitedFriends,
            'follow_topics' => $followTopics,
            'set_favorite_topics' => $setFavoriteTopics,
            'set_attributes' => $setAttributes,
            'progress' => 0,
            'need_exp' => 0,
            'activated' => false,
            'experience' => 0,
        );

        if ($missionManager->isUserActivateMissions($account->id) === false) {
            $experience = $this->account()->getUserExperience($account->id);
            $args['experience'] = $experience;
            return $this->render('LycheeApiBundle:Mission:web.html.twig', $args);
        }

        /** @var LevelCalculator $levelCalculator */
        $levelCalculator = $this->get('lychee.module.account.level_calculator');
        $thisLevelExp = $levelCalculator->getExperienceByLevel($account->level);
        $nextLevelExp = $levelCalculator->getExperienceByLevel($account->level + 1);

        if ($nextLevelExp == null) {
            $progress = 100;
            $needExp = 0;
        } else {
            $experience = $this->account()->getUserExperience($account->id);
            $progress = round(($experience - $thisLevelExp) / ($nextLevelExp - $thisLevelExp) * 100);
            $needExp = $nextLevelExp - $experience;
        }

        $args['progress'] = $progress;
        $args['need_exp'] = $needExp;
        $args['activated'] = true;


        return $this->render('LycheeApiBundle:Mission:web.html.twig', $args);
    }

    /**
     * @Route("/mission/activate")
     * @Method("POST")
     * @ApiDoc(
     *   section="mission",
     *   description="旧用户激活任务，拿积累的经验",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true}
     *   }
     * )
     */
    public function activateAction(Request $request) {
        $account = $this->requireAuth($request);

        $missionManager = $this->missionManager();
        if ($missionManager->isUserActivateMissions($account->id) === false) {
            $this->missionManager()->userActivateMissions($account->id);
        }

        /** @var LevelCalculator $levelCalculator */
        $levelCalculator = $this->get('lychee.module.account.level_calculator');
        $thisLevelExp = $levelCalculator->getExperienceByLevel($account->level);
        $nextLevelExp = $levelCalculator->getExperienceByLevel($account->level + 1);

        if ($nextLevelExp == null) {
            $progress = 100;
            $needExp = 0;
        } else {
            $experience = $this->account()->getUserExperience($account->id);
            $progress = round(($experience - $thisLevelExp) / ($nextLevelExp - $thisLevelExp) * 100);
            $needExp = $nextLevelExp - $experience;
        }

        return $this->dataResponse(array(
            'level' => $account->level,
            'progress' => $progress,
            'need_exp' => $needExp
        ));
    }
}
