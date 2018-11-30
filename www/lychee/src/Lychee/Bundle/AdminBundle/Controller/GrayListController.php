<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 6/15/16
 * Time: 2:53 PM
 */

namespace Lychee\Bundle\AdminBundle\Controller;


use Lychee\Bundle\AdminBundle\Service\GrayListService;
use Lychee\Component\Foundation\ArrayUtility;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/gray_list")
 * Class GrayListController
 * @package Lychee\Bundle\AdminBundle\Controller
 */
class GrayListController extends BaseController {

    public function getTitle() {
        return '灰名单';
    }

    /**
     * @Route("/")
     * @Template
     * @param Request $request
     * @return array
     */
    public function indexAction(Request $request) {
        $type = $request->query->get('type', GrayListService::TYPE_USER);
        $sort = $request->query->get('sort', GrayListService::SORT_BY_DELETE_TIME);
        $page = $request->query->get('page', 1);
        $count = $request->query->get('count', 20);
        /** @var GrayListService $grayListService */
        $grayListService = $this->get('lychee_admin.service.graylist');
        $list = $grayListService->fetch($type, $sort, $page, $count);

        $selector = array_map(function($item) { return $item[0]; }, $list);
        $users = $topics = $topicManagers = $managerTopics = [];
        if ($type == GrayListService::TYPE_USER) {
            $users = ArrayUtility::mapByColumn($this->account()->fetch($selector), 'id');
            $topicService = $this->topic();
            $managerTopics = array_reduce($users, function($result, $user) use ($topicService) {
                $managerId = $user->id;
                $topicIds = $topicService->fetchIdsByManager($managerId, 0, 2, $nextCursor);
                $topics = $topicService->fetch($topicIds);
                if ($nextCursor) {
                    array_push($topics, []);
                }
                $result[$managerId] = $topics;

                return $result;
            });
        } else {
            $topics = ArrayUtility::mapByColumn($this->topic()->fetch($selector), 'id');
            $topicManagerIds = array_map(function($t) { return $t->managerId; }, $topics);
            $topicManagers = ArrayUtility::mapByColumn($this->account()->fetch($topicManagerIds), 'id');
        }
        $sum = $grayListService->fetchListCount();
        $pageCount = min(ceil($sum / $count), 10);
        $ciyoUserId = 31721;
        $ciyoUser = $this->account()->fetchOne($ciyoUserId);
        $deviceBlock = array_reduce($users, function($result, $user) {
            !$result && $result = [];
            /**
             * @var \Lychee\Module\Account\DeviceBlocker $deviceBlocker
             */
            $deviceBlocker = $this->get('lychee.module.account.device_blocker');
            $platformAndDevice = $deviceBlocker->getUserDeviceId($user->id);
            if (is_array($platformAndDevice)) {
                if ($deviceBlocker->isDeviceBlocked($platformAndDevice[0], $platformAndDevice[1])) {
                    array_push($result, $user->id);
                }
            }

            return $result;
        });
        return $this->response('灰名单', [
            'type' => $type,
            'sort' => $sort,
            'page' => $page,
            'list' => $list,
            'users' => $users,
            'managerTopics' => $managerTopics,
            'topics' => $topics,
            'topicManagers' => $topicManagers,
            'pageCount' => $pageCount,
            'ciyoUser' => $ciyoUser,
            'deviceBlock' => $deviceBlock,
        ]);
    }
}