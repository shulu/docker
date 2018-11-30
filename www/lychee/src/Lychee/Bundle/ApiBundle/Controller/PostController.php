<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Bundle\ApiBundle\AntiSpam\SpamChecker;
use Lychee\Bundle\ApiBundle\Error\CommonError;
use Lychee\Bundle\ApiBundle\Error\ErrorsException;
use Lychee\Bundle\ApiBundle\Error\PostError;
use Lychee\Bundle\ApiBundle\Error\TopicError;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Foundation\CursorWrapper;
use Lychee\Constant;
use Lychee\Module\Account\Mission\MissionType;
use Lychee\Module\City\Entity\CityTopic;
use Lychee\Module\Voting\Entity\VotingOption;
use Lychee\Bundle\AdminBundle\Service\ManagerLog\OperationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Lychee\Module\Post\PostParameter;
use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Bundle\CoreBundle\Geo\BaiduGeocoder;
use Lychee\Component\Foundation\StringUtility;
use Lychee\Module\ContentManagement\Domain\WhiteList;
use Lychee\Bundle\CoreBundle\Entity\User;
use Lychee\Module\Post\StickyService;
use Lychee\Module\IM\GroupService;
use Lychee\Component\IdGenerator\IdGenerator;
use Lychee\Module\Schedule\ScheduleService;
use Lychee\Module\Voting\VotingService;
use Lychee\Bundle\ApiBundle\AntiSpam\SpammerRecorder;
use Lychee\Module\IM\Exceptions\JoinTooMuchGroupInTopic;
use Lychee\Bundle\ApiBundle\Error\IMError;

/**
 * @Route("/post")
 */
class PostController extends Controller {

    /**
     * @return int
     */
    private function generatePostId() {
        /** @var IdGenerator $idGenerator */
        $idGenerator = $this->get('lychee.module.post.id_generator');
        return $idGenerator->generate();
    }

    /**
     * @param User $user
     * @throws ErrorsException
     */
    private function antiSpam($user, $content, $ip) {

	    $ipBlocker = $this->get('lychee_api.ip_blocker');
	    if ($ipBlocker->checkAndUpdate($ip, SpamChecker::ACTION_POST) == false) {
		    throw new ErrorsException(CommonError::SystemBusy());
	    }

	    $action = SpamChecker::ACTION_POST.'_60s';
        $isSpam = $this->getSpamChecker()->check($user->id, 60, 2, $action);

        if($isSpam == false){
	        $action = SpamChecker::ACTION_POST.'_2400s';
	        $isSpam = $this->getSpamChecker()->check($user->id, 2400, 20, $action);
        }

	    if($isSpam == false){
		    $action = SpamChecker::ACTION_POST.'_86400s';
		    $mayBeSpam = $this->getSpamChecker()->check($user->id, 86400, 20, $action);

		    if($mayBeSpam){
				$isSpam = $this->isUserPostSimilar($user->id, 20);
		    }
	    }

        if ($isSpam) {
	        //如果等级小于或者等于4级，直接封号
	        if($user->level <= 4) {
		        //$this->blockUserDevice( $user );
	        }

	        throw new ErrorsException(CommonError::SystemBusy());
        }
    }

    /**
     * @param int $userId
     * @return bool
     */
    private function isUserPostSimilar($userId, $count) {

        $postIds = $this->post()->fetchIdsByAuthorId($userId, 0, $count);
        if (count($postIds) != $count) {
            return false;
        }

        $posts = array_values($this->post()->fetch($postIds));
        if (count($posts) < $count) {
            return false;
        }

        $similarCount = 0;
        for($i=0;$i<$count;$i++){

			$post1 = $posts[$i];
			if($post1->type == POST::TYPE_LIVE){
				continue;
			}

	        $contentA = $post1->content;
			$strLength = 20;
			if(strlen($contentA) < $strLength){
				continue;
			}

			for($k=$i+1;$k<$count;$k++){

				$post2 = $posts[$k];

				if($post2->type == POST::TYPE_LIVE){
					continue;
				}

				$contentB = $post2->content;

				if(strlen($contentB) < $strLength){
					continue;
				}

				if($this->isContentSimilar($contentA, $contentB)){
					$similarCount ++;
				}
			}
        }

        return $similarCount >= $count / 2;
    }

    private function isContentSimilar($contentA, $contentB){

	    $match = similar_text($contentA, $contentB);
	    $len = strlen($contentA);
	    $similarity = $match / $len;
	    return $similarity >= 0.7;
    }

    private function blockUserDevice($user){

	    $userId = $user->id;

	    $this->account()->freeze($userId);

	    $tokenService = $this->get('lychee.module.authentication.token_issuer');
	    $tokenService->revokeTokensByUser($userId);

	    $contentAuditService = $this->get('lychee.module.content_audit');
	    $contentAuditService->removeUserFromAntiRubbish($userId);

	    $managerLogDesc = [];

	    $cleaner = $this->get('lychee.module.account.posts_cleaner');
	    $cleaner->cleanUser($userId);
	    $managerLogDesc['delete_posts_and_comments'] = true;

//	    $blocker = $this->get('lychee.module.account.device_blocker');
//	    try {
//		    $blocker->blockUserDevice($userId);
//	    } catch (\Exception $e) {
//	    }
//	    $managerLogDesc['block_device'] = true;

	    //$this->managerLog()->log(31722, OperationType::BLOCK_USER, $userId, $managerLogDesc);
    }

    /**
     *
     * 判断是否关注了该次元，如果没关注即自动关注
     *
     * @param $userId
     * @param $topicId
     * @return bool|\Symfony\Component\HttpFoundation\Response
     */
    private function checkAndFollowTopic($userId, $topicId) {
        // 默认次元放行
        if ($topicId == self::SPECIAL_TOPIC) {
            return true;
        }

        // 已经关注了即不处理
        if ($this->topicFollowing()->isFollowing($userId, $topicId)) {
            return true;
        }

        // 自动关注
        try {
            $this->topicFollowing()->follow($userId, $topicId);
        } catch (\Lychee\Module\Topic\Exception\FollowingTooMuchTopicException $e) {
            throw new ErrorsException(TopicError::FollowingTooMuchTopic());
        } catch (\Lychee\Module\Topic\Exception\TopicMissingException $e) {
            throw new ErrorsException(TopicError::TopicNotExist($topicId));
        } catch (\Exception $e) {
            throw new ErrorsException(TopicError::RequireFollow());
        }

        return true;
    }

    /**
     *
     *
     *
     * [错误码说明](http://gitlab.ciyo.cn/ciyocon/lychee-server/wikis/服务/接口#接口公共错误码)
     *
     * @Route("/create")
     * @Method("POST")
     * @ApiDoc(
     *   section="post",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="topic_id", "dataType"="integer", "required"=true},
     *     {"name"="type", "dataType"="string", "required"=false, "description"="可选值picture, resource, group_chat, video"},
     *     {"name"="content", "dataType"="string", "required"=true},
     *     {"name"="image_url", "dataType"="string", "required"=false},
     *     {"name"="video_url", "dataType"="string", "required"=false},
     *     {"name"="audio_url", "dataType"="string", "required"=false},
     *     {"name"="annotation", "dataType"="string", "required"=false,
     *       "description"="客户端可以保存一定的数据到这个参数里，客户端获取数据时，将返回同样的数据。
     *       只能是json形式，不能超过1024个字符。"},
     *     {"name"="im_group_name", "dataType"="string", "required"=false, "description"="最长20个字符"},
     *     {"name"="im_group_icon", "dataType"="string", "required"=false, "description"="icon url"},
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
    public function createAction(Request $request) {
        $clientVersion = $request->request->get(self::CLIENT_APP_VERSION_KEY);
        if (!$clientVersion || $clientVersion && version_compare($clientVersion, '3.0', '<')) {
            return $this->errorsResponse(PostError::PostForbidden());
        }
        $account = $this->requirePhoneAuth($request);
        $parameter = $this->extractPostParam($request, $account);

	    $ip = $request->getClientIp();
	    if(!$this->account()->isUserInVip($account->id)){
		    $this->antiSpam($account, $parameter->getContent(), $ip);
	    }

        $parameter->setAuthorId($account->id);
	    $parameter->setAuthorLevel($account->level);

        //下面的逻辑是为了兼容旧版本的客户端1.6
        if ($parameter->getType() == Post::TYPE_GROUP_CHAT) {
            $chatGroupName = $request->request->get('im_group_name');
            if (mb_strlen($chatGroupName, 'utf8') > 20) {
                return $this->errorsResponse(CommonError::ParameterInvalid('im_group_name', $chatGroupName));
            }
            $chatGroupIcon = $request->request->get('im_group_icon');
            if (strlen($chatGroupIcon) > 2083) {
                return $this->errorsResponse(CommonError::ParameterInvalid('im_group_icon', $chatGroupIcon));
            }
            $postId = $this->generatePostId();

            /** @var GroupService $groupService */
            $groupService = $this->get('lychee.module.im.group');
            $group = $groupService->create(
                $parameter->getAuthorId(), $chatGroupName, $chatGroupIcon, $parameter->getContent(),
                $parameter->getTopicId(), $postId);
            if ($group == null) {
                return $this->errorsResponse(CommonError::SystemBusy());
            }
            $parameter->setImGroupId($group->id);
            $parameter->setPostId($postId);
        }

        return $this->createPostWithParameter($parameter);
    }



    /**
     *
     *
     * [错误码说明](http://gitlab.ciyo.cn/ciyocon/lychee-server/wikis/服务/接口#接口公共错误码)
     *
     * @Route("/create_short_video")
     * @Method("POST")
     * @ApiDoc(
     *   section="post",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="topic_id", "dataType"="integer", "required"=true},
     *     {"name"="content", "dataType"="string", "required"=true},
     *     {"name"="image_url", "dataType"="string", "required"=true},
     *     {"name"="video_url", "dataType"="string", "required"=true},
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
     *     {"name"="bgm_id", "dataType"="integer", "required"=false,
     *       "description"="视频背景音乐id"},
     *     {"name"="cover_width", "dataType"="integer", "required"=true,
     *       "description"="视频封面宽度"},
     *     {"name"="cover_height", "dataType"="integer", "required"=true,
     *       "description"="视频封面高度"},
     *     {"name"="upload_duration", "dataType"="integer", "required"=false,
     *       "description"="上传时长，单位秒"},
     *     {"name"="source_way", "dataType"="integer", "required"=false,
     *       "description"="视频来源，1：录制，2：上传"},
     *     {"name"="source[type]", "dataType"="string", "required"=false,
     *       "description"="原始视频类型"},
     *     {"name"="source[bitrate]", "dataType"="integer", "required"=false,
     *       "description"="原始视频码率，kbps"},
     *     {"name"="source[fps]", "dataType"="float", "required"=false,
     *       "description"="原始视频fps"},
     *     {"name"="source[width]", "dataType"="integer", "required"=false,
     *       "description"="原始视频宽度"},
     *     {"name"="source[height]", "dataType"="integer", "required"=false,
     *       "description"="原始视频高度"},
     *     {"name"="source[size]", "dataType"="integer", "required"=false,
     *       "description"="原始视频大小"},
     *     {"name"="processed[type]", "dataType"="string", "required"=false,
     *       "description"="编辑后视频类型"},
     *     {"name"="processed[bitrate]", "dataType"="integer", "required"=false,
     *       "description"="编辑后视频码率，kbps"},
     *     {"name"="processed[width]", "dataType"="integer", "required"=false,
     *       "description"="编辑后视频宽度"},
     *     {"name"="processed[height]", "dataType"="integer", "required"=false,
     *       "description"="编辑后视频高度"},
     *     {"name"="processed[fps]", "dataType"="float", "required"=false,
     *       "description"="编辑后视频fps"},
     *     {"name"="processed[size]", "dataType"="integer", "required"=false,
     *       "description"="编辑后视频大小"},
     *     {"name"="net_type", "dataType"="integer", "required"=false,
     *       "description"="用户当前网络类型, 1：wifi，2：流量"},
     *     {"name"="sv_id", "dataType"="string", "required"=true,
     *       "description"="短视频文件id"}
     *   }
     * )
     */
    public function createShortVideo(Request $request) {
        $clientVersion = $request->request->get(self::CLIENT_APP_VERSION_KEY);
        if (!$clientVersion || $clientVersion && version_compare($clientVersion, '3.0', '<')) {
            return $this->errorsResponse(PostError::PostForbidden());
        }
        $request->request->set('type', 'short_video');

        $annotation = array();
        $annotation['video_cover_width']=$request->request->getInt('cover_width', 0);
        $annotation['video_cover_height']=$request->request->getInt('cover_height', 0);
        $annotation['video_cover']=$request->request->get('image_url');
        $annotation = json_encode($annotation);
        $request->request->set('annotation', $annotation);

        $account = $this->requirePhoneAuth($request);
        $parameter = $this->extractPostParam($request, $account);

        $isOpen = $this->ugsvWhiteList()->isExist($account->id);
        if (empty($isOpen)) {
            return $this->errorsResponse(PostError::SVForbidden());
        }

        $ip = $request->getClientIp();
        if(!$this->account()->isUserInVip($account->id)){
            $this->antiSpam($account, $parameter->getContent(), $ip);
        }

        $parameter->setAuthorId($account->id);
        $parameter->setAuthorLevel($account->level);

        $logger = $this->get('logger');
        $logger->info(__METHOD__.':'.json_encode($request->request->all()));

        return $this->createPostWithParameter($parameter);
    }

    /**
     * @Route("/create_chat")
     * @Method("POST")
     * @ApiDoc(
     *   section="post",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="topic_id", "dataType"="integer", "required"=true},
     *     {"name"="content", "dataType"="string", "required"=true},
     *     {"name"="im_group_name", "dataType"="string", "required"=false, "description"="最长20个字符"},
     *     {"name"="im_group_icon", "dataType"="string", "required"=false, "description"="icon url"},
     *     {"name"="annotation", "dataType"="string", "required"=false,
     *       "description"="客户端可以保存一定的数据到这个参数里，客户端获取数据时，将返回同样的数据。
     *       只能是json形式，不能超过1024个字符。"}
     *   }
     * )
     */
    public function createChat(Request $request) {
        $clientVersion = $request->request->get(self::CLIENT_APP_VERSION_KEY);
        if (!$clientVersion || version_compare($clientVersion, '2.8', '<')) {
            return $this->errorsResponse(PostError::PostForbidden());
        }
        $account = $this->requirePhoneAuth($request);

        $parameter = $this->extractPostParam($request, $account);

	    $ip = $request->getClientIp();
	    if(!$this->account()->isUserInVip($account->id)){
		    $this->antiSpam($account, $parameter->getContent(), $ip);
	    }

        if ($this->topicFollowing()->isFollowing($account->id, $parameter->getTopicId()) == false) {
            return $this->errorsResponse(TopicError::RequireFollow());
        }

        $chatGroupName = $request->request->get('im_group_name');
        if (mb_strlen($chatGroupName, 'utf8') > 20) {
            return $this->errorsResponse(CommonError::ParameterInvalid('im_group_name', $chatGroupName));
        }
        $chatGroupIcon = $request->request->get('im_group_icon');
        if (strlen($chatGroupIcon) > 2083) {
            return $this->errorsResponse(CommonError::ParameterInvalid('im_group_icon', $chatGroupIcon));
        }

        $parameter->setAuthorId($account->id);
	    $parameter->setAuthorLevel($account->level);
        $parameter->setType(Post::TYPE_GROUP_CHAT);

        $postId = $this->generatePostId();

        /** @var GroupService $groupService */
        $groupService = $this->get('lychee.module.im.group');
        try {
            $group = $groupService->create($parameter->getAuthorId(), $chatGroupName, $chatGroupIcon,
                $parameter->getContent(), $parameter->getTopicId(), $postId);
        } catch (JoinTooMuchGroupInTopic $e) {
            return $this->errorsResponse(IMError::JoinTooMuchGroupInTopic());
        }
        if ($group == null) {
            return $this->errorsResponse(CommonError::SystemBusy());
        }
        $parameter->setImGroupId($group->id);
        $parameter->setPostId($postId);

        return $this->createPostWithParameter($parameter);
    }

    /**
     * @Route("/create_schedule")
     * @Method("POST")
     * @ApiDoc(
     *   section="post",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="topic_id", "dataType"="integer", "required"=true},
     *     {"name"="title", "dataType"="string", "required"=true},
     *     {"name"="content", "dataType"="string", "required"=false},
     *     {"name"="start_time", "dataType"="string", "required"=false,
     *       "description"="格式: xxxx-xx-xx xx:xx:xx"},
     *     {"name"="end_time", "dataType"="string", "required"=false,
     *       "description"="格式: xxxx-xx-xx xx:xx:xx"},
     *     {"name"="longitude", "dataType"="float", "required"=false},
     *     {"name"="latitude", "dataType"="float", "required"=false},
     *     {"name"="address", "dataType"="string", "required"=false, "description"="最长200字"},
     *     {"name"="poi", "dataType"="string", "required"=false, "description"="最长100字"},
     *     {"name"="annotation", "dataType"="string", "required"=false,
     *       "description"="客户端可以保存一定的数据到这个参数里，客户端获取数据时，将返回同样的数据。
     *       只能是json形式，不能超过1024个字符。"},
     *   }
     * )
     */
    public function createSchedule(Request $request) {
        $clientVersion = $request->request->get(self::CLIENT_APP_VERSION_KEY);
        if (!$clientVersion || version_compare($clientVersion, '2.8', '<')) {
            return $this->errorsResponse(PostError::PostForbidden());
        }
        $account = $this->requirePhoneAuth($request);

        $parameter = $this->extractPostParam($request, $account);

	    $ip = $request->getClientIp();
	    if(!$this->account()->isUserInVip($account->id)){
		    $this->antiSpam($account, $parameter->getContent(), $ip);
	    }

        if ($this->topicFollowing()->isFollowing($account->id, $parameter->getTopicId()) == false) {
            return $this->errorsResponse(TopicError::RequireFollow());
        }
        $parameter->setAuthorId($account->id);
	    $parameter->setAuthorLevel($account->level);
        $parameter->setType(Post::TYPE_SCHEDULE);

        $title = $request->request->get('title');
        if ($title != null) {
            if (StringUtility::isUtf8Encoding($title) === false) {
                throw new ErrorsException(CommonError::PleaseUseUTF8());
            }
            if (mb_strlen($title, 'utf8') > 60) {
                throw new ErrorsException(array(CommonError::ParameterInvalid('title', $title)));
            }
            $title = $this->sensitiveWordChecker()->replaceSensitiveWords($title);
        }

        list($longitude, $latitude, $address, $poi) = $this->getRequestGeoInfo($request);

        try {
            $startTimeString = $request->request->get('start_time');
            $endTimeString = $request->request->get('end_time');
            $startTime = new \DateTime($startTimeString);
            $endTime = new \DateTime($endTimeString);
        } catch (\Exception $e) {
            return $this->errorsResponse(CommonError::ParameterInvalid('', ''));
        }
        if ($endTime <= $startTime) {
            return $this->errorsResponse(CommonError::ParameterInvalid('', ''));
        }

        $postId = $this->generatePostId();

        /** @var ScheduleService $scheduleService */
        $scheduleService = $this->get('lychee.module.schedule');
        $schedule = $scheduleService->create($account->id, $parameter->getTopicId(), $postId,
            $title, $parameter->getContent(), $address, $poi, $longitude, $latitude,
            $startTime, $endTime);
        $scheduleService->join($account->id, $schedule->id);

        $parameter->setScheduleId($schedule->id);
        $parameter->setPostId($postId);

        return $this->createPostWithParameter($parameter);
    }

    /**
     * @Route("/create_voting")
     * @Method("POST")
     * @ApiDoc(
     *   section="post",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="topic_id", "dataType"="integer", "required"=true},
     *     {"name"="title", "dataType"="string", "required"=true},
     *     {"name"="content", "dataType"="string", "required"=false},
     *     {"name"="opt1", "dataType"="string", "required"=true},
     *     {"name"="opt2", "dataType"="string", "required"=true},
     *     {"name"="opt3", "dataType"="string", "required"=false},
     *     {"name"="opt4", "dataType"="string", "required"=false},
     *     {"name"="annotation", "dataType"="string", "required"=false,
     *       "description"="客户端可以保存一定的数据到这个参数里，客户端获取数据时，将返回同样的数据。
     *       只能是json形式，不能超过1024个字符。"},
     *   }
     * )
     */
    public function createVoting(Request $request) {
        $clientVersion = $request->request->get(self::CLIENT_APP_VERSION_KEY);
        if (!$clientVersion || version_compare($clientVersion, '2.8', '<')) {
            return $this->errorsResponse(PostError::PostForbidden());
        }
        $account = $this->requirePhoneAuth($request);

        $parameter = $this->extractPostParam($request, $account);

	    $ip = $request->getClientIp();
	    if(!$this->account()->isUserInVip($account->id)){
		    $this->antiSpam($account, $parameter->getContent(), $ip);
	    }

        if ($this->topicFollowing()->isFollowing($account->id, $parameter->getTopicId()) == false) {
            return $this->errorsResponse(TopicError::RequireFollow());
        }
        $parameter->setAuthorId($account->id);
	    $parameter->setAuthorLevel($account->level);
        $parameter->setType(Post::TYPE_VOTING);

        $title = $request->request->get('title');
        if ($title != null) {
            if (StringUtility::isUtf8Encoding($title) === false) {
                throw new ErrorsException(CommonError::PleaseUseUTF8());
            }
            if (mb_strlen($title, 'utf8') > 60) {
                throw new ErrorsException(array(CommonError::ParameterInvalid('title', $title)));
            }
            $title = $this->sensitiveWordChecker()->replaceSensitiveWords($title);
        }

        $options = array();
        for ($i = 1; $i <= 8; ++ $i) {
            $optKey = 'opt'.$i;
            if ($request->request->has($optKey) === false) {
                break;
            }
            $optTitle = $request->request->get($optKey);
            if (mb_strlen($optTitle, 'utf8') > 20) {
                throw new ErrorsException(array(CommonError::ParameterInvalid($optKey, $optTitle)));
            }
            $opt = new VotingOption();
            $opt->title = $optTitle;
            $options[] = $opt;
        }
        if (count($options) < 2) {
            throw new ErrorsException(array(CommonError::ParameterInvalid('opt', '')));
        }

        $postId = $this->generatePostId();

        /** @var VotingService $votingService */
        $votingService = $this->get('lychee.module.voting');
        $voting = $votingService->create($postId, $title, $parameter->getContent(), $options);

        $parameter->setVotingId($voting->id);
        $parameter->setPostId($postId);

        return $this->createPostWithParameter($parameter);
    }

    private function getRequestGeoInfo(Request $request) {
        $longitude = $request->request->filter(
            'longitude', null, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $latitude = $request->request->filter(
            'latitude', null, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $longitude = empty($longitude) ? null : $longitude;
        $latitude = empty($latitude) ? null : $latitude;
        $address = $request->request->get('address');
        $address = empty($address) ? null : $address;
        if (StringUtility::isUtf8Encoding($address) === false) {
            throw new ErrorsException(CommonError::PleaseUseUTF8());
        }
        $poi = $request->request->get('poi');
        $poi = empty($poi) ? null: $poi;
        if (StringUtility::isUtf8Encoding($poi) === false) {
            throw new ErrorsException(CommonError::PleaseUseUTF8());
        }
        if (mb_strlen($poi, 'utf8') > 100) {
            throw new ErrorsException(CommonError::ParameterInvalid('poi', $poi));
        }
        if (mb_strlen($address, 'utf8') > 200) {
            throw new ErrorsException(CommonError::ParameterInvalid('address', $address));
        }

        if ($longitude != null && $latitude != null && $address == null) {
            $address = $this->fetchAddress($latitude, $longitude);
        }
        return array($longitude, $latitude, $address, $poi);
    }

    /**
     * @param Request $request
     * @param User $account
     *
     * @return PostParameter
     * @throws ErrorsException
     */
    private function extractPostParam($request, $account) {
        $content = $this->requireParam($request->request, 'content');
        if ((is_string($content) && StringUtility::isUtf8Encoding($content)) === false) {
            throw new ErrorsException(CommonError::PleaseUseUTF8());
        }
        if ($content != null) {
            if (mb_strlen($content, 'utf8') > 2000) {
                throw new ErrorsException(array(PostError::ContentTooLong(2000)));
            }
            $content = $this->sensitiveWordChecker()->replaceSensitiveWords($content);
            if (preg_match_all('#https?:\/\/(?:[^@:]*:[^@:]*@)?([0-9a-zA-Z\-_\.]*)#i', $content, $matches)) {
                $hostes = $matches[1];
                foreach ($hostes as $host) {
                    if (strlen($host) < 3 || strpos($host, '.') === false) {
                        throw new ErrorsException(array(PostError::UrlIsForbidden()));
                    }

                    $this->inputDomainRecorder()->record($account->id, $host);
                }
                foreach ($hostes as $host) {
                    /** @var WhiteList $domainWhiteList */
                    $domainWhiteList = $this->get('lychee.module.content_management.domain_whitelist');
                    if ($domainWhiteList->isValid($host) === false) {
                        throw new ErrorsException(array(PostError::UrlIsForbidden()));
                    }
                }
            }
        }

        $topicId = $this->requireId($request->request, 'topic_id');
        $topic = $this->topic()->fetchOne($topicId);
        if ($topic == null || $topic->deleted) {
            throw new ErrorsException(array(TopicError::TopicNotExist($topicId)));
        }

        $imageUrl = $request->request->get('image_url');
        $videoUrl = $request->request->get('video_url');
        $audioUrl = $request->request->get('audio_url');
        $siteUrl = $request->request->get('site_url');
        $annotation = $request->request->get('annotation');
        $bgmId = $request->request->getInt('bgm_id');
        $svId = $request->request->get('sv_id');
        if (strlen($annotation) > 2048) {
            throw new ErrorsException(array(PostError::AnnotationTooLong(2048)));
        }
        if (StringUtility::isJsonString($annotation) === false) {
            throw new ErrorsException(array(PostError::AnnotationError()));
        }

        $typeStr = $request->request->get('type', 'picture');
        $typeMap = array(
            'picture' => Post::TYPE_NORMAL,
            'resource' => Post::TYPE_RESOURCE,
            'group_chat' => Post::TYPE_GROUP_CHAT,
            'video' => Post::TYPE_VIDEO,
            'short_video' => Post::TYPE_SHORT_VIDEO,
	        'live' => Post::TYPE_LIVE,
        );
        $type = isset($typeMap[$typeStr]) ? $typeMap[$typeStr] : Post::TYPE_NORMAL;
        if ($type == Post::TYPE_RESOURCE) {
            $resourceData = json_decode($annotation, true);
            if (!isset($resourceData['resource_link'])) {
                throw new ErrorsException(array(PostError::ResourceInvalid()));
            }
            if (preg_match('#^https?:\/\/(?:[^@:]*:[^@:]*@)?([0-9a-zA-Z\-_\.]*)#',
                    $resourceData['resource_link'], $matches) == false) {
                throw new ErrorsException(array(PostError::ResourceInvalid()));
            }
            /** @var WhiteList $domainWhiteList */
            $domainWhiteList = $this->get('lychee.module.content_management.domain_whitelist');
            if ($domainWhiteList->isValid($matches[1]) == false) {
                throw new ErrorsException(array(PostError::ResourceIsForbidden()));
            }
        }

        if ($type == Post::TYPE_SHORT_VIDEO) {
            $bgmId = intval($bgmId);
            if (!is_string($svId)||empty($svId)) {
                throw new ErrorsException(array(PostError::SVIdInvalid()));
            }
            if (empty($videoUrl)) {
                throw new ErrorsException(array(PostError::VideoUrlInvalid()));
            }
        }

        $parameter = new PostParameter();
        $parameter->setTopicId($topicId > 0 ? $topicId : null);
        $parameter->setContent($content);
        $parameter->setResource($imageUrl, $videoUrl, $audioUrl, $siteUrl);
        $parameter->setAnnotation($annotation);
        $parameter->setType($type);
        $parameter->setBgmId($bgmId);
        $parameter->setSvId($svId);

        return $parameter;
    }

    private function fetchAddress($latitude, $longitude) {
        $geocoder = new BaiduGeocoder('p9IGaqRYyAwkG1oKQBTwnQ8R');
        return $geocoder->getAddressWithCoordinate($latitude, $longitude);
    }

	/**
	 * @param string $location
	 *
	 * @return string | null
	 */
    public function getCityFromLocation($location) {

	    $str = trim( $location );
	    if ( strlen( $str ) == 0 ) {
		    return null;
	    }

	    $directCitys = array( '北京', '上海', '天津', '重庆' );

	    foreach ( $directCitys as $dc) {

			if ( StringUtility::startsWith($str, $dc)) {
				return $dc;
			}
		}

		$components = explode(' ', $str);
		$count = count($components);

		if($count == 1){
			return $components[0];
		}

		if($count == 2){

			$result = $components[1];
			if(StringUtility::endsWith($result, '市')){
				$r = substr($result, 0, strlen($result) - 1);
				return $r;
			}

			return $result;
		}
    }

	/**
	 * @return array
	 */
	private function filterCityTopicIds($topicIds) {
		$topics = $this->entityManager->getRepository(CityTopic::class)
		                              ->findBy([
			                              'topicId' => $topicIds
		                              ]);

		return ArrayUtility::columns($topics, 'topicId');
	}

    /**
     * @param PostParameter $parameter
     * @return JsonResponse
     */
    private function createPostWithParameter($parameter) {

    	$profile = $this->account()->fetchOneUserProfile($parameter->getAuthorId());
    	$authorLevel = $parameter->getAuthorLevel();

    	$recommendationTopics = $this->recommendation()->filterRecommendableTopicIds(array($parameter->getTopicId()));
    	$allowTopic = (($parameter->getTopicId() == self::SPECIAL_TOPIC) ||
	                   (isset($recommendationTopics) && count($recommendationTopics) > 0));

    	$postContent = $parameter->getContent();
    	$invalidContent = (strpos($postContent, 'http://pan.baidu.com') > 0 || strpos($postContent, 'https://pan.baidu.com') > 0);

    	//Get User Location, 等级大于等于8级，发表在精选次元并且不是百度云盘的链接的帖子
    	if(isset($profile) && $authorLevel >= 8 && $allowTopic && !$invalidContent){

    		$location = $profile->location;

    		if(isset($location)){
				$city = $this->getCityFromLocation($location);

				if(isset($city)) {

					$cityId = $this->post()->getCityId( $city );

					if ($cityId > 0) {
						$parameter->setCityId( $cityId );
					}
				}
		    }
	    }

	    $vip = $this->account()->isUserInVip($parameter->getAuthorId());
    	$parameter->setIsVip($vip);

        $post = $this->post()->create($parameter);

        $missionResult = $this->missionManager()
            ->userAccomplishMission($parameter->getAuthorId(), MissionType::DAILY_POST);

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildListPostSynthesizer(array($post->id => $post), $parameter->getAuthorId());
        $response = $synthesizer->synthesizeOne($post->id);
        $this->injectMissionResult($response, $missionResult);
        return $this->dataResponse($response);
    }

	/**
	 * @Route("/testlocation")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="post",
	 *   parameters={
	 *   }
	 * )

	public function testLocationAction(Request $request){

		set_time_limit(0);
		$alllocation = $this->account()->fetchAllUserLocations();
		$logger = $this->get('monolog.logger.thirdparty_invoke');

		foreach( $alllocation as $location ){

			$city = $this->getCityFromLocation($location);

			if(strlen($city) > 0) {
				$cityId = $this->post()->getCityId( $city );

				if ( $cityId <= 0 ) {
					$logger->info( sprintf(
						"[%s] => %s",
						$location, $city
					) );
				}
			}
		}

		return $this->sucessResponse();
	}
	 */

    /**
     * @Route("/delete")
     * @Method("POST")
     * @ApiDoc(
     *   section="post",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="pid", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function deleteAction(Request $request) {
        $account = $this->requireAuth($request);
        $postId = $this->requireInt($request, 'pid');
        $post = $this->post()->fetchOne($postId);
        if ($post === null) {
            return $this->errorsResponse(PostError::PostNotExist($postId));
        }

        $deleterIsManager = false;
        if ($post->authorId != $account->id) {
            if ($post->authorId == Constant::CIYUANJIANG_ID) {
                return $this->errorsResponse(PostError::NotYourOwnPost());
            }

            $topic = $this->topic()->fetchOne($post->topicId);
            if ($topic) {
                $deleterIsManager = $topic->managerId == $account->id
                    || $this->getCoreMemberService()->isCoreMember($topic->id, $account->id);
                if (!$deleterIsManager) {
                    if ($post->deleted === false) {
                        return $this->errorsResponse(PostError::NotYourOwnPost());
                    } else {
                        return $this->errorsResponse(PostError::PostNotExist($postId));
                    }
                }
            } else {
                if ($post->deleted === false) {
                    return $this->errorsResponse(PostError::NotYourOwnPost());
                } else {
                    return $this->errorsResponse(PostError::PostNotExist($postId));
                }
            }
        }

        if ($post->deleted) {
            return $this->sucessResponse();
        } else {
            try {
                $this->post()->delete($post->id, $account->id);
                if ($deleterIsManager) {
                    $this->get('lychee.module.notification')
                        ->notifyIllegalPostDeletedByTopicManagerEvent($post->id, $account->id);
                }
                return $this->sucessResponse();
            } catch (\Exception $e) {
                throw $e;
            }
        }
    }

    /**
     * @Route("/fold")
     * @Method("POST")
     * @ApiDoc(
     *   section="post",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="pid", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function foldAction(Request $request) {
        $account = $this->requireAuth($request);
        $postId = $this->requireInt($request, 'pid');
        $post = $this->post()->fetchOne($postId);
        if ($post === null || $post->deleted) {
            return $this->errorsResponse(PostError::PostNotExist($postId));
        }
        if ($post->topicId == null) {
            return $this->errorsResponse(TopicError::TopicNotExist(0));
        }
        $topic = $this->topic()->fetchOne($post->topicId);
        if ($topic->managerId !== $account->id) {
            return $this->errorsResponse(TopicError::YouAreNotManager());
        }

        if ($post->folded) {
            return $this->sucessResponse();
        } else {
            try {
                $this->post()->fold($post->id);
                return $this->sucessResponse();
            } catch (\Exception $e) {
                throw $e;
            }
        }
    }

    /**
     * @Route("/get")
     * @Method("GET")
     * @ApiDoc(
     *   section="post",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="pid", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function getAction(Request $request) {
        $account = $this->getAuthUser($request);
        $postId = $this->requireId($request->query, 'pid');

        $post = $this->post()->fetchOne($postId);
        if ($post === null) {
            return $this->errorsResponse(PostError::PostNotExist($postId));
        }
        if ($post->topicId) {
            $topic = $this->topic()->fetchOne($post->topicId);
            if ($topic && $topic->private && $this->topicFollowing()->isFollowing($account->id, $topic->id) == false) {
                return $this->errorsResponse(TopicError::RequireFollow());
            }
        }

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildPostSynthesizer(array($post), $account ? $account->id : 0);
        $postInfo = $synthesizer->synthesizeOne($postId);
        $isSticky = $this->getStickyService()->isPostSticky($post->id, $post->topicId);
        if ($isSticky) {
            $postInfo['sticky'] = true;
        }
        
        return $this->dataResponse($postInfo);
    }

    /**
     *
     * ```json
     * {
     * "posts": [
     * {
     * "id": "128916531779585",
     * "topic": {
     * "id": "54687",
     * "create_time": 1521254500,
     * "title": "ciyo不存在的次元",
     * "description": "啊啊啊简介啊啊啊",
     * "index_image": "http://qn.ciyocon.com/img-theme-0011.png",
     * "post_count": 51,
     * "followers_count": 13,
     * "private": false,
     * "apply_to_follow": false,
     * "color": "13df83",
     * "certified": false,
     * "link": "Www.baidu.com",
     * "link_title": "测试",
     * "following": true,
     * "manager": {
     * "id": "2422630"
     * }
     * },
     * "create_time": 1527001985,
     * "type": "picture",
     * "content": "该帖子安卓可以看，不给ios看",
     * "author": {
     * "id": "2249256",
     * "nickname": "疯狂的茶几",
     * "avatar_url": "http://qn.ciyocon.com/upload/FlmIIMAMUKt7ci-Q9RLh3Di4hz4H",
     * "gender": "male",
     * "level": 13,
     * "signature": "人生是一张茶几",
     * "ciyoCoin": "0.00",
     * "my_follower": false,
     * "my_followee": true
     * },
     * "latest_likers": [],
     * "liked_count": 0,
     * "commented_count": 0,
     * "reposted_count": 0,
     * "liked": false,
     * "favorited": false
     * },
     * {
     * "id": "128915915507713",
     * "topic": {
     * "id": "54687",
     * "create_time": 1521254500,
     * "title": "ciyo不存在的次元",
     * "description": "啊啊啊简介啊啊啊",
     * "index_image": "http://qn.ciyocon.com/img-theme-0011.png",
     * "post_count": 51,
     * "followers_count": 13,
     * "private": false,
     * "apply_to_follow": false,
     * "color": "13df83",
     * "certified": false,
     * "link": "Www.baidu.com",
     * "link_title": "测试",
     * "following": true,
     * "manager": {
     * "id": "2422630"
     * }
     * },
     * "create_time": 1527001397,
     * "type": "picture",
     * "content": "试试水",
     * "author": {
     * "id": "2249256",
     * "nickname": "疯狂的茶几",
     * "avatar_url": "http://qn.ciyocon.com/upload/FlmIIMAMUKt7ci-Q9RLh3Di4hz4H",
     * "gender": "male",
     * "level": 13,
     * "signature": "人生是一张茶几",
     * "ciyoCoin": "0.00",
     * "my_follower": false,
     * "my_followee": true
     * },
     * "latest_likers": [],
     * "liked_count": 0,
     * "commented_count": 0,
     * "reposted_count": 0,
     * "liked": false,
     * "favorited": false
     * }
     * ],
     * "next_cursor": "128915915507713"
     * }
     * ```
     *
     * @Route("/timeline/user")
     * @Method("GET")
     * @ApiDoc(
     *   section="post",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="uid", "dataType"="integer", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function listPostsByUserAction(Request $request) {
        $account = $this->requireAuth($request);
        $userId = $this->requireId($request->query, 'uid');
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 100);

        if ($userId == $account->id) {
            //查看自己的帖子列表时显示全部帖子
            $postIds = $this->post()->fetchIdsByAuthorId(
                $userId, $cursor, $count, $nextCursor
            );
        } else {
           //查看别人的帖子列表时只显示在公开次元内的
            $postIds = $this->post()->fetchIdsByAuthorIdInPublicTopic(
                $userId, $cursor, $count, $nextCursor
            );
        }

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildListPostSynthesizer($postIds, $account ? $account->id : 0);
        $postInfos = $synthesizer->synthesizeAll();
        $postInfos = $this->filterUndeletedPosts($postInfos);
        return $this->arrayResponse(
            'posts', $postInfos, $nextCursor
        );
    }

    /**
     * @Route("/timeline/topic/user")
     * @Method("GET")
     * @ApiDoc(
     *   section="post",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="tid", "dataType"="integer", "required"=true},
     *     {"name"="uid", "dataType"="integer", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function listPostsByTopicUserAction(Request $request) {
        $account = $this->requireAuth($request);
        $topicId = $this->requireId($request->query, 'tid');
        $userId = $this->requireId($request->query, 'uid');
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 100);

        $postIds = $this->post()->fetchIdsByAuthorIdAndTopicId(
            $userId, $topicId, $cursor, $count, $nextCursor
        );

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildListPostSynthesizer($postIds, $account ? $account->id : 0);
        $postInfos = $synthesizer->synthesizeAll();
        $postInfos = $this->filterUndeletedPosts($postInfos);
        return $this->arrayResponse(
            'posts', $postInfos, $nextCursor
        );
    }

    /**
     * @Route("/timeline/topic")
     * @Method("GET")
     * @ApiDoc(
     *   section="post",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="tid", "dataType"="integer", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function listPostsByTopicAction(Request $request) {
        $account = $this->getAuthUser($request);
        $topicId = $this->requireId($request->query, 'tid');
        $topic = $this->topic()->fetchOne($topicId);
        if ($topic == null || $topic->deleted) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }
        if ($topic->private && $account != null && $this->topicFollowing()->isFollowing($account->id, $topicId) == false) {
            return $this->errorsResponse(TopicError::RequireFollow());
        }
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 100);

        $client = $request->query->get(self::CLIENT_PLATFORM_KEY, 'android');
        $hasStickyPost = false;
        if ($cursor < 1000000) {
            $stickyPostIds = $this->getStickyService()->getStickyPostIds($topicId, $cursor, $count, $nextCursor);
            if (!empty($stickyPostIds)) {
                $hasStickyPost = true;
            }
            $remainCount = $count - count($stickyPostIds);
            if ($remainCount > 0) {
                $sourcePostIds = $this->post()->fetchIdsByTopicIdForClient(
                    $topicId, 0, $remainCount, $nextCursor, $client
                );
                $postIds = array_values(array_unique(array_merge($stickyPostIds, $sourcePostIds)));
            } else {
                $postIds = $stickyPostIds;
            }
        } else {
            //因为当前的设置中,每个次元最多可以置顶3个帖子.所以这里直接把置顶的帖子从余下的时间线中剔除.
            $stickyPostIds = $this->getStickyService()->getStickyPostIds($topicId, 0, 10, $nextCursor);
            $postIds = $this->post()->fetchIdsByTopicId(
                $topicId, $cursor, $count, $nextCursor
            );
            $postIds = ArrayUtility::diffValue($postIds, $stickyPostIds);
        }

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildListPostSynthesizer($postIds, $account ? $account->id : 0);
        $posts = $synthesizer->synthesizeAll();
        $posts = $this->filterUndeletedPosts($posts);
        if ($hasStickyPost) {
            $clientVersion = $request->query->get(self::CLIENT_APP_VERSION_KEY, '2.2');
            $oldVersion = version_compare($clientVersion, '2.2', '<');
            foreach ($posts as &$post) {
                if (in_array($post['id'], $stickyPostIds)) {
                    if ($oldVersion) {
                        //旧版本中设置为1意味着是次元领主置顶的.
                        $post['sticky_level'] = 1;
                    } else {
                        $post['sticky'] = true;
                    }
                }
            }
            unset($post);
        }

        $exposedPostIdsAndTopicIds = array();
        foreach ($posts as $p) {
            $exposedPostIdsAndTopicIds[] = [$p['id'], $p['topic']['id']];
        }
        $this->get('lychee.module.post.exposure_recorder')->recordPostsExposure($exposedPostIdsAndTopicIds);

        return $this->arrayResponse(
            'posts', $posts, $nextCursor
        );
    }

    /**
     * @Route("/timeline/topic/chat")
     * @Method("GET")
     * @ApiDoc(
     *   section="post",
     *   description="获取次元里的群聊帖子",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="tid", "dataType"="integer", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function listChatPostsByTopicAction(Request $request) {
        $account = $this->requireAuth($request);
        $topicId = $this->requireId($request->query, 'tid');
        $topic = $this->topic()->fetchOne($topicId);
        if ($topic == null) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }
        if ($topic->private && $this->topicFollowing()->isFollowing($account->id, $topicId) == false) {
            return $this->errorsResponse(TopicError::RequireFollow());
        }
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 100);

        $postIds = $this->post()->fetchIdsWithChatByTopicId(
            $topicId, $cursor, $count, $nextCursor
        );

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildListPostSynthesizer($postIds, $account ? $account->id : 0);
        $postInfos = $synthesizer->synthesizeAll();
        $postInfos = $this->filterUndeletedPosts($postInfos);
        return $this->arrayResponse(
            'posts', $postInfos, $nextCursor
        );
    }

    /**
     * @Route("/timeline/topic/schedule")
     * @Method("GET")
     * @ApiDoc(
     *   section="post",
     *   description="获取次元里的未取消活动的活动帖子",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="tid", "dataType"="integer", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function listSchedulePostsByTopicAction(Request $request) {
        $account = $this->requireAuth($request);
        $topicId = $this->requireId($request->query, 'tid');
        $topic = $this->topic()->fetchOne($topicId);
        if ($topic == null) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }
        if ($topic->private && $this->topicFollowing()->isFollowing($account->id, $topicId) == false) {
            return $this->errorsResponse(TopicError::RequireFollow());
        }
        list($cursor, $count) = $this->getStringCursorAndCount($request->query, 20, 100);

        /** @var ScheduleService $scheduleService */
        $scheduleService = $this->get('lychee.module.schedule');
        $postIds = $scheduleService->getSchedulePostIdsByTopicId($topicId, $cursor, $count, $nextCursor);
        $synthesizer = $this->getSynthesizerBuilder()
            ->buildListPostSynthesizer($postIds, $account ? $account->id : 0);
        $postInfos = $synthesizer->synthesizeAll();
        $postInfos = $this->filterUndeletedPosts($postInfos);
        return $this->arrayResponse(
            'posts', $postInfos, $nextCursor
        );
    }

    /**
     * @Route("/timeline/me/schedule")
     * @Method("GET")
     * @ApiDoc(
     *   section="post",
     *   description="获取用户参加过的未取消活动的活动帖子",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function listSchedulePostsByUserAction(Request $request) {
        $account = $this->requireAuth($request);
        list($cursor, $count) = $this->getStringCursorAndCount($request->query, 20, 100);

        /** @var ScheduleService $scheduleService */
        $scheduleService = $this->get('lychee.module.schedule');
        $postIds = $scheduleService->getSchedulePostIdsByUserId($account->id, $cursor, $count, $nextCursor);
        $synthesizer = $this->getSynthesizerBuilder()
            ->buildListPostSynthesizer($postIds, $account ? $account->id : 0);
        $postInfos = $synthesizer->synthesizeAll();
        $postInfos = $this->filterUndeletedPosts($postInfos);
        return $this->arrayResponse(
            'posts', $postInfos, $nextCursor
        );
    }


    /**
     * @Route("/timeline/topic/hot")
     * @Method("GET")
     * @ApiDoc(
     *   section="post",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="tid", "dataType"="integer", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function listHotPostsByTopicAction(Request $request) {
        $this->getAuthUser($request);
        return $this->arrayResponse(
            'posts', array(), 0
        );
    }

	/**
	 * @Route("/timeline/city")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="post",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=false},
	 *     {"name"="gender", "dataType"="string", "required"=false,
	 *     "description"="过滤性别，允许的值：female, male"},
	 *     {"name"="age_min", "dataType"="integer", "required"=false,
	 *     "description"="过滤年龄，和age_max一起传入"},
	 *     {"name"="age_max", "dataType"="integer", "required"=false,
	 *     "description"="过滤年龄，和age_min一起传入"},
	 *     {"name"="cursor", "dataType"="integer", "required"=false},
	 *     {"name"="count", "dataType"="integer", "required"=false,
	 *       "description"="每次返回的数据，默认20，最多不超过100"}
	 *   }
	 * )
	 */
    public function listCityPosts(Request $request){

	    $account = $this->requireAuth($request);
	    $sex = $request->query->get('gender');
	    $ageMin = $request->query->get('age_min');
	    $ageMax = $request->query->get('age_max');

	    if($ageMax != null && $ageMin == null){
		    return $this->errorsResponse(CommonError::ParameterMissing('age_min'));
	    }

	    if($ageMax == null && $ageMin != null){
		    return $this->errorsResponse(CommonError::ParameterMissing('age_max'));
	    }

	    if($sex != null && $sex != 'male' && $sex != 'female'){
		    return $this->errorsResponse(CommonError::ParameterInvalid('sex'));
	    }
	    $gender = null;
	    if($sex == 'female'){
			$gender = User::GENDER_FEMALE;
	    } else if($sex == 'male'){
		    $gender = User::GENDER_MALE;
	    }

	    /*
	    $logger = $this->get('monolog.logger.thirdparty_invoke');
	    $logger->info( sprintf(
			    "[gender] => %s",
			    $gender
		    )
	    );
	    */

	    $profile = $this->account()->fetchOneUserProfile($account->id);
	    $location = $profile->location;
		$cityId = 0;

	    if(isset($location)) {
		    $city = $this->getCityFromLocation( $location );

		    if (isset( $city ) ) {
			    $cityId = $this->post()->getCityId( $city );
		    }
	    }

	    if($cityId <= 0){
		    return $this->errorsResponse(PostError::CityNotSetup());
	    }

	    list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 100);
	    $postIds = $this->post()->fetchIdsByCityId(
		    $cityId, $cursor, $count, $nextCursor, $gender, $ageMin, $ageMax
	    );

	    $synthesizer = $this->getSynthesizerBuilder()
	                        ->buildListPostSynthesizer($postIds, $account ? $account->id : 0);
	    $posts = $synthesizer->synthesizeAll();
	    $posts = $this->filterUndeletedPosts($posts);

	    return $this->arrayResponse(
		    'posts', $posts, $nextCursor
	    );
    }


    /**
     * @Route("/timeline/following")
     * @Method("GET")
     * @ApiDoc(
     *   section="post",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="scope", "dataType"="string", "required"=false,
     *       "description"="获取数据的范围，'all'为全部，'topic'为话题，'user'为用户。
     *         默认是'all'，注意区分大小写。"},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function timelineAction(Request $request) {
        $account = $this->requireAuth($request);
        $scope = $request->query->get('scope');
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 100);

        $topicIterator = new CursorWrapper(
            function($cursor, $count, &$nextCursor) use ($account) {
                $itor = $this->topicFollowing()->getUserFolloweeIterator($account->id);
                $itor->setCursor($cursor)->setStep($count);
                $nextCursor = $itor->getNextCursor();
                return $itor->current();
            },
            100
        );
        $userIterator = new CursorWrapper(
            function($cursor, $count, &$nextCursor) use ($account) {
                if ($cursor === 0) {
                    $followees = $this->relation()->fetchFolloweeIdsByUserId(
                        $account->id, $cursor, $count - 1, $nextCursor
                    );
                    $followees[] = $account->id;
                    return $followees;
                } else {
                    return $this->relation()->fetchFolloweeIdsByUserId(
                        $account->id, $cursor, $count, $nextCursor
                    );
                }
            },
            100
        );


        $client = $request->query->get(self::CLIENT_PLATFORM_KEY, 'android');

        switch ($scope) {
            case 'topic':
                $postIds = $this->post()->fetchIdsByAuthorIdsAndTopicIdsForClient(
                    null, $topicIterator, $cursor, $count, $nextCursor, $client
                );
                break;
            case 'user':
                $postIds = $this->post()->fetchIdsByAuthorIdsAndTopicIdsForClient(
                    $userIterator, null, $cursor, $count, $nextCursor, $client
                );
                break;
            default:
                $postIds = $this->post()->fetchIdsByAuthorIdsAndTopicIdsForClient(
                    $userIterator, $topicIterator, $cursor, $count, $nextCursor, $client
                );
        }

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildListPostSynthesizer($postIds, $account->id);
        return $this->arrayResponse(
            'posts', $synthesizer->synthesizeAll(), $nextCursor
        );
    }

    /**
     * @Route("/latest_by_topics")
     * @Method("GET")
     * @ApiDoc(
     *   section="post",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="tids", "dataType"="string", "required"=true,
     *       "description"="话题id，多个id用半角逗号分隔，一次最多50个id"},
     *     {"name"="detail", "dataType"="integer", "required"=false,
     *       "description"="是否返回详细post信息，1返回，0只返回id，默认为0"}
     *   }
     * )
     */
    public function fetchLatestByTopic(Request $request) {
        $account = $this->requireAuth($request);
        $topicIds = $this->getRequestIds($request->query, 'tids', 50);
        $detail = $request->query->getInt('detail', 0);

        if ($detail) {
            $postIdsByTopicIds = $this->post()->fetchLatestIdsGroupByTopicId($topicIds, 4);
            $postIds = call_user_func_array('array_merge', $postIdsByTopicIds);
            $synthesizer = $this->getSynthesizerBuilder()
                ->buildListPostSynthesizer($postIds, $account->id);
            $result = array();
            foreach ($postIdsByTopicIds as $topicId => $postIds) {
                $posts = array();
                foreach ($postIds as $postId) {
                    $posts[] = $synthesizer->synthesizeOne($postId);
                }
                $result[$topicId] = $posts;
            }
        } else {
            $result = $this->post()->fetchLatestIdsGroupByTopicId($topicIds, 1);
        }

        return $this->dataResponse($result);
    }

    /**
     * @Route("/stick")
     * @Method("POST")
     * @ApiDoc(
     *   section="post",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="pid", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function stickPost(Request $request) {
        $account = $this->requireAuth($request);
        $postId = $this->requireId($request->request, 'pid');

        $post = $this->post()->fetchOne($postId);
        if ($post == null) {
            return $this->errorsResponse(PostError::PostNotExist($postId));
        }

        $topic = $this->topic()->fetchOne($post->topicId);
        if ($topic == null || ($topic->managerId != $account->id
            && $this->getCoreMemberService()->isCoreMember($topic->id, $account->id) == false)) {
            return $this->errorsResponse(TopicError::YouAreNotManager());
        }

        if ($this->getStickyService()->countStickies($topic->id) >= 3) {
            return $this->errorsResponse(PostError::StickyPostExceedLimit());
        }

        $this->getStickyService()->stickPost($postId);
        return $this->sucessResponse();
    }

    /**
     * @Route("/unstick")
     * @Method("POST")
     * @ApiDoc(
     *   section="post",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="pid", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function unstickPost(Request $request) {
        $account = $this->requireAuth($request);
        $postId = $this->requireId($request->request, 'pid');

        $post = $this->post()->fetchOne($postId);
        if ($post == null) {
            return $this->errorsResponse(PostError::PostNotExist($postId));
        }

        $topic = $this->topic()->fetchOne($post->topicId);
        if ($topic == null || ($topic->managerId != $account->id
            && $this->getCoreMemberService()->isCoreMember($topic->id, $account->id) == false)) {
            return $this->errorsResponse(TopicError::YouAreNotManager());
        }

        $this->getStickyService()->unstickPost($postId);
        return $this->sucessResponse();
    }

    /**
     * @return StickyService
     */
    public function getStickyService() {
        return $this->get('lychee.module.post.sticky');
    }

    /**
     * @return \Lychee\Module\Topic\CoreMember\TopicCoreMemberService
     */
    public function getCoreMemberService() {
        return $this->get('lychee.module.topic.core_member');
    }

    private function filterUndeletedPosts($data) {
        return array_values(array_filter($data, function($p){
            return !isset($p['deleted']) || $p['deleted'] == false;
        }));
    }



    /**
     *
     * ### 返回内容 ###
     * ```json
     * {
     * "posts": [
     * {
     * "id": "130046756321281",
     * "topic": {
     * "id": "29579",
     * "create_time": 1433201074,
     * "title": "动漫表情",
     * "description": "没有表情怎么鱼块的勾搭！\n表情赛高！\n\n感谢大家的入驻支持！本次元本周会有专题话题，欢迎大家踊跃参加嗷！",
     * "index_image": "http://qn.ciyocon.com/ad268345-6d9b-4e17-8f49-08c2c05ecc21",
     * "post_count": 6008,
     * "followers_count": 44666,
     * "private": false,
     * "apply_to_follow": false,
     * "color": "009dff",
     * "certified": false,
     * "following": false,
     * "manager": {
     * "id": "73708"
     * }
     * },
     * "create_time": 1528079851,
     * "type": "picture",
     * "content": "#樱桃小丸子# ​​",
     * "image_url": "http://qn.ciyocon.com/upload/FhyItpMbw1m0SN84NfS_noTI6N8R",
     * "annotation": {
     * "image_height": 590,
     * "image_width": 801,
     * "multi_original_photos": [
     * "http://qn.ciyocon.com/upload/Fo5l9Rz0WQc99AhBRQL60aXvNqOr",
     * "http://qn.ciyocon.com/upload/Fi3WvVRaKtyB7kq8KjJmtI7HFOBM"
     * ],
     * "multi_photos": [
     * "http://qn.ciyocon.com/upload/FhyItpMbw1m0SN84NfS_noTI6N8R",
     * "http://qn.ciyocon.com/upload/FpjJl6-m6h0cd2rCGcPAQMokwYsC"
     * ],
     * "multi_photo_heights": [590,463],
     * "multi_photo_widths": [801,600],
     * "original_url": "http://qn.ciyocon.com/upload/FhyItpMbw1m0SN84NfS_noTI6N8R",
     * "video_cover_height": 0,
     * "video_cover_width": 0
     * },
     * "author": {
     * "id": "2012777",
     * "nickname": "麽麽酱",
     * "avatar_url": "http://qn.ciyocon.com/upload/FoAKW2Ovtrd_zwHKoNQD-bpNyLmU",
     * "gender": "female",
     * "level": 44,
     * "signature": "我是个麽麽酱～\n有事请联系QQ:859867015",
     * "ciyoCoin": "0.00"
     * },
     * "latest_likers": [],
     * "liked_count": 27,
     * "commented_count": 0,
     * "reposted_count": 0,
     * "liked": false,
     * "favorited": false
     * }
     * ],
     * "next_cursor": "130046756321281" //用于查询下一页的帖子id
     * }
     * ```
     *
     * @Route("/hots/topic")
     * @Method("GET")
     * @ApiDoc(
     *   description="获取指定次元下的热门帖子",
     *   section="post",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="tid", "dataType"="integer", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function getHotPostsByTopicAction(Request $request) {
        $account = $this->getAuthUser($request);
        $topicId = $this->requireId($request->query, 'tid');
        $topic = $this->topic()->fetchOne($topicId);
        if ($topic == null || $topic->deleted) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }
        if ($topic->private && $account != null && $this->topicFollowing()->isFollowing($account->id, $topicId) == false) {
            return $this->errorsResponse(TopicError::RequireFollow());
        }
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 100);

        $client = $request->query->get(self::CLIENT_PLATFORM_KEY, 'android');
        $hasStickyPost = false;
        if ($cursor < 1000000) {
            $stickyPostIds = $this->getStickyService()->getStickyPostIds($topicId, $cursor, $count, $nextCursor);
            if (!empty($stickyPostIds)) {
                $hasStickyPost = true;
            }
            $remainCount = $count - count($stickyPostIds);
            if ($remainCount > 0) {
                $sourcePostIds = $this->post()->fetchIdsByTopicIdOrderByHotForClient(
                    $topicId, $cursor, $remainCount, $nextCursor, $client
                );
                $postIds = array_values(array_unique(array_merge($stickyPostIds, $sourcePostIds)));
            } else {
                $postIds = $stickyPostIds;
            }
        } else {
            //因为当前的设置中,每个次元最多可以置顶3个帖子.所以这里直接把置顶的帖子从余下的时间线中剔除.
            $stickyPostIds = $this->getStickyService()->getStickyPostIds($topicId, 0, 10, $nextCursor);
            $postIds = $this->post()->fetchIdsByTopicIdOrderByHotForClient(
                $topicId, $cursor, $count, $nextCursor, $client
            );
            $postIds = ArrayUtility::diffValue($postIds, $stickyPostIds);
        }


        $synthesizer = $this->getSynthesizerBuilder()
            ->buildListPostSynthesizer($postIds, $account ? $account->id : 0);
        $posts = $synthesizer->synthesizeAll();
        $posts = $this->filterUndeletedPosts($posts);
        if ($hasStickyPost) {
            foreach ($posts as &$post) {
                if (in_array($post['id'], $stickyPostIds)) {
                        $post['sticky'] = true;
                }
            }
            unset($post);
        }

        $exposedPostIdsAndTopicIds = array();
        foreach ($posts as $p) {
            $exposedPostIdsAndTopicIds[] = [$p['id'], $p['topic']['id']];
        }
        $this->get('lychee.module.post.exposure_recorder')->recordPostsExposure($exposedPostIdsAndTopicIds);

        return $this->arrayResponse(
            'posts', $posts, $nextCursor
        );
    }

    /**
     *
     * ### 返回内容 ###
     * ```json
     * {
     * "posts": [
     * {
     * "id": "130046756321281",
     * "topic": {
     * "id": "29579",
     * "create_time": 1433201074,
     * "title": "动漫表情",
     * "description": "没有表情怎么鱼块的勾搭！\n表情赛高！\n\n感谢大家的入驻支持！本次元本周会有专题话题，欢迎大家踊跃参加嗷！",
     * "index_image": "http://qn.ciyocon.com/ad268345-6d9b-4e17-8f49-08c2c05ecc21",
     * "post_count": 6008,
     * "followers_count": 44666,
     * "private": false,
     * "apply_to_follow": false,
     * "color": "009dff",
     * "certified": false,
     * "following": false,
     * "manager": {
     * "id": "73708"
     * }
     * },
     * "create_time": 1528079851,
     * "type": "picture",
     * "content": "#樱桃小丸子# ​​",
     * "image_url": "http://qn.ciyocon.com/upload/FhyItpMbw1m0SN84NfS_noTI6N8R",
     * "annotation": {
     * "image_height": 590,
     * "image_width": 801,
     * "multi_original_photos": [
     * "http://qn.ciyocon.com/upload/Fo5l9Rz0WQc99AhBRQL60aXvNqOr",
     * "http://qn.ciyocon.com/upload/Fi3WvVRaKtyB7kq8KjJmtI7HFOBM"
     * ],
     * "multi_photos": [
     * "http://qn.ciyocon.com/upload/FhyItpMbw1m0SN84NfS_noTI6N8R",
     * "http://qn.ciyocon.com/upload/FpjJl6-m6h0cd2rCGcPAQMokwYsC"
     * ],
     * "multi_photo_heights": [590,463],
     * "multi_photo_widths": [801,600],
     * "original_url": "http://qn.ciyocon.com/upload/FhyItpMbw1m0SN84NfS_noTI6N8R",
     * "video_cover_height": 0,
     * "video_cover_width": 0
     * },
     * "author": {
     * "id": "2012777",
     * "nickname": "麽麽酱",
     * "avatar_url": "http://qn.ciyocon.com/upload/FoAKW2Ovtrd_zwHKoNQD-bpNyLmU",
     * "gender": "female",
     * "level": 44,
     * "signature": "我是个麽麽酱～\n有事请联系QQ:859867015",
     * "ciyoCoin": "0.00"
     * },
     * "latest_likers": [],
     * "liked_count": 27,
     * "commented_count": 0,
     * "reposted_count": 0,
     * "liked": false,
     * "favorited": false
     * }
     * ],
     * "next_cursor": "130046756321281" //用于查询下一页的帖子id
     * }
     * ```
     *
     * @Route("/newly/topic")
     * @Method("GET")
     * @ApiDoc(
     *   description="获取指定次元下的热门帖子",
     *   section="post",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="tid", "dataType"="integer", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function getNewLyPostsByTopicAction(Request $request) {
        $account = $this->getAuthUser($request);
        $topicId = $this->requireId($request->query, 'tid');
        $topic = $this->topic()->fetchOne($topicId);
        if ($topic == null || $topic->deleted) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }
        if ($topic->private && $account != null && $this->topicFollowing()->isFollowing($account->id, $topicId) == false) {
            return $this->errorsResponse(TopicError::RequireFollow());
        }
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 100);

        $client = $request->query->get(self::CLIENT_PLATFORM_KEY, 'android');
        $postIds = $this->post()->fetchIdsByTopicIdForClient(
            $topicId, $cursor, $count, $nextCursor, $client
        );

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildListPostSynthesizer($postIds, $account ? $account->id : 0);
        $posts = $synthesizer->synthesizeAll();
        $posts = $this->filterUndeletedPosts($posts);

        $exposedPostIdsAndTopicIds = array();
        foreach ($posts as $p) {
            $exposedPostIdsAndTopicIds[] = [$p['id'], $p['topic']['id']];
        }
        $this->get('lychee.module.post.exposure_recorder')->recordPostsExposure($exposedPostIdsAndTopicIds);

        return $this->arrayResponse(
            'posts', $posts, $nextCursor
        );
    }

    /**
     *
     * ```json
     * {
     * "posts": [
     * {
     * "id": "128916531779585",
     * "topic": {
     * "id": "54687",
     * "create_time": 1521254500,
     * "title": "ciyo不存在的次元",
     * "description": "啊啊啊简介啊啊啊",
     * "index_image": "http://qn.ciyocon.com/img-theme-0011.png",
     * "post_count": 51,
     * "followers_count": 13,
     * "private": false,
     * "apply_to_follow": false,
     * "color": "13df83",
     * "certified": false,
     * "link": "Www.baidu.com",
     * "link_title": "测试",
     * "following": true,
     * "manager": {
     * "id": "2422630"
     * }
     * },
     * "create_time": 1527001985,
     * "type": "picture",
     * "content": "该帖子安卓可以看，不给ios看",
     * "author": {
     * "id": "2249256",
     * "nickname": "疯狂的茶几",
     * "avatar_url": "http://qn.ciyocon.com/upload/FlmIIMAMUKt7ci-Q9RLh3Di4hz4H",
     * "gender": "male",
     * "level": 13,
     * "signature": "人生是一张茶几",
     * "ciyoCoin": "0.00",
     * "my_follower": false,
     * "my_followee": true
     * },
     * "latest_likers": [],
     * "liked_count": 0,
     * "commented_count": 0,
     * "reposted_count": 0,
     * "liked": false,
     * "favorited": false
     * },
     * {
     * "id": "128915915507713",
     * "topic": {
     * "id": "54687",
     * "create_time": 1521254500,
     * "title": "ciyo不存在的次元",
     * "description": "啊啊啊简介啊啊啊",
     * "index_image": "http://qn.ciyocon.com/img-theme-0011.png",
     * "post_count": 51,
     * "followers_count": 13,
     * "private": false,
     * "apply_to_follow": false,
     * "color": "13df83",
     * "certified": false,
     * "link": "Www.baidu.com",
     * "link_title": "测试",
     * "following": true,
     * "manager": {
     * "id": "2422630"
     * }
     * },
     * "create_time": 1527001397,
     * "type": "picture",
     * "content": "试试水",
     * "author": {
     * "id": "2249256",
     * "nickname": "疯狂的茶几",
     * "avatar_url": "http://qn.ciyocon.com/upload/FlmIIMAMUKt7ci-Q9RLh3Di4hz4H",
     * "gender": "male",
     * "level": 13,
     * "signature": "人生是一张茶几",
     * "ciyoCoin": "0.00",
     * "my_follower": false,
     * "my_followee": true
     * },
     * "latest_likers": [],
     * "liked_count": 0,
     * "commented_count": 0,
     * "reposted_count": 0,
     * "liked": false,
     * "favorited": false
     * }
     * ],
     * "next_cursor": "128915915507713"
     * }
     * ```
     *
     * @Route("/plain/timeline/user")
     * @Method("GET")
     * @ApiDoc(
     *   description="获取指定用户发布的图文帖子，从新到旧排序",
     *   section="post",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="uid", "dataType"="integer", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function listPlainsByUserAction(Request $request) {
        $account = $this->getAuthUser($request);
        $userId = $this->requireId($request->query, 'uid');
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 100);

        $client = $request->query->get(self::CLIENT_PLATFORM_KEY, 'android');
        if ($account && $userId == $account->id) {
            //查看自己的帖子列表时显示全部帖子
            $postIds = $this->post()->fetchPlainIdsByAuthorId(
                $userId, $cursor, $count, $nextCursor
            );
        } else {
            //查看别人的帖子列表时只显示在公开次元内的
            $postIds = $this->post()->fetchPlainIdsByAuthorIdInPublicTopicForClient(
                $userId, $cursor, $count, $nextCursor, $client
            );
        }

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildListPostSynthesizer($postIds, $account ? $account->id : 0);
        $postInfos = $synthesizer->synthesizeAll();
        $postInfos = $this->filterUndeletedPosts($postInfos);
        return $this->arrayResponse(
            'posts', $postInfos, $nextCursor
        );
    }


    /**
     *
     *
     * ### 返回内容 ###
     *
     * ```json
     *{
     * "result": true,
     * }
     *
     * ```
     *
     * @Route("/try_create")
     * @Method("POST")
     * @ApiDoc(
     *   description="用于判断是否具备可以发帖的条件",
     *   section="post",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true}
     *   }
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */

    public function tryCreateAction(Request $request)
    {
        $this->requirePhoneAuth($request, true);
        return $this->dataResponse(['result'=>true]);
    }


}
