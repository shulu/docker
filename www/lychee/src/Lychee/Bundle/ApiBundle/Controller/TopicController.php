<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Bundle\ApiBundle\Error\CommonError;
use Lychee\Bundle\ApiBundle\Error\Error;
use Lychee\Bundle\ApiBundle\Error\ErrorsException;
use Lychee\Bundle\ApiBundle\Error\PostError;
use Lychee\Bundle\ApiBundle\Error\TopicError;
use Lychee\Bundle\CoreBundle\Validator\Constraints\NotSensitiveWord;
use Lychee\Bundle\CoreBundle\Validator\Constraints\ReservedWord;
use Lychee\Bundle\CoreBundle\Validator\Constraints\TopicName;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Recommendation\RecommendationType;
use Lychee\Module\Topic\Exception\CategoryNotFoundException;
use Lychee\Module\Topic\Exception\FollowingTooMuchTopicException;
use Lychee\Module\Topic\Exception\RunOutOfCreatingQuotaException;
use Lychee\Module\Topic\Exception\TooMuchCoreMemberException;
use Lychee\Module\Topic\Exception\TopicAlreadyExistException;
use Lychee\Module\Topic\Exception\TopicMissingException;
use Lychee\Module\Topic\Following\UserFollowingIterator;
use Lychee\Module\Topic\TopicAnnouncementService;
use Lychee\Module\Topic\TopicParameter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Lychee\Module\Account\Mission\MissionType;
use Lychee\Module\Topic\TopicCategoryService;
use Lychee\Module\IM\IMService;
use Lychee\Module\ContentManagement\ContentBlockingService;
use Symfony\Component\HttpFoundation\Response;
use Lychee\Module\Topic\TopicVisitorRecorder;
use Lychee\Module\Topic\Following\ApplyService;
use Lychee\Module\Topic\Entity\TopicFollowingApplication;
use Lychee\Module\Notification\NotificationService;
use Lychee\Module\Topic\CoreMember\TopicCoreMemberService;
use Symfony\Component\Validator\Constraints\Length;
use Lychee\Module\Topic\TopicDefaultGroupService;
use Lychee\Module\IM\GroupService;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * @Route("/topic")
 */
class TopicController extends Controller {

    private function checkTopicName($topicName) {
        $constraints = [new TopicName(), new NotSensitiveWord(), new ReservedWord()];
        $violations = $this->get('validator')->validate($topicName, $constraints);
        if (count($violations) == 0) {
            return;
        }
        $errors = [];
        foreach ($violations as $v) {
            /** @var ConstraintViolation $v */
            $c = $v->getConstraint();
            if ($c instanceof TopicName || $c instanceof NotSensitiveWord) {
                $errors[] = TopicError::TopicNameInvalid();
            } else if ($c instanceof ReservedWord) {
                $errors[] = CommonError::ContainsReservedWords();
            }
        }
        throw new ErrorsException($errors);
    }

    /**
     * @Route("/create")
     * @Method("POST")
     * @ApiDoc(
     *   section="topic",
     *   description="创建话题",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="title", "dataType"="string", "required"=true},
     *     {"name"="summary", "dataType"="string", "required"=false},
     *     {"name"="description", "dataType"="string", "required"=false},
     *     {"name"="index_image", "dataType"="string", "required"=false},
     *     {"name"="cover_image", "dataType"="string", "required"=false},
     *     {"name"="category", "dataType"="string", "required"=false,
     *       "description"="分类，多个分类用半角逗号分开，不带空格"},
     *     {"name"="private", "dataType"="integer", "required"=false},
     *     {"name"="apply_to_follow", "dataType"="integer", "required"=false},
     *     {"name"="color", "dataType"="string", "required"=false},
     *   }
     * )
     */
    public function createAction(Request $request) {
        $account = $this->requireAuth($request);

        $title = $this->requireParam($request, 'title');
        $this->checkTopicName($title);

        if ($this->topic()->fetchOneByTitle($title)) {
            return $this->errorsResponse(TopicError::TopicAlreadyExist());
        }
        if ($this->topic()->getUserCreatingQuota($account->id) <= 0) {
            return $this->errorsResponse(TopicError::RunOutOfCreatingQuota());
        }

        $p = new TopicParameter();
        $p->creatorId = $account->id;
        $p->title = $title;
        $p->summary = $request->request->get('summary');
        $p->description = $request->request->get('description');
        $p->indexImageUrl = $request->request->get('index_image');
        $p->coverImageUrl = $request->request->get('cover_image');

        $categoryString = $request->request->get('category');
        if (!empty($categoryString)) {
            $categories = explode(',', $categoryString);
            if (count($categories) > 1) {
                return $this->errorsResponse(CommonError::ParameterInvalid('category', $categoryString));
            }
            $categoryIds = $this->getCategoryService()->categoryIdsOfNames($categories);
            $p->categoryIds = $categoryIds;
        }

        $p->private = $request->request->getInt('private', 0) > 0;
        if ($p->private) {
            $p->applyToFollow = true;
        } else {
            $p->applyToFollow = false;
        }

        $p->color = $request->request->get('color', null);
        if (strlen($p->color) > 10) {
            return $this->errorsResponse(CommonError::ParameterInvalid('color', $p->color));
        }

        if (!$p->private) {
            $appVersion = $request->request->get(self::CLIENT_APP_VERSION_KEY);
            if (version_compare($appVersion, '2.5', '>=')) {
                try {
                    $this->topic()->submitCreatingApplication($p);
                } catch (TopicAlreadyExistException $e) {
                    return $this->errorsResponse(TopicError::TopicAlreadyExist());
                } catch (RunOutOfCreatingQuotaException $e) {
                    return $this->errorsResponse(TopicError::RunOutOfCreatingQuota());
                }
                return $this->sucessResponse();
            }
//            return $this->errorsResponse(new Error(CommonError::CODE_UnknownError, 'require_update', '2.5才能创建公开次元'));
        }

        try {
            $topic = $this->topic()->create($p);
        } catch (TopicAlreadyExistException $e) {
            return $this->errorsResponse(TopicError::TopicAlreadyExist());
        } catch (RunOutOfCreatingQuotaException $e) {
            return $this->errorsResponse(TopicError::RunOutOfCreatingQuota());
        }

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildSimpleTopicSynthesizer(array($topic->id => $topic), $account->id);
        return $this->dataResponse($synthesizer->synthesizeOne($topic->id));
    }

    /**
     * @Route("/creating_quota")
     * @Method("GET")
     * @ApiDoc(
     *   section="topic",
     *   description="获取创建话题的剩余次数",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true}
     *   }
     * )
     */
    public function getCreatingQuota(Request $request) {
        $account = $this->requireAuth($request);
        $quota = $this->topic()->getUserCreatingQuota($account->id);
        return $this->dataResponse(array('quota' => $quota));
    }

    /**
     * @Route("/get")
     * @Method("GET")
     * @ApiDoc(
     *   section="topic",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="tid", "dataType"="integer", "required"=false},
     *     {"name"="title", "dataType"="string", "required"=false}
     *   }
     * )
     */
    public function getAction(Request $request) {
        $account = $this->getAuthUser($request);
        $topicId = $request->query->getInt('tid', 0);

        if ($topicId == 0) {
            $title = $this->requireParam($request->query, 'title');
            $topic = $this->topic()->fetchOneByTitle($title);
        } else {
            $topic = $this->topic()->fetchOne($topicId);
        }

        if ($topic == null) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }
        if ($this->isTopicBlocking($request, $topic->id)) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }

        $synthesizer = $this->getSynthesizerBuilder()->buildTopicSynthesizer(
            array($topic), $account ? $account->id : 0
        );
        $data = $synthesizer->synthesizeOne($topic->id);

        if ($account) {
            if ($this->topicFollowing()->getUserFollowingResolver($account->id, array($topicId))->isFavorite($topicId)) {
                $data['my_favorite'] = true;
            }
        }

        return $this->dataResponse($data);
    }

    /**
     * @Route("/newests")
     * @Method("GET")
     * @ApiDoc(
     *   section="topic",
     *   description="获取最新的话题列表",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="cursor", "dataType"="integer", "required"=true},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function listNewAction(Request $request) {
        $account = $this->getAuthUser($request);
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);

        $topics = $this->topic()->fetchAll($cursor, $count, $nextCursor);
        $topics = $this->filterBlockingTopics($request, $topics);

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildSimpleTopicSynthesizer($topics, $account ? $account->id : 0);
        return $this->arrayResponse(
            'topics', $synthesizer->synthesizeAll(), $nextCursor
        );
    }

    /**
     * @Route("/followees")
     * @Method("GET")
     * @ApiDoc(
     *   section="topic",
     *   description="获取指定用户关注的话题列表",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="uid", "dataType"="integer", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=true},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function listUserFollowingAction(Request $request) {
        $account = $this->getAuthUser($request);
        $userId = $this->requireId($request->query, 'uid');
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);

        $itor = $this->topicFollowing()->getUserFolloweeIterator($userId);
        $itor->withOrder(UserFollowingIterator::ORDER_VISIT_DESC);
        $itor->setStep($count)->setCursor($cursor);
        $nextCursor = $itor->getNextCursor();
        $topicIds = $itor->current();

        $topicIds = $this->filterBlockingTopics($request, $topicIds);

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildSimpleTopicSynthesizer($topicIds, $account ? $account->id : 0);
        return $this->arrayResponse(
            'topics', $synthesizer->synthesizeAll(), $nextCursor
        );
    }

    /**
     * @Route("/followers")
     * @Method("GET")
     * @ApiDoc(
     *   section="topic",
     *   description="获取关注指定话题的用户列表",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="tid", "dataType"="integer", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=true},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function listFollowersAction(Request $request) {
        $account = $this->getAuthUser($request);
        $topicId = $this->requireId($request->query, 'tid');
        if ($this->isTopicBlocking($request, $topicId)) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }

        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);

        $itor = $this->topicFollowing()->getSortedTopicFollowerIterator($topicId)
            ->setCursor($cursor)->setStep($count);
        $userIds = $itor->current();
        $nextCursor = $itor->getNextCursor();

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildUserSynthesizer($userIds, $account ? $account->id : 0);
        return $this->arrayResponse(
            'users', $synthesizer->synthesizeAll(), $nextCursor
        );
    }

    /**
     * @Route("/follow")
     * @Method("post")
     * @ApiDoc(
     *   section="topic",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="tid", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function followAction(Request $request) {
        $account = $this->requireAuth($request);
        $topicId = $this->requireId($request->request, 'tid');
        if ($this->isTopicBlocking($request, $topicId)) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }
        $topic = $this->topic()->fetchOne($topicId);
        if ($topic == null || $topic->deleted) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }
        // 特定次元无法加入
//        if ($topic->id == self::SPECIAL_TOPIC) {
//        	return $this->errorsResponse(TopicError::CannotFollow());
//        }
        if ($topic->private) {
            return $this->errorsResponse(TopicError::RequireForApply());
        }

        try {
            $this->topicFollowing()->follow($account->id, $topicId, $followedBefore);
        } catch (FollowingTooMuchTopicException $e) {
            return $this->errorsResponse(TopicError::FollowingTooMuchTopic());
        } catch (TopicMissingException $e) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        } catch (\Exception $e) {
            return $this->failureResponse();
        }

        $missionResult = $this->missionManager()->userAccomplishMission($account->id, MissionType::FOLLOW_TOPIC);
        $response = array('result' => true);
        $this->injectMissionResult($response, $missionResult);
        return $this->dataResponse($response);
    }

    /**
     * @Route("/unfollow")
     * @Method("post")
     * @ApiDoc(
     *   section="topic",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="tid", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function unfollowAction(Request $request) {
        $account = $this->requireAuth($request);
        $topicId = $this->requireId($request->request, 'tid');

        $topic = $this->topic()->fetchOne($topicId);
        if ($topic && $topic->managerId == $account->id) {
            return $this->errorsResponse(TopicError::ManagerCannotUnfollow());
        }

        try {
            $this->topicFollowing()->unfollow($account->id, $topicId);
            $this->coreMember()->removeCoreMember($topicId, $account->id);
        } catch (\Exception $e) {
            return $this->failureResponse();
        }

        return $this->sucessResponse();
    }

    /**
     * @Route("/favorite/list")
     * @Method("GET")
     * @ApiDoc(
     *   section="topic",
     *   description="获取指定用户关注的话题列表，优先返回用户最爱的话题",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="cursor", "dataType"="integer", "required"=true},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function listUserFollowingOrderByFavorite(Request $request) {
        $account = $this->requireAuth($request);
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);

        $itor = $this->topicFollowing()->getUserFolloweeIterator($account->id);
        $itor->setCursor($cursor)->setStep($count);
        $topicIds = $itor->current();
        $nextCursor = $itor->getNextCursor();

        $topicIds = $this->filterBlockingTopics($request, $topicIds);

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildSimpleTopicSynthesizer($topicIds, $account->id);
        $topics = $synthesizer->synthesizeAll();

        $favoriteResolvor = $this->topicFollowing()->getUserFollowingResolver($account->id, $topicIds);
        foreach ($topics as &$topic) {
            if ($favoriteResolvor->isFavorite($topic['id'])) {
                $topic['my_favorite'] = true;
            }
        }

        return $this->arrayResponse(
            'topics', $topics, $nextCursor
        );
    }

    /**
     * @Route("/favorite/add")
     * @Method("POST")
     * @ApiDoc(
     *   section="topic",
     *   description="添加最爱话题",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="tid", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function addFavorite(Request $request) {
        $account = $this->requireAuth($request);
        $topicId = $this->requireId($request->request, 'tid');
        if ($this->isTopicBlocking($request, $topicId)) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }

        if ($this->topicFollowing()->isFollowing($account->id, $topicId) === false) {
            return $this->failureResponse();
        }

        $this->topicFollowing()->setFavorite($account->id, $topicId);
        return $this->sucessResponse();
    }

    /**
     * @Route("/favorite/remove")
     * @Method("POST")
     * @ApiDoc(
     *   section="topic",
     *   description="移除最爱话题",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="tid", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function removeFavorite(Request $request) {
        $account = $this->requireAuth($request);
        $topicId = $this->requireId($request->request, 'tid');

        $this->topicFollowing()->unsetFavorite($account->id, $topicId);
        return $this->sucessResponse();
    }

    /**
     * @Route("/countings")
     * @Method("GET")
     * @ApiDoc(
     *   section="topic",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="tids", "dataType"="string", "required"=true,
     *       "description"="话题id，多个id用半角逗号分隔，一次最多100个id"}
     *   }
     * )
     */
    public function fetchCountings(Request $request) {
        $account = $this->requireAuth($request);
        $topicIds = $this->getRequestIds($request->query, 'tids', 100);

        $topics = $this->topic()->fetch($topicIds);
        $result = array();
        foreach ($topics as $topic) {
            $result[$topic->id] = $topic->postCount;
        }

        $postCountMap = $result;
        $result = array();
        foreach ($topicIds as $topicId) {
            $result[$topicId] = array(
                'posts' => isset($postCountMap[$topicId]) ? $postCountMap[$topicId] : 0,
            );
        }

        return $this->dataResponse($result);
    }

    /**
     * @Route("/invite")
     * @Method("POST")
     * @ApiDoc(
     *   section="topic",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="tid", "dataType"="integer", "required"=true},
     *     {"name"="uids", "dataType"="string", "required"=true,
     *       "description"="用户id，多个id用半角逗号分隔，一次最多20个id"}
     *   }
     * )
     */
    public function inviteFriends(Request $request) {
        $account = $this->requireAuth($request);
        $topicId = $this->requireId($request->request, 'tid');
        if ($this->isTopicBlocking($request, $topicId)) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }

        $topic = $this->topic()->fetchOne($topicId);
        if ($topic == null) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }

        $uids = $this->getRequestIds($request->request, 'uids', 20, true);

        if (count($uids) > 0) {
            /** @var IMService $im */
            $im = $this->get('lychee.module.im');
            $ok = $im->dispatchMass($account->id, $uids, 0, '邀请你入驻['.$topic->title.']，一起来玩吧~', time());
            if (!$ok) {
                return $this->failureResponse();
            }
        }

        return $this->sucessResponse();
    }

    /**
     * @Route("/created_by_me")
     * @Method("GET")
     * @ApiDoc(
     *   section="topic",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function getTopicsCreatedByMe(Request $request) {
        $account = $this->requireAuth($request);
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);

        $topicIds = $this->topic()->fetchIdsByManager($account->id, $cursor, $count, $nextCursor);
        $topicIds = $this->filterBlockingTopics($request, $topicIds);

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildSimpleTopicSynthesizer($topicIds, $account ? $account->id : 0);
        return $this->arrayResponse(
            'topics', $synthesizer->synthesizeAll(), $nextCursor
        );
    }

    /**
     * @Route("/managed_by")
     * @Method("GET")
     * @ApiDoc(
     *   section="topic",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="uid", "dataType"="integer", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function listByManager(Request $request) {
        $account = $this->requireAuth($request);
        $manager = $this->requireInt($request->query, 'uid');
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);

        $topicIds = $this->topic()->fetchIdsByManager($manager, $cursor, $count, $nextCursor);
        $topicIds = $this->filterBlockingTopics($request, $topicIds);

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildSimpleTopicSynthesizer($topicIds, $account ? $account->id : 0);
        return $this->arrayResponse(
            'topics', $synthesizer->synthesizeAll(), $nextCursor
        );
    }

    /**
     * @return TopicCategoryService
     */
    private function getCategoryService() {
        return $this->get('lychee.module.topic.category');
    }

    /**
     * @Route("/category/list")
     * @Method("GET")
     * @ApiDoc(
     *   section="topic",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="category", "dataType"="string", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function listByCategory(Request $request) {
        $account = $this->requireAuth($request);
        $categoryName = $this->requireParam($request->query, 'category');
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);

        $categoryService = $this->getCategoryService();
        try {
            $topicIds = $categoryService->allTopicIdsInCategory($categoryName);
            $topicIds = $this->filterBlockingTopics($request, $topicIds);
            $hotIds = $this->recommendation()->fetchHottestIds(RecommendationType::TOPIC, 0, 200, $nextCursor);
            $topicIds = ArrayUtility::sortIntsByIntArray($topicIds, $hotIds);
            $topicIds = array_slice($topicIds, $cursor, $count);
            if (count($topicIds) < $count) {
                $nextCursor = 0;
            } else {
                $nextCursor = $cursor + $count;
            }
        } catch (CategoryNotFoundException $e) {
            $topicIds = array();
            $nextCursor = 0;
        }

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildSimpleTopicSynthesizer($topicIds, $account ? $account->id : 0);
        return $this->arrayResponse(
            'topics', $synthesizer->synthesizeAll(), $nextCursor
        );
    }

    /**
     * @Route("/update")
     * @Method("POST")
     * @ApiDoc(
     *   section="topic",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="topic", "dataType"="integer", "required"=true},
     *     {"name"="image", "dataType"="string", "required"=false},
     *     {"name"="description", "dataType"="string", "required"=false},
     *     {"name"="color", "dataType"="string", "required"=false},
     *     {"name"="link_title", "dataType"="string", "required"=false},
     *     {"name"="link", "dataType"="string", "required"=false}
     *
     *   }
     * )
     */
    public function updateAction(Request $request) {
        $account = $this->requireAuth($request);
        $topicId = $this->requireId($request->request, 'topic');
        $imageUrl = $request->request->get('image');
        $description = $request->request->get('description');
        $color = $request->request->get('color');
	    $linkTitle = $request->request->get('link_title');
	    $link = $request->request->get('link');

        $topic = $this->topic()->fetchOne($topicId);
        if ($topic == null || $topic->deleted) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }
        if ($topic->managerId != $account->id && !$this->topic()->isCoreMember($topicId, $account->id)) {
            return $this->errorsResponse(TopicError::YouAreNotManager());
        }
        if ($description !== null) {
            if (mb_strlen($description, 'utf8') > 100) {
                return $this->errorsResponse(CommonError::ParameterInvalid('description', $description));
            }
            $topic->description = $description;
        }

        if ($imageUrl !== null) {
            $topic->indexImageUrl = $imageUrl;
        }

        if ($color !== null && is_string($color)) {
            if (strlen($color) > 10) {
                return $this->errorsResponse(CommonError::ParameterInvalid('color', $color));
            }
            $topic->color = $color;
        }

        if($linkTitle !== null){
	        if (mb_strlen($linkTitle, 'utf8') > 20) {
		        return $this->errorsResponse(CommonError::ParameterInvalid('link_title', $linkTitle));
	        }
	        $topic->linkTitle = $linkTitle;
        }

	    if($link !== null){
		    if (mb_strlen($link, 'utf8') > 255) {
			    return $this->errorsResponse(CommonError::ParameterInvalid('link', $link));
		    }
		    $topic->link = $link;
	    }

        $this->topic()->update($topic);
        return $this->sucessResponse();
    }

    /**
     * @Route("/manager_privilege/web")
     * @Method("GET")
     */
    public function managerPrivilegeWebAction() {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }
        return $this->render('LycheeApiBundle:Topic:manager_privilege.html.twig');
    }

    /**
     * @Route("/visit")
     * @Method("POST")
     * @ApiDoc(
     *   section="topic",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="topic", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function visitAction(Request $request) {
        $account = $this->requireAuth($request);
        $topicId = $this->requireId($request->request, 'topic');
        /** @var TopicVisitorRecorder $recorder */
        $recorder = $this->get('lychee.module.topic.visitor_recorder');
        $recorder->topicAddVisitor($topicId, $account->id);

        return $this->sucessResponse();
    }

    /**
     * @Route("/latest_visitor")
     * @Method("GET")
     * @ApiDoc(
     *   section="topic",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="topic", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function getLatestVisitorAction(Request $request) {
        $this->requireAuth($request);
        $topicId = $this->requireId($request->query, 'topic');

        /** @var TopicVisitorRecorder $recorder */
        $recorder = $this->get('lychee.module.topic.visitor_recorder');
        $visitorIds = $recorder->getTopicLatestVisitors($topicId);

        $synthesizer = $this->getSynthesizerBuilder()->buildSimpleUserSynthesizer($visitorIds);

        return $this->dataResponse(array('users' => $synthesizer->synthesizeAll()));
    }

    /**
     * @Route("/apply_follow")
     * @Method("POST")
     * @ApiDoc(
     *   section="topic",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="topic", "dataType"="integer", "required"=true},
     *     {"name"="description", "dataType"="string", "required"=true},
     *   }
     * )
     */
    public function applyToFollowAction(Request $request) {
        $account = $this->requireAuth($request);
        $topicId = $this->requireId($request->request, 'topic');
        $description = $this->requireParam($request->request, 'description');
        if (mb_strlen($description, 'utf8') > 50) {
            return $this->errorsResponse(CommonError::ParameterInvalid('description', $description));
        }

        $topic = $this->topic()->fetchOne($topicId);
        if ($topic == null) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }
        if ($topic->private == false) {
            return $this->failureResponse();
        }

        /** @var ApplyService $applyService */
        $applyService = $this->get('lychee.module.topic.following_apply');
        $didApplied = $applyService->apply($account->id, $topicId, $description);
        if ($didApplied) {
            /** @var NotificationService $notificationService */
            $notificationService = $this->get('lychee.module.notification');
            $notificationService->notifyApplyToFollowEvent($topic->managerId, $account->id, $topicId, $description);
        }

        return $this->sucessResponse();
    }

    /**
     * @Route("/application/list")
     * @Method("GET")
     * @ApiDoc(
     *   section="topic",
     *   description="获取话题请求入驻的列表",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="topic", "dataType"="integer", "required"=true},
     *     {"name"="cursor", "dataType"="string", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function listTopicApplication(Request $request) {
        $account = $this->requireAuth($request);
        $topicId = $this->requireId($request->query, 'topic');

        $topic = $this->topic()->fetchOne($topicId);
        if ($topic == null || $topic->deleted) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }
        if ($topic->managerId != $account->id) {
            return $this->errorsResponse(TopicError::YouAreNotManager());
        }

        list($cursor, $count) = $this->getStringCursorAndCount($request->query, 20, 50);

        /** @var ApplyService $applyService */
        $applyService = $this->get('lychee.module.topic.following_apply');
        $applications = $applyService->fetchApplicationsByTopic($topicId, $cursor, $count, $nextCursor);

        $userIds = ArrayUtility::columns($applications, 'applicantId');
        $userSynthesizer = $this->getSynthesizerBuilder()->buildSimpleUserSynthesizer($userIds);

        $result = array();
        foreach ($applications as $a) {
            /** @var TopicFollowingApplication $a */
            $result[] = array(
                'user' => $userSynthesizer->synthesizeOne($a->applicantId),
                'time' => $a->applyTime,
                'description' => $a->applyDescription
            );
        }

        return $this->arrayResponse('applications', $result, $nextCursor);
    }

    /**
     * @Route("/application/confirm")
     * @Method("POST")
     * @ApiDoc(
     *   section="topic",
     *   description="通过入驻请求",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="topic", "dataType"="integer", "required"=true},
     *     {"name"="applicant", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function confirmApplication(Request $request) {
        $account = $this->requireAuth($request);
        $topicId = $this->requireId($request->request, 'topic');

        $topic = $this->topic()->fetchOne($topicId);
        if ($topic == null || $topic->deleted) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }
        if ($topic->managerId != $account->id) {
            return $this->errorsResponse(TopicError::YouAreNotManager());
        }

        $applicant = $this->requireId($request->request, 'applicant');
        /** @var ApplyService $applyService */
        $applyService = $this->get('lychee.module.topic.following_apply');
        try {
            $confirmed = $applyService->confirm($topicId, $applicant);
        } catch (TopicMissingException $e) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        } catch (FollowingTooMuchTopicException $e) {
            return $this->sucessResponse();
        }

        if ($confirmed) {
            try {
                /** @var NotificationService $notificationService */
                $notificationService = $this->get('lychee.module.notification');
                $notificationService->notifyApplicationConfirmedEvent($applicant, $account->id, $topicId);
            } catch (\Exception $e) {
                //失败则不处理
            }
        }

        return $this->sucessResponse();
    }

    /**
     * @Route("/application/reject")
     * @Method("POST")
     * @ApiDoc(
     *   section="topic",
     *   description="拒绝入驻请求",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="topic", "dataType"="integer", "required"=true},
     *     {"name"="applicant", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function rejectApplication(Request $request) {
        $account = $this->requireAuth($request);
        $topicId = $this->requireId($request->request, 'topic');

        $topic = $this->topic()->fetchOne($topicId);
        if ($topic == null || $topic->deleted) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }
        if ($topic->managerId != $account->id) {
            return $this->errorsResponse(TopicError::YouAreNotManager());
        }

        $applicant = $this->requireId($request->request, 'applicant');
        /** @var ApplyService $applyService */
        $applyService = $this->get('lychee.module.topic.following_apply');
        $rejected = $applyService->reject($topicId, $applicant);

        if ($rejected) {
            /** @var NotificationService $notificationService */
            $notificationService = $this->get('lychee.module.notification');
            $notificationService->notifyApplicationRejectedEvent($applicant, $account->id, $topicId);
        }

        return $this->sucessResponse();
    }

    /**
     * @Route("/kickout")
     * @Method("post")
     * @ApiDoc(
     *   section="topic",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="topic", "dataType"="integer", "required"=true},
     *     {"name"="user", "dataType"="integer", "required"=true},
     *   }
     * )
     */
    public function kickoutAction(Request $request) {
        $account = $this->requireAuth($request);
        $topicId = $this->requireId($request->request, 'topic');
        $userId = $this->requireId($request->request, 'user');

        $topic = $this->topic()->fetchOne($topicId);
        if ($topic == null || $topic->deleted) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }
        if ($topic->managerId != $account->id
            && $this->coreMember()->isCoreMember($topic->id, $account->id) == false) {
            return $this->errorsResponse(TopicError::YouAreNotManager());
        }

        try {
            $didUnfollowed = $this->topicFollowing()->unfollow($userId, $topicId);
            if ($didUnfollowed) {
                $this->coreMember()->removeCoreMember($topicId, $userId);
            }
        } catch (\Exception $e) {
            return $this->failureResponse();
        }
        if ($didUnfollowed) {
            /** @var NotificationService $notificationService */
            $notificationService = $this->get('lychee.module.notification');
            $notificationService->notifyTopicKickoutEvent($userId, $topic->managerId, $topic->id);
        }

        return $this->sucessResponse();
    }

    /**
     * @return TopicCoreMemberService
     */
    private function coreMember() {
        return $this->get('lychee.module.topic.core_member');
    }

    /**
     * @Route("/core_member/add")
     * @Method("post")
     * @ApiDoc(
     *   section="topic",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="topic", "dataType"="integer", "required"=true},
     *     {"name"="user", "dataType"="integer", "required"=true},
     *     {"name"="title", "dataType"="string", "required"=true},
     *   }
     * )
     */
    public function addCoreMember(Request $request) {
        $account = $this->requireAuth($request);
        $topicId = $this->requireId($request->request, 'topic');
        $userId = $this->requireId($request->request, 'user');

        $topic = $this->topic()->fetchOne($topicId);
        if ($topic == null || $topic->deleted) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }
        if ($topic->managerId != $account->id) {
            return $this->errorsResponse(TopicError::YouAreNotManager());
        }
        if ($this->topicFollowing()->isFollowing($userId, $topicId) == false) {
            return $this->errorsResponse(TopicError::RequireFollow());
        }
        if ($topic->managerId == $userId) {
            return $this->failureResponse();
        }

        $title = $this->requireParam($request->request, 'title');
        if ($this->isValueValid($title, array(new Length(array('min' => 1, 'max' => 6)),
                new NotSensitiveWord())) !== true) {
            return $this->errorsResponse(TopicError::CoreMemberTitleInvalid());
        }

        try {
            $didAdd = $this->coreMember()->addCoreMember($topicId, $userId, $title);
        } catch (TooMuchCoreMemberException $e) {
            return $this->errorsResponse(TopicError::TooMuchCoreMember());
        }
        if ($didAdd) {
            $this->get('lychee.module.notification')
                ->notifyBecomeCoreMemberEvent($userId, $topicId, $topic->managerId);
        }

        return $this->sucessResponse();
    }

    /**
     * @Route("/core_member/remove")
     * @Method("post")
     * @ApiDoc(
     *   section="topic",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="topic", "dataType"="integer", "required"=true},
     *     {"name"="user", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function removeCoreMember(Request $request) {
        $account = $this->requireAuth($request);
        $topicId = $this->requireId($request->request, 'topic');
        $userId = $this->requireId($request->request, 'user');

        $topic = $this->topic()->fetchOne($topicId);
        if ($topic == null || $topic->deleted) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }
        if ($topic->managerId != $account->id) {
            return $this->errorsResponse(TopicError::YouAreNotManager());
        }

        $didRemove = $this->coreMember()->removeCoreMember($topicId, $userId);
        if ($didRemove) {
            $this->get('lychee.module.notification')
                ->notifyRemoveCoreMemberEvent($userId, $topicId, $topic->managerId);
        }

        return $this->sucessResponse();
    }

    /**
     * @Route("/core_member/update_title")
     * @Method("post")
     * @ApiDoc(
     *   section="topic",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="topic", "dataType"="integer", "required"=true},
     *     {"name"="user", "dataType"="integer", "required"=true},
     *     {"name"="title", "dataType"="string", "required"=true},
     *   }
     * )
     */
    public function updateCoreMemberTitle(Request $request) {
        $account = $this->requireAuth($request);
        $topicId = $this->requireId($request->request, 'topic');
        $userId = $this->requireId($request->request, 'user');

        $topic = $this->topic()->fetchOne($topicId);
        if ($topic == null || $topic->deleted) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }
        if ($topic->managerId != $account->id) {
            return $this->errorsResponse(TopicError::YouAreNotManager());
        }

        $title = $this->requireParam($request->request, 'title');
        if ($this->isValueValid($title, array(new Length(array('min' => 1, 'max' => 6)),
                new NotSensitiveWord())) !== true) {
            return $this->errorsResponse(TopicError::CoreMemberTitleInvalid());
        }

        $this->coreMember()->updateTitle($topicId, $userId, $title);

        return $this->sucessResponse();
    }

    /**
     * @Route("/core_member/order")
     * @Method("post")
     * @ApiDoc(
     *   section="topic",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="topic", "dataType"="integer", "required"=true},
     *     {"name"="ordered_users", "dataType"="string", "required"=true,
     *          "description"="排序好的核心用户id，用半角逗号分隔，除领主外全部核心用户id一齐提交,不然排序不一定就是提交的结果"}
     *   }
     * )
     */
    public function orderCoreMember(Request $request) {
        $account = $this->requireAuth($request);
        $topicId = $this->requireId($request->request, 'topic');
        $orderedUserIds = $this->getRequestIds($request->request, 'ordered_users', 10, true);

        $topic = $this->topic()->fetchOne($topicId);
        if ($topic == null || $topic->deleted) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }
        if ($topic->managerId != $account->id) {
            return $this->errorsResponse(TopicError::YouAreNotManager());
        }

        $this->coreMember()->updateOrder($topicId, $orderedUserIds);
        return $this->sucessResponse();
    }

    /**
     * @Route("/core_member/list")
     * @Method("get")
     * @ApiDoc(
     *   section="topic",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="topic", "dataType"="integer", "required"=true},
     *   }
     * )
     */
    public function getCoreMembers(Request $request) {
        $account = $this->requireAuth($request);
        $topicId = $this->requireId($request->query, 'topic');

        $topic = $this->topic()->fetchOne($topicId);
        if ($topic == null || $topic->deleted) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }
        if ($topic->private && $this->topicFollowing()->isFollowing($account->id, $topicId) == false) {
            return $this->errorsResponse(TopicError::RequireFollow());
        }

        $coreMembers = $this->coreMember()->getCoreMembers($topicId);
        $userIds = array($topic->managerId);
        $param = array();
        foreach ($coreMembers as $coreMember) {
            $userIds[] = $coreMember->userId;
            $param[] = array($topicId, $coreMember->userId);
        }

        $resolver = $this->coreMember()->getTitleResolver($param);
        $userSynthesizer = $this->getSynthesizerBuilder()
            ->buildSimpleUserSynthesizer($userIds, $resolver);
        $data = $userSynthesizer->synthesizeAll($topicId);
        $data[0]['topic_title'] = '领主';
        return $this->dataResponse($data);
    }

    /**
     * @Route("/announce")
     * @Method("post")
     * @ApiDoc(
     *   section="topic",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="post", "dataType"="integer", "required"=true},
     *   }
     * )
     */
    public function announceAction(Request $request) {
        $account = $this->requireAuth($request);
        $postId = $this->requireId($request->request, 'post');

        $post = $this->post()->fetchOne($postId);
        if ($post == null) {
            return $this->errorsResponse(PostError::PostNotExist($postId));
        }

        if ($post->topicId <= 0) {
            return $this->errorsResponse(CommonError::ParameterInvalid('post', $postId));
        }

        $topic = $this->topic()->fetchOne($post->topicId);
        if ($topic == null || $topic->deleted) {
            return $this->errorsResponse(TopicError::TopicNotExist($post->topicId));
        }

        if ($topic->managerId != $account->id
            && $this->coreMember()->isCoreMember($topic->id, $account->id) == false) {
            return $this->errorsResponse(TopicError::YouAreNotManager());
        }

        /** @var TopicAnnouncementService $announceService */
        $announceService = $this->get('lychee.module.topic.announce');
        $announced = $announceService->announce($topic->id, $post->id);
        if ($announced) {
            return $this->sucessResponse();
        } else {
            return $this->errorsResponse(TopicError::AnnounceTooFrequently());
        }
    }

    /**
     * @Route("/certified/list")
     * @Method("GET")
     * @ApiDoc(
     *   section="topic",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function listCertifiedTopicAction(Request $request) {
        $account = $this->requireAuth($request);
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);

        $topicIds = $this->topic()->listCertifiedTopics($cursor, $count, $nextCursor);

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildSimpleTopicSynthesizer($topicIds, $account ? $account->id : 0);
        return $this->arrayResponse(
            'topics', $synthesizer->synthesizeAll(), $nextCursor
        );
    }

    /**
     * @Route("/count")
     * @Method("GET")
     * @ApiDoc(
     *   section="topic",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true}
     *   }
     * )
     */
    public function getAllTopicCount(Request $request) {
        $account = $this->requireAuth($request);
        $count = $this->topic()->allTopicCount();
        return $this->dataResponse(array('count' => intval($count)));
    }

    /**
     *
     * ### 返回内容 ###
     *
     * ```
     * {
     * "list": [
     * {
     * "group_id": 1,   // 分类id
     * "group_name": "分类1",  //分类名称
     * "topics": [   //次元列表
     * {
     * "id": 1,   //次元id
     * "title": "次元1",   //次元名称
     * "followers_count" : 11111,
     * "index_image": "http://qn.ciyocon.com/upload/Ftukf1ei0TZCsBboXvZAQWQaXShz"  //次元封面
     * },
     * {
     * "id": 2,
     * "title": "次元2",
     * "followers_count" : 11111,
     * "index_image": "http://qn.ciyocon.com/upload/Ftukf1ei0TZCsBboXvZAQWQaXShz"
     * }
     * ]
     * },
     * {
     * "group_id": 3,
     * "group_name": "分类3",
     * "topics": [
     * {
     * "id": 1,
     * "title": "次元1",
     * "followers_count" : 11111,
     * "index_image": "http://qn.ciyocon.com/upload/Ftukf1ei0TZCsBboXvZAQWQaXShz"
     * },
     * {
     * "id": 2,
     * "title": "次元2",
     * "followers_count" : 11111,
     * "index_image": "http://qn.ciyocon.com/upload/Ftukf1ei0TZCsBboXvZAQWQaXShz"
     * }
     * ]
     * }
     * ],
     * "next_cursor": "0" //用于查询下一页的cursor参数，为0即没有下一页
     * }
     *
     * ```
     *
     * @Route("/groups/list")
     * @Method("GET")
     * @ApiDoc(
     *   description="获取次元分组与对应分组下的次元列表",
     *   section="topic",
     *   parameters={
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认50，最多不超过100"}
     *   }
     * )
     */
    public function getListByGroupsAction(Request $request) {
        list($cursor, $count) = $this->getCursorAndCount($request->query, 50, 100);

        $topicService = $this->topic();

        $groupRels = $topicService->getGroupRelations($cursor, $count, $nextCursor);

        $topicIds = [];
        foreach ($groupRels as $item) {
            if (empty($item['topicIds'])) {
                continue;
            }
            $topicIds = array_merge($topicIds, $item['topicIds']);
        }

        if ($topicIds) {
            $topics = $topicService->fetch($topicIds);
        }

        $list = [];

        foreach ($groupRels as $item) {

            $retItem = [];
            $retItem['group_id'] = $item['groupId'];
            $retItem['group_name'] = $item['groupName'];
            $retItem['topics'] = [];

            foreach ($item['topicIds'] as $topicId) {
                if (empty($topics[$topicId])) {
                    continue;
                }
                $topic = $topics[$topicId];
                $topicItem = [];
                $topicItem['id'] = $topic->id;
                $topicItem['title'] = $topic->title;
                $topicItem['index_image'] = $topic->indexImageUrl;
                $topicItem['followers_count'] = intval($topic->followerCount);
                $retItem['topics'][] = $topicItem;
            }

            $list[] = $retItem;

        }


        return $this->arrayResponse(
            'list', $list, $nextCursor
        );
    }

} 