<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Bundle\ApiBundle\Error\AccountError;
use Lychee\Bundle\ApiBundle\Error\CommentError;
use Lychee\Bundle\ApiBundle\Error\CommonError;
use Lychee\Bundle\ApiBundle\Error\PostError;
use Lychee\Bundle\ApiBundle\Error\RelationError;
use Lychee\Bundle\ApiBundle\Error\TopicError;
use Lychee\Bundle\CoreBundle\Entity\User;
use Lychee\Bundle\CoreBundle\Geo\BaiduGeocoder;
use Lychee\Bundle\CoreBundle\Validator\Constraints\NotSensitiveWord;
use Lychee\Module\Account\Mission\MissionType;
use Lychee\Module\Recommendation\RecommendationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Lychee\Component\Foundation\StringUtility;
use Lychee\Bundle\ApiBundle\AntiSpam\SpamChecker;
use Lychee\Bundle\ApiBundle\Error\ErrorsException;

/**
 * @Route("/comment");
 */
class CommentController extends Controller {

    /**
     *
     * [错误码说明](http://gitlab.ciyo.cn/ciyocon/lychee-server/wikis/服务/接口#接口公共错误码)
     *
     * @Route("/create")
     * @Method("POST")
     * @ApiDoc(
     *   section="comment",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="pid", "dataType"="integer", "required"=true},
     *     {"name"="content", "dataType"="string", "required"=false,
     *       "description"="content和image_url两者至少有一个是非空的"},
     *     {"name"="image_url", "dataType"="string", "required"=false,
     *       "description"="content和image_url两者至少有一个是非空的"},
     *     {"name"="annotation", "dataType"="string", "required"=false,
     *       "description"="客户端可以保存一定的数据到这个参数里，客户端获取数据时，将返回同样的数据。
     *       只能是json形式，不能超过1024个字符。"}
     *   }
     * )
     */
    public function createAction(Request $request) {
        $account = $this->requirePhoneAuth($request);

        $postId = $this->requireInt($request, 'pid');
        $content = $request->request->get('content');
        $imageUrl = $request->request->get('image_url');

	    $ip = $request->getClientIp();
        if(!$this->account()->isUserInVip($account->id)){
            $this->antiSpam($account, $content, $ip);
        }

        if (StringUtility::isUtf8Encoding($content) === false) {
            return $this->errorsResponse(CommonError::PleaseUseUTF8());
        }
        if (empty($content) && empty($imageUrl)) {
            return $this->errorsResponse(CommonError::ParameterMissing('content'));
        }
        $post = $this->post()->fetchOne($postId);
        if ($post === null || $post->deleted) {
            return $this->errorsResponse(PostError::PostNotExist($postId));
        }
        
        if ($post->topicId) {
            $topic = $this->topic()->fetchOne($post->topicId);
            if ($topic && $topic->private
                && $this->topicFollowing()->isFollowing($account->id, $post->topicId) == false) {
                return $this->errorsResponse(TopicError::RequireFollow());
            }
        }

        if ($this->relation()->userBlackListHas($post->authorId, $account->id)) {
            return $this->errorsResponse(RelationError::YouBeingBlocked());
        }

        if (mb_strlen($content, 'utf8') > 200 || $this->isValueValid($content, array(
            new NotSensitiveWord()
        )) === false) {
            return $this->errorsResponse(CommentError::ContentInvalid());
        }


        $district = $this->fetchAddressByIp($ip);

        $annotation = $request->request->get('annotation');
        if ($annotation) {
            if (strlen($annotation) > 1024) {
                return $this->errorsResponse(CommentError::AnnotationTooLong(1024));
            }
            if (StringUtility::isJsonString($annotation) === false) {
                return $this->errorsResponse(CommentError::AnnotationError());
            }
        }

        $comment = $this->comment()->create(
            $postId, $account->id, $content,
            null, $imageUrl, $ip, $district,
            $annotation
        );

        $missionResult = $this->missionManager()->userAccomplishMission($account->id, MissionType::DAILY_COMMENT);

        $this->updateUserDevice($request, $account->id);

        $synthesizer = $this->getSynthesizerBuilder()->buildSimpleCommentSynthesizer(array($comment));

        $response = $synthesizer->synthesizeOne($comment->id);
        $this->injectMissionResult($response, $missionResult);
        return $this->dataResponse($response);
    }

    /**
     * @param User $user
     * @param string $content
     * @throws ErrorsException
     */
    private function antiSpam($user, $content, $ip) {

	    $ipBlocker = $this->get('lychee_api.ip_blocker');
	    if ($ipBlocker->checkAndUpdate($ip, SpamChecker::ACTION_COMMENT) == false) {
		    throw new ErrorsException(CommonError::SystemBusy());
	    }

        $action = SpamChecker::ACTION_COMMENT.'_60s';
        $isSpam = $this->getSpamChecker()->check($user->id, 60, 4, $action);

        if($isSpam == false){
            $action = SpamChecker::ACTION_COMMENT.'_600s';
            $isSpam = $this->getSpamChecker()->check($user->id, 600, 20, $action);
        }

	    if($isSpam == false){
		    $action = SpamChecker::ACTION_COMMENT.'_86400s';
		    $mayBeSpam = $this->getSpamChecker()->check($user->id, 86400, 20, $action);

		    if($mayBeSpam){
			    $isSpam = $this->isUserCommentSimilar($user->id, 20);
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
	 * @param int $count
	 * @return bool
	 */
	private function isUserCommentSimilar($userId, $count) {

		$commentIds = $this->comment()->fetchIdsByAuthorId($userId, 0, $count);
		if (count($commentIds) != $count) {
			return false;
		}

		$comments = array_values($this->comment()->fetch($commentIds));
		if (count($comments) < $count) {
			return false;
		}

		$similarCount = 0;
		$strLength = 20;
		for($i=0;$i<$count;$i++){

			$comment1 = $comments[$i];

			$contentA = $comment1->content;
			if(strlen($contentA) < $strLength){
				continue;
			}

			for($k=$i+1;$k<$count;$k++){

				$comment2 = $comments[$k];
				$contentB = $comment2->content;

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
    }

    /**
     * @Route("/reply")
     * @Method("POST")
     * @ApiDoc(
     *   section="comment",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="replied_id", "dataType"="integer", "required"=true,
     *       "description"="回复的评论id"},
     *     {"name"="content", "dataType"="string", "required"=false,
     *       "description"="content和image_url两者至少有一个是非空的"},
     *     {"name"="image_url", "dataType"="string", "required"=false,
     *       "description"="content和image_url两者至少有一个是非空的"},
     *     {"name"="annotation", "dataType"="string", "required"=false,
     *       "description"="客户端可以保存一定的数据到这个参数里，客户端获取数据时，将返回同样的数据。
     *       只能是json形式，不能超过1024个字符。"}
     *   }
     * )
     */
    public function replyAction(Request $request) {
        $account = $this->requireAuth($request);

        $repliedId = $this->requireInt($request, 'replied_id');
        $content = $request->request->get('content');
        $imageUrl = $request->request->get('image_url');

	    $ip = $request->getClientIp();
        if(!$this->account()->isUserInVip($account->id)){
	        $this->antiSpam($account, $content, $ip);
        }

        if (StringUtility::isUtf8Encoding($content) === false) {
            return $this->errorsResponse(CommonError::PleaseUseUTF8());
        }
        if (empty($content) && empty($imageUrl)) {
            return $this->errorsResponse(CommonError::ParameterMissing('content'));
        }
        if (mb_strlen($content, 'utf8') > 200 || $this->isValueValid($content, array(
                new NotSensitiveWord()
            )) === false) {
            return $this->errorsResponse(CommentError::ContentInvalid());
        }

        $repliedComment = $this->comment()->fetchOne($repliedId);
        if ($repliedComment === null || $repliedComment->deleted) {
            return $this->errorsResponse(CommentError::CommentNotExist($repliedId));
        }
        if ($repliedComment->authorId == $account->id) {
            return $this->errorsResponse(CommentError::CanNotReplyYourself());
        }

        $postId = $repliedComment->postId;
        $post = $this->post()->fetchOne($postId);
        if ($post === null || $post->deleted) {
            return $this->errorsResponse(PostError::PostNotExist($postId));
        }

        if (
            $this->relation()->userBlackListHas($post->authorId, $account->id)
            || $this->relation()->userBlackListHas($repliedComment->authorId, $account->id)
        ) {
            return $this->errorsResponse(RelationError::YouBeingBlocked());
        }

        $ip = $request->getClientIp();
        $district = $this->fetchAddressByIp($ip);

        $annotation = $request->request->get('annotation');
        if ($annotation) {
            if (strlen($annotation) > 1024) {
                return $this->errorsResponse(CommentError::AnnotationTooLong(1024));
            }
            if (StringUtility::isJsonString($annotation) === false) {
                return $this->errorsResponse(CommentError::AnnotationError());
            }
        }

        $comment = $this->comment()->create(
            $postId, $account->id, $content,
            $repliedId, $imageUrl, $ip, $district, $annotation
        );

        $synthesizer = $this->getSynthesizerBuilder()->buildSimpleCommentSynthesizer(array($comment));
        return $this->dataResponse($synthesizer->synthesizeOne($comment->id));
    }

    private function fetchAddressByIp($ip) {
        $geocoder = new BaiduGeocoder('p9IGaqRYyAwkG1oKQBTwnQ8R');
        return $geocoder->getAddressWithIp($ip);
    }

    /**
     * @Route("/delete")
     * @Method("POST")
     * @ApiDoc(
     *   section="comment",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="cid", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function deleteAction(Request $request) {
        $account = $this->requireAuth($request);
        $commentId = $this->requireInt($request, 'cid');
        $comment = $this->comment()->fetchOne($commentId);
        if ($comment === null) {
            return $this->errorsResponse(CommentError::CommentNotExist($commentId));
        }
        if ($comment->authorId !== $account->id) {
            $post = $this->post()->fetchOne($comment->postId);
            if ($post == null || $post->authorId != $account->id) {
                //post author can delete comments
                if ($comment->deleted === false) {
                    return $this->errorsResponse(CommentError::NotYourOwnComment());
                } else {
                    return $this->errorsResponse(CommentError::CommentNotExist($commentId));
                }
            }
        }

        if ($comment->deleted) {
            return $this->sucessResponse();
        } else {
            try {
                $this->comment()->delete($comment->id);
                return $this->sucessResponse();
            } catch (\Exception $e) {
                throw $e;
            }
        }
    }

    /**
     *
     * ```json
     * {
     * "comments": [
     * {
     * "id": "129912692427777",
     * "author": {
     * "id": "266649",
     * "nickname": "啊东",
     * "avatar_url": "http://qn.ciyocon.com/upload/FpScP2M_iWQFLowgTzFCkL0FITr7",
     * "gender": "male",
     * "level": 50,
     * "signature": "",
     * "ciyoCoin": "80.00"
     * },
     * "post": {
     * "id": "129881663982593"
     * },
     * "create_time": 1527951998,
     * "district": "广东省揭阳市",
     * "content": "唉，学长你发视频的那个游戏是什么游戏",
     * "liked":true,
     * "liked_count":1
     * },
     * {
     * "id": "129914395518977",
     * "author": {
     * "id": "1645074",
     * "nickname": "大石学长",
     * "avatar_url": "http://qn.ciyocon.com/upload/Fgf-OXnYaAcfAQXwaHIpHs3JW1Hw",
     * "gender": "male",
     * "level": 34,
     * "signature": "声出高楼人独坐，明月知我，赠与狂风和（四声）。",
     * "ciyoCoin": "0.00",
     * "certificate": "次元社最帅的学长"
     * },
     * "post": {
     * "id": "129881663982593"
     * },
     * "replyed": {
     * "id": "129912692427777",
     * "author": {
     * "id": "266649",
     * "nickname": "啊东",
     * "avatar_url": "http://qn.ciyocon.com/upload/FpScP2M_iWQFLowgTzFCkL0FITr7",
     * "gender": "male",
     * "level": 50,
     * "signature": "",
     * "ciyoCoin": "80.00"
     * },
     * "post": {
     * "id": "129881663982593"
     * },
     * "create_time": 1527951998,
     * "district": "广东省揭阳市",
     * "content": "唉，学长你发视频的那个游戏是什么游戏"
     * },
     * "create_time": 1527953622,
     * "district": "吉林省松原市",
     * "content": "啊？啥？",
     * "liked":true,
     * "liked_count":1
     * }
     * ],
     * "next_cursor": "129914395518977"
     * }
     * ```
     *
     * @Route("/timeline/post")
     * @Method("GET")
     * @ApiDoc(
     *   section="comment",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="pid", "dataType"="integer", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function listByPostAction(Request $request) {
        $postId = $this->requireId($request->query, 'pid');
        $post = $this->post()->fetchOne($postId);
        if ($post === null || $post->deleted) {
            return $this->errorsResponse(PostError::PostNotExist($postId));
        }

        $accountId = 0;
        try {
            $account = $this->getAuthUser($request);
            if ($account) {
                $accountId = $account->id;
            }
        } catch (\Exception $e) {}

        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 100);
        $commentIds = $this->comment()->fetchIdsByPostId(
            $postId, $cursor, $count, $nextCursor
        );

        $synthesizer = $this->getSynthesizerBuilder()->buildSimpleCommentSynthesizer($commentIds, $accountId);
        return $this->arrayResponse('comments', $synthesizer->synthesizeAll(), $nextCursor);
    }

    /**
     *
     * ```json
     * {
     * "comments": [
     * {
     * "id": "129912692427777",
     * "author": {
     * "id": "266649",
     * "nickname": "啊东",
     * "avatar_url": "http://qn.ciyocon.com/upload/FpScP2M_iWQFLowgTzFCkL0FITr7",
     * "gender": "male",
     * "level": 50,
     * "signature": "",
     * "ciyoCoin": "80.00"
     * },
     * "post": {
     * "id": "129881663982593"
     * },
     * "create_time": 1527951998,
     * "district": "广东省揭阳市",
     * "content": "唉，学长你发视频的那个游戏是什么游戏",
     * "liked":true,
     * "liked_count":1
     * },
     * {
     * "id": "129914395518977",
     * "author": {
     * "id": "1645074",
     * "nickname": "大石学长",
     * "avatar_url": "http://qn.ciyocon.com/upload/Fgf-OXnYaAcfAQXwaHIpHs3JW1Hw",
     * "gender": "male",
     * "level": 34,
     * "signature": "声出高楼人独坐，明月知我，赠与狂风和（四声）。",
     * "ciyoCoin": "0.00",
     * "certificate": "次元社最帅的学长"
     * },
     * "post": {
     * "id": "129881663982593"
     * },
     * "replyed": {
     * "id": "129912692427777",
     * "author": {
     * "id": "266649",
     * "nickname": "啊东",
     * "avatar_url": "http://qn.ciyocon.com/upload/FpScP2M_iWQFLowgTzFCkL0FITr7",
     * "gender": "male",
     * "level": 50,
     * "signature": "",
     * "ciyoCoin": "80.00"
     * },
     * "post": {
     * "id": "129881663982593"
     * },
     * "create_time": 1527951998,
     * "district": "广东省揭阳市",
     * "content": "唉，学长你发视频的那个游戏是什么游戏"
     * },
     * "create_time": 1527953622,
     * "district": "吉林省松原市",
     * "content": "啊？啥？",
     * "liked":true,
     * "liked_count":1
     * }
     * ],
     * "next_cursor": "129914395518977"
     * }
     * ```
     *
     * @Route("/hottest/post")
     * @Method("GET")
     * @ApiDoc(
     *   section="comment",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="pid", "dataType"="integer", "required"=true,
     *       "description"="帖子id"}
     *   }
     * )
     */
    public function listHottestByPostAction(Request $request) {
        $postId = $this->requireId($request->query, 'pid');
        $post = $this->post()->fetchOne($postId);
        if ($post === null || $post->deleted) {
            return $this->errorsResponse(PostError::PostNotExist($postId));
        }

        $accountId = 0;
        try {
            $account = $this->getAuthUser($request);
            if ($account) {
                $accountId = $account->id;
            }
        } catch (\Exception $e) {}

        $counting = $this->post()->fetchOneCounting($postId);
        $limit = round($counting->commentedCount*0.1);
        if ($limit<=0) {
            return $this->arrayResponse('comments', [], 0);
        }
        if ($limit>10) {
            $limit = 10;
        }
        $commentIds = $this->comment()->fetchHotIdsByPostId($postId, 1, 0, $limit);
        $synthesizer = $this->getSynthesizerBuilder()->buildSimpleCommentSynthesizer($commentIds, $accountId);
        return $this->arrayResponse('comments', $synthesizer->synthesizeAll(), 0);
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
     *   description="用于判断是否具备可以发评论的条件",
     *   section="comment",
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
