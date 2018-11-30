<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Lsw\MemcacheBundle\Cache\MemcacheInterface;
use Lychee\Bundle\ApiBundle\Error\AccountError;
use Lychee\Bundle\ApiBundle\Error\AuthenticationError;
use Lychee\Bundle\ApiBundle\Error\CommonError;
use Lychee\Bundle\ApiBundle\Error\RelationError;
use Lychee\Component\GraphStorage\FollowingResolver;
use Lychee\Constant;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/relation")
 */
class RelationController extends Controller {
    /**
     * @Route("/followers")
     * @Method("GET")
     * @ApiDoc(
     *   section="relation",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="uid", "dataType"="integer", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false,
     *       "description"="根据cursor来返回指定批次的数据，可以简单理解为页码，起始为0"},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过500"}
     *   }
     * )
     */
    public function getFollowersAction(Request $request) {
        $account = $this->getAuthUser($request);
        $targetId = $this->requireId($request->query, 'uid');
        if ($targetId == Constant::CIYUANJIANG_ID) {
            $followerIds = array(Constant::CIYUANJIANG_ID);
            $nextCursor = 0;
        } else {
            list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 500);

            $followerIds = $this->relation()->fetchFollowerIdsByUserId(
                $targetId, $cursor, $count, $nextCursor
            );
        }

        if (count($followerIds) > 0) {
            $synthesizer = $this->getSynthesizerBuilder()->buildUserSynthesizer(
                $followerIds,
                $account ? $account->id : 0
            );
            $followerInfos = $synthesizer->synthesizeAll();
        } else {
            $followerInfos = array();
        }

        return $this->arrayResponse(
            'users', $followerInfos, $nextCursor
        );
    }

    /**
     * @Route("/followees")
     * @Method("GET")
     * @ApiDoc(
     *   section="relation",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="uid", "dataType"="integer", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false,
     *       "description"="根据cursor来返回指定批次的数据，可以简单理解为页码，起始为0"},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过500"}
     *   }
     * )
     */
    public function getFolloweesAction(Request $request) {
        $account = $this->getAuthUser($request);
        $targetId = $this->requireId($request->query, 'uid');
        if ($targetId == Constant::CIYUANJIANG_ID) {
            $followeeIds = array(Constant::CIYUANJIANG_ID);
            $nextCursor = 0;
        } else {
            list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 500);

            $relation = $this->relation();
            $followeeIds = $relation->fetchFolloweeIdsByUserId(
                $targetId, $cursor, $count, $nextCursor
            );
        }

        if (count($followeeIds) > 0) {
            $synthesizer = $this->getSynthesizerBuilder()->buildUserSynthesizer(
                $followeeIds,
                $account ? $account->id : 0
            );
            $followeeInfos = $synthesizer->synthesizeAll();
        } else {
            $followeeInfos = array();
        }

        return $this->arrayResponse(
            'users', $followeeInfos, $nextCursor
        );
    }

    /**
     * @Route("/follow")
     * @Method("POST")
     * @ApiDoc(
     *   section="relation",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="uid", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function followUserAction(Request $request) {
        $account = $this->requireAuth($request);
        $targetId = $this->requireId($request->request, 'uid');

        if ($account->id === $targetId) {
            return $this->failureResponse();
        }

        $targetUser = $this->account()->fetchOne($targetId);
        if ($targetUser === null) {
            return $this->errorsResponse(AccountError::UserNotExist($targetId));
        }
        if ($this->relation()->isUserFollowingAnother($account->id, $targetId)) {
            return $this->sucessResponse();
        }
        if ($this->relation()->countUserFollowees($account->id) >= 1500) {
            return $this->errorsResponse(RelationError::FollowingTooManyUser());
        }

        if ($this->relation()->userBlackListHas($account->id, $targetId)) {
            return $this->errorsResponse(RelationError::YouBlockingThey());
        }
        if ($this->relation()->userBlackListHas($targetId, $account->id)) {
            return $this->errorsResponse(RelationError::YouBeingBlocked());
        }

        try {
            $this->relation()->makeUserFollowAnother($account->id, $targetId);
        } catch (\Exception $e) {
            //TODO: maybe a exception represent for errors?
            return $this->failureResponse();
        }

        return $this->sucessResponse();
    }

	/**
	 * @Route("/follow/vip")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="relation",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
    public function followVipUserAction(Request $request) {
    	$account = $this->requireAuth($request);
        /** @var MemcacheInterface $memcache */
        $memcache = $this->container()->get('memcache.default');
        $vipUserIds = array_keys($memcache->get('recommendActiveVip'));
        if ($this->relation()->countUserFollowees($account->id) <= 10) {
            $followCounter = 0;
            foreach ($vipUserIds as $uid) {
                if ($followCounter >= 30) {
                    break;
                }

                $targetUser = $this->account()->fetchOne($uid);
                if ($targetUser === null ||
                    $account->id == $uid ||
                    $this->relation()->isUserFollowingAnother($account->id, $uid) ||
                    $this->relation()->userBlackListHas($account->id, $uid) ||
                    $this->relation()->userBlackListHas($uid, $account->id) ||
                    $this->relation()->countUserFollowees($account->id) >= 1500
                ) {
                    continue;
                }
                try {
                    $this->relation()->makeUserFollowAnother($account->id, $uid);
                } catch (\Exception $e) {
                    return $this->failureResponse();
                }
                $followCounter += 1;
            }
        }

	    return $this->sucessResponse();
    }

    /**
     * @Route("/unfollow")
     * @Method("POST")
     * @ApiDoc(
     *   section="relation",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="uid", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function unfollowUserAction(Request $request) {
        $user = $this->requireAuth($request);
        $targetId = $this->requireId($request->request, 'uid');

        if ($user->id === $targetId) {
            return $this->failureResponse();
        }

        $targetUser = $this->account()->fetchOne($targetId);
        if ($targetUser === null) {
            return $this->errorsResponse(AccountError::UserNotExist($targetId));
        }

        try {
            $this->relation()->makeUserUnfollowAnother($user->id, $targetId);
        } catch (\Exception $e) {
            //TODO: maybe a exception represent for errors?
            return $this->failureResponse();
        }

        return $this->sucessResponse();
    }

    /**
     * @Route("/blacklist/add")
     * @Method("POST")
     * @ApiDoc(
     *   section="relation",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="uid", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function blackListAdd(Request $request) {
        $account = $this->requireAuth($request);
        $targetId = $this->requireId($request->request, 'uid');

        if ($account->id == $targetId) {
            return $this->errorsResponse(RelationError::CanNotBlockYourself());
        }

        $targetUser = $this->account()->fetchOne($targetId);
        if ($targetUser === null) {
            return $this->errorsResponse(AccountError::UserNotExist($targetId));
        }

        if ($this->relation()->userBlackListCount($account->id) >= 1000) {
            return $this->errorsResponse(RelationError::BlockingTooManyUser());
        }

        $this->relation()->userBlackListAdd($account->id, $targetId);
        if ($this->relation()->isUserFollowingAnother($account->id, $targetId)) {
            $this->relation()->makeUserUnfollowAnother($account->id, $targetId);
        }
        if ($this->relation()->isUserFollowingAnother($targetId, $account->id)) {
            $this->relation()->makeUserUnfollowAnother($targetId, $account->id);
        }
        return $this->sucessResponse();
    }

    /**
     * @Route("/blacklist/remove")
     * @Method("POST")
     * @ApiDoc(
     *   section="relation",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="uid", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function blackListRemove(Request $request) {
        $account = $this->requireAuth($request);
        $targetId = $this->requireId($request->request, 'uid');

        $this->relation()->userBlackListRemove($account->id, $targetId);
        return $this->sucessResponse();
    }

    /**
     * @Route("/blacklist/list")
     * @Method("GET")
     * @ApiDoc(
     *   section="relation",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false,
     *       "description"="根据cursor来返回指定批次的数据，可以简单理解为页码，起始为0"},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function blackListList(Request $request) {
        $account = $this->requireAuth($request);
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);
        $userIds = $this->relation()->userBlackListList($account->id, $cursor, $count, $nextCursor);

        $synthesizer = $this->getSynthesizerBuilder()->buildUserSynthesizer(
            $userIds, $account->id
        );
        return $this->arrayResponse('users', $synthesizer->synthesizeAll(), $nextCursor);
    }
} 