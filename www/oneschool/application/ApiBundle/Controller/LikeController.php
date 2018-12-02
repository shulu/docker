<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Bundle\ApiBundle\Error\CommentError;
use Lychee\Bundle\ApiBundle\Error\PostError;
use Lychee\Bundle\ApiBundle\Error\TopicError;
use Lychee\Module\Account\Mission\MissionType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;

class LikeController extends Controller {

    /**
     * @Route("/post/likers")
     * @Method("GET")
     * @ApiDoc(
     *   section="like",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="pid", "dataType"="integer", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function getPostLikerAction(Request $request) {
        $account = $this->getAuthUser($request);
        $postId = $this->requireId($request->query, 'pid');
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 100);

        $likerIds = $this->like()->fetchPostLikerIds(
            $postId, $cursor, $count, $nextCursor
        );
        $synthesizer = $this->getSynthesizerBuilder()
            ->buildUserSynthesizer($likerIds, $account ? $account->id : 0);
        return $this->arrayResponse('users', $synthesizer->synthesizeAll(), $nextCursor);
    }

    /**
     * @Route("/post/like")
     * @Method("POST")
     * @ApiDoc(
     *   section="like",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="pid", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function likePostAction(Request $request) {
        $account = $this->requireAuth($request);
        $postId = $this->requireId($request->request, 'pid');

        $post = $this->post()->fetchOne($postId);
        if ($post === null || $post->deleted == true) {
            return $this->errorsResponse(PostError::PostNotExist($postId));
        }

        if ($post->topicId) {
            $topic = $this->topic()->fetchOne($post->topicId);
            if ($topic && $topic->private
                && $this->topicFollowing()->isFollowing($account->id, $post->topicId) == false) {
                return $this->errorsResponse(TopicError::RequireFollow());
            }
        }

        $this->like()->likePost($account->id, $postId, $likedBefore);
        $counting = $this->post()->fetchOneCounting($postId);
        $response = array('liked_count' => $counting->likedCount);

        if ($likedBefore === false && $post->authorId != $account->id) {
            $missionResult = $this->missionManager()->userAccomplishMission($account->id, MissionType::DAILY_LIKE_POST);
            $this->injectMissionResult($response, $missionResult);
        }

        return $this->dataResponse($response);
    }

    /**
     * @Route("/post/unlike")
     * @Method("POST")
     * @ApiDoc(
     *   section="like",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="pid", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function cancelLikePostAction(Request $request) {
        $account = $this->requireAuth($request);
        $postId = $this->requireId($request->request, 'pid');

        $post = $this->post()->fetchOne($postId);
        if ($post === null) {
            return $this->errorsResponse(PostError::PostNotExist($postId));
        }

        $this->like()->cancelLikePost($account->id, $postId);
        $counting = $this->post()->fetchOneCounting($postId);
        return $this->dataResponse(array(
            'liked_count' => $counting->likedCount
        ));
    }


    /**
     *
     * ```json
     * {
     * "liked_count": "1111"
     * }
     * ```
     *
     * @Route("/comment/like")
     * @Method("POST")
     * @ApiDoc(
     *   section="like",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="cid", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function likeCommentAction(Request $request) {

        $account = $this->requireAuth($request);
        $commentId = $this->requireId($request->request, 'cid');

        $comment = $this->comment()->fetchOne($commentId);
        if ($comment === null || $comment->deleted == true) {
            return $this->errorsResponse(CommentError::CommentNotExist($commentId));
        }

        $this->like()->likeComment($account->id, $commentId, $likedBefore);

        $refechedComment = $this->comment()->fetchOne($commentId);
        return $this->dataResponse(array(
            'liked_count' => $refechedComment->likedCount
        ));
    }

    /**
     *
     * ```json
     * {
     * "liked_count": "1111"
     * }
     * ```
     * @Route("/comment/unlike")
     * @Method("POST")
     * @ApiDoc(
     *   section="like",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="cid", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function cancelLikeCommentAction(Request $request) {

        $account = $this->requireAuth($request);
        $commentId = $this->requireId($request->request, 'cid');

        $comment = $this->comment()->fetchOne($commentId);
        if ($comment === null || $comment->deleted == true) {
            return $this->errorsResponse(CommentError::CommentNotExist($commentId));
        }

        $this->like()->cancelLikeComment($account->id, $commentId);

        $refechedComment = $this->comment()->fetchOne($commentId);
        return $this->dataResponse(array(
            'liked_count' => $refechedComment->likedCount
        ));
    }

} 