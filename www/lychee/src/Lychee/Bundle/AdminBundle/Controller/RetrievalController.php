<?php

namespace Lychee\Bundle\AdminBundle\Controller;

use Lychee\Bundle\AdminBundle\Components\Foundation\Paginator;
use Lychee\Bundle\AdminBundle\Entity\CustomizeContent;
use Lychee\Bundle\AdminBundle\Service\DuplicateCustomizeContentException;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\KVStorage\MemcacheStorage;
use Lychee\Module\Recommendation\RecommendationType;
use Lychee\Module\Recommendation\UserRankingType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RetrievalController
 * @package Lychee\Bundle\AdminBundle\Controller
 * @Route("/retrieval")
 */
class RetrievalController extends BaseController
{

    /**
     * @return string
     */
    public function getTitle()
    {
        return '排行检索';
    }

    /**
     * @Route("/")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function indexAction()
    {
        return $this->redirect($this->generateUrl('lychee_admin_retrieval_users'));
    }

    /**
     * @param Request $request
     * @param $type
     * @param $module
     * @return array
     */
    private function getResult(Request $request, $type, $module)
    {
        $recommendedList = $this->recommendation()->getHotestIdList($type);
        $paginator = $this->getPaginator(
            $recommendedList->getIterator(),
            $request->query->get('cursor', 0),
            $request->query->get('page', 1),
            $request->query->get('start_page', 1)
        );

        $recommendedIds = $paginator->getResult();
        $recommendedData = $this->$module()->fetch($recommendedIds);

        return array(
            'paginator' => $paginator,
            'recommendedIds' => $recommendedIds,
            'recommendedData' => $recommendedData,
        );
    }

    /**
     * @param $iterator
     * @param $cursor
     * @param $page
     * @param $startPage
     * @param int $step
     * @return Paginator
     */
    private function getPaginator($iterator, $cursor, $page, $startPage, $step = 30)
    {
        $paginator = new Paginator($iterator);
        $paginator->setCursor($cursor)
            ->setPage($page)
            ->setStep($step)
            ->setStartPageNum($startPage);

        return $paginator;
    }

    /**
     * @Route("/users")
     * @Template
     * @param Request $request
     * @return array
     */
    public function usersAction(Request $request)
    {
        $typeId = $request->query->get('type');
        switch ($typeId) {
            case '2':
                $type = UserRankingType::FOLLOWED;
                break;
            case '3':
                $type = UserRankingType::COMMENT;
                break;
            case '4':
                $type = UserRankingType::IMAGE_COMMENT;
                break;
            default:
                $type = UserRankingType::POST;
                break;
        }
        $rankingList = $this->recommendation()->getUserRankingIdList($type);
        $paginator = $this->getPaginator(
            $rankingList->getIterator(),
            $request->query->get('cursor', 0),
            $request->query->get('page', 1),
            $request->query->get('start_page', 1)
        );
        $ranking = $paginator->getResult();
        $recommendedIds = array_keys($ranking);
        $recommendedData = $this->account()->fetch($recommendedIds);

        $topicService = $this->topic();
        $managerTopics = array_reduce($recommendedData, function($result, $user) use ($topicService) {
            $managerId = $user->id;
            $topicIds = array_slice($topicService->fetchIdsByManager($managerId, 0, 3, $nextCursor), 0, 2);
            $topics = $topicService->fetch($topicIds);
            if ($nextCursor) {
                array_push($topics, []);
            }
            $result[$managerId] = $topics;

            return $result;
        });

        return $this->response('用户', array(
            'paginator' => $paginator,
            'recommendedIds' => $recommendedIds,
            'recommendedData' => $recommendedData,
            'ranking' => $ranking,
            'managerTopics' => $managerTopics,
        ));
    }

    /**
     * @Route("/posts")
     * @Template
     * @param Request $request
     * @return array
     */
    public function postsAction(Request $request)
    {
        $recommendedData = $this->getResult($request, RecommendationType::POST, 'post');
        $authors = $this->getAuthors($recommendedData['recommendedData']);
        $topics = $this->getTopics($recommendedData['recommendedData']);

        return $this->response('帖子', array_merge($recommendedData, array(
            'authors' => $authors,
            'topics' => $topics,
        )));
    }

    /**
     * @Route("/topics")
     * @Template
     * @param Request $request
     * @return array
     */
    public function topicsAction(Request $request)
    {
        $data = $this->getResult($request, RecommendationType::TOPIC, 'topic');
        $topicIds = $data['recommendedIds'];
        $topics = $data['recommendedData'];
        $managerIds = array_map(function($topic) {
            return $topic->managerId;
        }, $topics);
        $managers = $this->account()->fetch($managerIds);
        $amountOfPosts = $this->topic()->fetchAmountOfPosts($topicIds);
        $followerCounter = $this->topicFollowing()->getTopicsFollowerCounter($topicIds);
        $topicFollowers = array();
        foreach ($topicIds as $topicId) {
            $topicFollowers[$topicId] = $followerCounter->getCount($topicId);
        }

        return $this->response($this->getParameter('topic_name'), array_merge($data, array(
            'postsAmount' => $amountOfPosts,
            'topicFollowers' => $topicFollowers,
            'managers' => $managers,
        )));
    }

    /**
     * @Route("/comments")
     * @Template
     * @param Request $request
     * @return array
     */
    public function commentsAction(Request $request)
    {
        $recommendedData = $this->getResult($request, RecommendationType::COMMENT, 'comment');
        $authors = $this->getAuthors($recommendedData['recommendedData']);

        return $this->response('评论', array_merge($recommendedData, array(
            'authors' => $authors,
            'amountOfCommentLiker' => 0,
        )));
    }

    /**
     * @param $recommendedData
     * @return array
     */
    private function getAuthors($recommendedData)
    {
        $authorIds = array_reduce($recommendedData, function($data, $item) {
            if (!$data) {
                $data = array();
            }
            if (!in_array($item->authorId, $data)) {
                $data[] = $item->authorId;
            }

            return $data;
        });

        return $this->account()->fetch($authorIds);
    }

    /**
     * @param $recommendedData
     * @return array
     */
    private function getTopics($recommendedData)
    {
        $topicIds = array_reduce($recommendedData, function($data, $item) {
            if (!$data) {
                $data = array();
            }
            if (!in_array($item->topicId, $data)) {
                $data[] = $item->topicId;
            }

            return $data;
        });

        return $this->topic()->fetch($topicIds);
    }

    /**
     * @Route("/customize_topic/list/{page}", requirements={"page" = "\d+"})
     * @Template
     * @param int $page
     * @return array
     */
    public function customizeTopicListAction($page = 1) {
        $countPerPage = 10;
        $type = CustomizeContent::TYPE_TOPIC;
        $result = $this->customizeContentService()->fetch($type, $page, $countPerPage);
        $topicIds = ArrayUtility::columns($result, 'targetId');
        $customizeContentCount = $this->customizeContentService()->customizeContentCount($type);

        return $this->response('自定义次元', [
            'type' => $type,
            'customizeContent' => ArrayUtility::mapByColumn($result, 'targetId'),
            'topicIds' => $topicIds,
            'topics' => ArrayUtility::mapByColumn($this->topic()->fetch($topicIds), 'id'),
            'pageCount' => ceil($customizeContentCount / $countPerPage),
            'page' => $page,
        ]);
    }

    /**
     * @Route("/customize_user/list/{page}", requirements={"page" = "\d+"})
     * @Template
     * @param int $page
     * @return array
     */
    public function customizeUserListAction($page = 1) {
        $countPerPage = 20;
        $type = CustomizeContent::TYPE_USER;
        $result = $this->customizeContentService()->fetch($type, $page, $countPerPage);
        $userIds = ArrayUtility::columns($result, 'targetId');
        $customizeContentCount = $this->customizeContentService()->customizeContentCount($type);
        $users = $this->account()->fetch($userIds);
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

        return $this->response('自定义用户', [
            'type' => $type,
            'customizeContent' => ArrayUtility::mapByColumn($result, 'targetId'),
            'userIds' => $userIds,
            'users' => ArrayUtility::mapByColumn($users, 'id'),
            'pageCount' => ceil($customizeContentCount / $countPerPage),
            'page' => $page,
            'managerTopics' => $managerTopics,
        ]);
    }

    /**
     * @Route("/customize_{type}/posts")
     * @Template
     * @param $type
     * @param Request $request
     * @return array
     */
    public function customizeContentAction($type, Request $request) {
        $date = $request->query->get('date', (new \DateTime())->format('Y-m-d'));
        $hour = str_pad($request->query->get('hour', getdate()['hours']), 2, '0', STR_PAD_LEFT);
        $subTitle = '自定义内容';
        if ($type === CustomizeContent::TYPE_USER) {
            $subTitle = '自定义用户';
        } elseif ($type === CustomizeContent::TYPE_TOPIC) {
            $subTitle = '自定义次元';
        }

        return $this->response($subTitle, [
            'type' => $type,
            'date' => $date,
            'hour' => $hour,
            'tags' => $this->get('lychee_admin.service.tag')->fetchAllTags(),
        ]);
    }
    
    /**
     * @Route("/fetch_posts")
     * @param Request $request
     * @return JsonResponse
     */
    public function fetchPostsAction(Request $request) {
        $type = $request->query->get('type');
        $date = $request->query->get('date');
        $hour = $request->query->get('hour');
        $cursor = (int)$request->query->get('cursor');
        $targetIds = $this->customizeContentService()->fetchTargetIds($type);
        if ($type == CustomizeContent::TYPE_TOPIC) {
            $fn = 'fetchIdsByTopicIdsPerHour';
        } else {
            $fn = 'fetchIdsByAuthorIdsPerHour';
        }
        $postIds = $this->post()->$fn($targetIds, new \DateTime(sprintf("%s %s:00", $date, $hour)), $cursor, 20, $nextCursor);
        $userAgent = $request->headers->get('User-Agent');
        if (preg_match('/(android|iphone)/i', $userAgent)) {
            $imageWidth = 180;
        } else {
            $imageWidth = 238;
        }
        $result = $this->getData($postIds, $imageWidth);
        $recommendationTargetIds = $this->recommendation()->filterItemTargetIds(RecommendationType::POST, $postIds);
        $result = array_map(function($item) use ($recommendationTargetIds) {
            if (in_array($item['id'], $recommendationTargetIds)) {
                $item['recommended'] = true;
            } else {
                $item['recommended'] = false;
            }
            return $item;
        }, $result);

        return new JsonResponse(array('total' => count($result), 'result' => $result));
    }

    /**
     * @Route("/customize_content/add")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function addCustomizeContent(Request $request) {
        $targetId = $request->request->get('id');
        $type = $request->request->get('type');
        if ($type === CustomizeContent::TYPE_TOPIC) {
            $topic = $this->topic()->fetchOne($targetId);
            if (!$topic) {
                return $this->redirect($this->generateUrl('lychee_admin_error', [
                    'errorMsg' => '次元不存在',
                    'callbackUrl' => $request->headers->get('referer'),
                ]));
            }
        } elseif ($type == CustomizeContent::TYPE_USER) {
            $user = $this->account()->fetchOne($targetId);
            if (!$user) {
                return $this->redirect($this->generateUrl('lychee_admin_error', [
                    'errorMsg' => '用户不存在',
                    'callbackUrl' => $request->headers->get('referer'),
                ]));
            }
        }
        try {
            $this->customizeContentService()->add($type, $targetId);
        } catch (DuplicateCustomizeContentException $e) {
            return $this->redirect($this->generateUrl('lychee_admin_error', [
                'errorMsg' => $e->getMessage(),
                'callbackUrl' => $request->headers->get('referer'),
            ]));
        }

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/customize_content/remove")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeCustomizeContent(Request $request) {
        $id = $request->request->get('id');
        $this->customizeContentService()->deleteById($id);

        return $this->redirect($request->headers->get('referer'));
    }

}
