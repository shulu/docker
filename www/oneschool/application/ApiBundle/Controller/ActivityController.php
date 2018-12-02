<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Component\Foundation\ArrayUtility;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Lychee\Component\Foundation\CursorWrapper;

/**
 * @Route("/activity")
 */
class ActivityController extends Controller {

    /**
     * @Route("/timeline")
     * @Method("GET")
     * @ApiDoc(
     *   section="activity",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据, 默认20，最多不超过100"
     *     }
     *   }
     * )
     */
    public function timelineAction(Request $request) {
        $account = $this->requireAuth($request);
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);

        $userIterator = new CursorWrapper(
            function($cursor, $count, &$nextCursor) use ($account) {
                return $this->relation()->fetchFolloweeIdsByUserId(
                    $account->id, $cursor, $count, $nextCursor
                );
            },
            100
        );

        $ids = array();
        foreach ($userIterator as $userIds) {
            $particalIds = $this->activity()->fetchIdsByUsers($userIds, $cursor, $count);
            $ids = $this->mergeIds($ids, $particalIds, $count);
        }

        if (count($ids) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $ids[count($ids) - 1];
        }

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildActivitySynthesizer($ids, $account->id);
        $activities = $synthesizer->synthesizeAll();

        $activities = array_values($activities);

        return $this->arrayResponse('activities', $activities, $nextCursor);
    }

    private function mergeIds($ids1, $ids2, $maxCount = null) {
        $result = array();
        if ($maxCount === null) {
            while(!empty($ids1) && !empty($ids2)){
                $result[] = $ids1[0] >= $ids2[0] ? array_shift($ids1) : array_shift($ids2);
            }
            $result = array_merge($result, $ids1, $ids2);
        } else {
            while(!empty($ids1) && !empty($ids2) && count($result) < $maxCount){
                $result[] = $ids1[0] >= $ids2[0] ? array_shift($ids1) : array_shift($ids2);
            }
            $result = array_merge($result, $ids1, $ids2);
            $result = array_slice($result, 0, $maxCount);
        }

        return $result;
    }

    /**
     * @Route("/timeline/user")
     * @Method("GET")
     * @ApiDoc(
     *   section="activity",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="user", "dataType"="integer", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"
     *     }
     *   }
     * )
     */
    public function userTimelineAction(Request $request) {
        $account = $this->requireAuth($request);
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 100);

        $userId = $this->requireId($request->query, 'user');
        $activityIds = $this->activity()->fetchIdsByUser($userId, $cursor, $count, $nextCursor);
        $synthesizer = $this->getSynthesizerBuilder()
            ->buildActivitySynthesizer($activityIds, $account->id);
        return $this->arrayResponse('activities', $synthesizer->synthesizeAll(), $nextCursor);
    }

}