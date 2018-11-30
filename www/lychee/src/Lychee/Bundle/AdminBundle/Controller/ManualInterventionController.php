<?php

namespace Lychee\Bundle\AdminBundle\Controller;

use Lychee\Bundle\AdminBundle\Components\Foundation\Paginator;
use Lychee\Bundle\AdminBundle\Entity\CustomizeContent;
use Lychee\Bundle\AdminBundle\Service\DuplicateCustomizeContentException;
use Lychee\Component\KVStorage\MemcacheStorage;
use Lychee\Module\Recommendation\UserRankingType;

use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Component\Foundation\ImageUtility;
use Lychee\Module\Notification\Entity\OfficialNotification;
use Lychee\Module\Notification\Entity\OfficialNotificationPush;
use Lychee\Module\Post\PostAnnotation;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Lychee\Component\Storage\QiniuStorage;
use Lychee\Module\Recommendation\AppChannelManagement;
use Lychee\Module\Recommendation\Entity\SubBanner;
use Lychee\Module\Game\Entity\Game;
use Lychee\Module\Recommendation\Entity\Column;
use Lychee\Module\Recommendation\Entity\ColumnElement;
use Lychee\Module\Recommendation\Entity\EditorChoiceTopic;
use Lychee\Module\Recommendation\Entity\EditorChoiceTopicCategory;
use Lychee\Module\Recommendation\Entity\RecommendationCronJob;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Storage\Resource;
use Lychee\Component\Storage\StorageException;
use Lychee\Module\Recommendation\Entity\Banner;
use Lychee\Module\Recommendation\Entity\RecommendationItem;
use Lychee\Module\Recommendation\Entity\SpecialSubject;
use Lychee\Module\Recommendation\Entity\SpecialSubjectRelation;
use Lychee\Module\Recommendation\Post\GroupPostsService;
use Lychee\Module\Recommendation\Post\SettleProcessor;
use Lychee\Module\Recommendation\RecommendationType;
use Lychee\Module\Topic\Entity\TopicCategory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


/**
 * Class ManualInterventionController
 * @package Lychee\Bundle\AdminBundle\Controller
 * @Route("/manual_intervention")
 */
class ManualInterventionController extends BaseController
{
    /**
     * @return string
     */
    public function getTitle()
    {
        return '内容推荐';
    }

    /**
     * @Route("/")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function indexAction()
    {
        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_postrecommendation'));
    }

    /**
     * @deprecated
     * @Route("/group")
     * @Template
     * @return array
     */
    public function groupAction()
    {
        $reflector = new \ReflectionClass('Lychee\Module\Recommendation\RecommendationType');
        $groups = $this->recommendation()->fetchAllGroups();
        krsort($groups);
        $now = new \DateTime();
        $pastGroups = [];
        $futureGroups = [];
        $inProgressGroups = [];
        $pastGroupsCounter = 0;
        foreach ($groups as $group) {
            if ($group->getStartTime() > $now) {
                $futureGroups[] = $group;
            } elseif ($group->getEndTime() < $now) {
                if ($pastGroupsCounter >= 5) {
                    continue;
                }
                $pastGroups[] = $group;
                $pastGroupsCounter++;
            } else {
                $inProgressGroups[] = $group;
            }
        }

        return $this->response('推荐排期', array(
            'recommendationTypes' => $reflector->getConstants(),
            'groups' => $groups,
            'future' => $futureGroups,
            'inProgress' => $inProgressGroups,
            'past' => $pastGroups,
        ));
    }

    /**
     * @deprecated
     * @Route("/add_group")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function addGroupAction(Request $request)
    {
        $group = new RecommendationGroup();
        $group->setType($request->request->get('recommendation_type'))
            ->setStartTime(new \DateTime($request->request->get('start_time')))
            ->setEndTime(new \DateTime($request->request->get('end_time')));
        $validator = $this->get('validator');
        $errors = $validator->validate($group);
        if (count($errors) > 0) {
            $errorsString = (string) $errors;

            return new Response($errorsString);
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($group);
        $em->flush();

        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_group'));
    }

    /**
     * @deprecated
     * @Route("/group/{gid}/item")
     * @Template
     * @param $gid
     * @return array|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function itemAction($gid)
    {
        $group = $this->recommendation()->fetchGroupById($gid);
        if (null === $group) {
            throw $this->createNotFoundException('Group Not Found.');
        }
        $items = $group->getItems();

        return $this->response('项目', array(
            'errors' => $this->get('Request')->query->get('errors'),
            'items' => $items,
        ));
    }

    /**
     * @deprecated
     * @Route("/add_item")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws \Exception
     */
    public function addItemAction(Request $request)
    {
        $groupId = $request->request->get('group_id');
        $position = $request->request->get('position') - 1;
        $group = $this->recommendation()->fetchGroupById($groupId);
        if (null === $group) {
            throw $this->createNotFoundException('Group Not Found.');
        }

        $overlappingGroups = $this->getOverlappingGroups($group);
        if ($this->isPositionOverlapping($position, $overlappingGroups)) {
            $errors = '位置发生了重复。';

            return $this->redirect($this->generateUrl('lychee_admin_manualintervention_item', array(
                'gid' => $groupId,
                'errors' => $errors,
            )));
        }

        $item = new RecommendationItem();
        $item->setGroup($group)
            ->setTargetId($request->request->get('item_id'))
            ->setReason($request->request->get('reason'))
            ->setPosition($position);
        if (true === $request->files->has('image')) {
            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $item->setImage($this->upload()->saveImage($imageFile));
            }
        }

        $validator = $this->get('validator');
        $errors = $validator->validate($item);
        if (count($errors) > 0) {
            $errorsString = (string) $errors;

            return new Response($errorsString);
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($item);
        $em->flush();

        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_item', array(
            'gid' => $groupId,
        )));
    }

    /**
     * @deprecated
     * @Route("/delete_item")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function deleteItemAction(Request $request)
    {
        $item = $this->recommendation()->fetchItemById($request->request->get('item_id'));
        if (null === $item) {
            throw $this->createNotFoundException('Item Not Found.');
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($item);
        $em->flush();

        return new JsonResponse();
    }

    /**
     * @deprecated
     * @param RecommendationGroup $recommendationGroup
     * @return array
     */
    private function getOverlappingGroups(RecommendationGroup $recommendationGroup)
    {
        $allGroups = $this->recommendation()->fetchAllGroups();
        $overlappingGroups = [];

        foreach ($allGroups as $group) {
            if ($group->getId() === $recommendationGroup->getId()) {
                continue;
            }
            if ($group->getType() === $recommendationGroup->getType()) {
                if (
                    $recommendationGroup->getStartTime() > $group->getStartTime() &&
                    $recommendationGroup->getStartTime() < $group->getEndTime() ||
                    $recommendationGroup->getEndTime() > $group->getStartTime() &&
                    $recommendationGroup->getEndTime() < $group->getEndTime() ||
                    $recommendationGroup->getStartTime() < $group->getStartTime() &&
                    $recommendationGroup->getEndTime() > $group->getEndTime() ||
                    $recommendationGroup->getStartTime() > $group->getStartTime() &&
                    $recommendationGroup->getEndTime() < $group->getEndTime()
                ) {
                    $overlappingGroups[] = $group;
                }
            }
        }

        return $overlappingGroups;
    }

    /**
     * @deprecated
     * @param $position
     * @param $overlappingGroups
     * @return bool
     */
    private function isPositionOverlapping($position, $overlappingGroups)
    {
        foreach ($overlappingGroups as $group) {
            $items = $group->getItems();
            foreach ($items as $item) {
                if ($position == $item->getPosition()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @deprecated
     * @Route("/past_groups")
     * @Template
     * @return array
     */
    public function morePastGroupsAction()
    {
        $groups = $this->recommendation()->fetchAllGroups();
        $pastGroups = [];
        $now = new \DateTime();
        foreach ($groups as $group) {
            if ($group->getEndTime() < $now) {
                $pastGroups[] = $group;
            }
        }
        krsort($pastGroups);

        return $this->response('已结束的排期', array(
            'pastGroups' => $pastGroups,
        ));
    }

    /**
     * @deprecated
     * @Route("/group/detail/{gid}", requirements={"id"="\d+"})
     * @Template
     * @param $gid
     * @return array|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function groupDetailAction($gid)
    {
        $group = $this->recommendation()->fetchGroupById($gid);
        if (null === $group) {
            throw $this->createNotFoundException('Group Not Found');
        }

        return $this->response('修改排期', array(
            'group' => $group,
        ));
    }

    /**
     * @deprecated
     * @Route("/modify_group")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function modifyGroupAction(Request $request)
    {
        $gid = $request->request->get('group_id');
        $group = $this->recommendation()->fetchGroupById($gid);
        if (null === $group) {
            throw $this->createNotFoundException('Group Not Found');
        }
        $startTime = new \DateTime($request->request->get('group_start_time'));
        $endTime = new \DateTime($request->request->get('group_end_time'));
        if ($startTime !== $group->getStartTime() || $endTime !== $group->getEndTime()) {
            $group->setStartTime($startTime);
            $group->setEndTime($endTime);
            $em = $this->getDoctrine()->getManager();
            $em->flush($group);
        }

        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_group'));
    }

    /**
     * @deprecated
     * @Route("/delete_group")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function deleteGroupAction(Request $request)
    {
        $gid = $request->request->get('gid');
        $group = $this->recommendation()->fetchGroupById($gid);
        if (null === $group) {
            throw $this->createNotFoundException('Group Not Found');
        }
        $this->recommendation()->removeGroup($group);

        return new JsonResponse();
    }

    /**
     * @Route("/post_recommendation/{cursor}", requirements={"cursor" = "\d+"})
     * @Template
     * @param int $cursor
     * @return array
     */
    public function postRecommendationAction($cursor = 0) {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $date = $request->query->get('date');
        if (!$date) {
            $date = null;
        } else {
            $date = new \DateTime($date);
        }
        $items = $this->recommendation()->listRecommendedItems(RecommendationType::POST, $cursor, 15, $nextCursor, $date);
        $postIds = array_map(function ($item) {
            return $item->getTargetId();
        }, $items);
        $posts = $this->post()->fetch($postIds);
        $userIds = array_map(function ($item) {
            return $item->authorId;
        }, $posts);
        $posts = ArrayUtility::mapByColumn($posts, 'id');
        $users = $this->account()->fetch($userIds);
        $users = ArrayUtility::mapByColumn($users, 'id');

        return $this->response('帖子推荐', [
            'items' => $items,
            'posts' => $posts,
            'users' => $users,
            'nextCursor' => $nextCursor,
        ]);
    }

    /**
     * @Route("/to_be_publish_item/{type}")
     * @Template
     * @param $type
     * @return array
     */
    public function toBePublishItemAction($type) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(RecommendationCronJob::class);
        $items = $repo->findBy([
            'recommendationType' => $type
        ], [
            'publishTime' => 'ASC'
        ]);
        $targetIds = array_map(function ($item) {
            return $item->getRecommendationId();
        }, $items);
        if ($type === RecommendationType::SPECIAL_SUBJECT) {
            $specialSubjects = $this->specialSubject()->fetch($targetIds);

            return $this->render('LycheeAdminBundle:ManualIntervention:toBePublishSpecialSubject.html.twig', [
                'title' => $this->getTitle(),
                'subTitle' => '待发布专题',
                'specialSubjects' => $specialSubjects,
                'items' => ArrayUtility::mapByColumn($items, 'recommendationId'),
            ]);
        }
        else{
            $posts = $this->post()->fetch($targetIds);
            $authorIds = array_map(function ($item) {
                return $item->authorId;
            }, $posts);
            $authors = $this->account()->fetch($authorIds);
            $authors = ArrayUtility::mapByColumn($authors, 'id');

            return $this->response('待发布帖子', [
                'authors' => $authors,
                'posts' => $posts,
                'items' => $items,
            ]);
        }

    }

    /**
     * @Route("/remove_cronjob")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeCronJobAction(Request $request) {
        $id = $request->request->get('id');
        $callback = $request->request->get('callback_url');
        $em = $this->getDoctrine()->getManager();
        $item = $em->getRepository(RecommendationCronJob::class)->find($id);
        if (null !== $item) {
            $em->remove($item);
            $em->flush();
        }
        if (!$callback) {
            $callback = $this->generateUrl('lychee_admin_manualintervention_tobepublishitem', [
                'type' => RecommendationType::POST
            ]);
        }

        return $this->redirect($callback);
    }

    /**
     * @Route("/user_recommendation/{cursor}", requirements={"cursor" = "\d+"})
     * @Template
     * @param int $cursor
     * @return array
     */
    public function userRecommendationAction($cursor = 0) {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $date = $request->query->get('date');
        if (!$date) {
            $date = null;
        } else {
            $date = new \DateTime($date);
        }
        $items = $this->recommendation()->listRecommendedItems(RecommendationType::USER, $cursor, 15, $nextCursor, $date);
        $userIds = array_map(function ($item) {
            return $item->getTargetId();
        }, $items);
        $users = $this->account()->fetch($userIds);
        $users = ArrayUtility::mapByColumn($users, 'id');

        return $this->response('用户推荐', [
            'items' => $items,
            'users' => $users,
            'nextCursor' => $nextCursor,
        ]);
    }

    /**
     * @Route("/recommendation")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function createRecommendationAction(Request $request) {
        $callbackUrl = $request->request->get('callback_url');
        $type = $request->request->get('type');
        $targetId = $request->request->get('target_id');
        $reason = $request->request->get('reason');
        $publishInstantly = $request->request->get('publish_instantly');
        $publishTime = $request->request->get('publish_time');
        $stickPost = $request->request->get('stick_post');
        $imageUrl = null;
        do {
            if ($request->files->has('image')) {
                $imageFile = $request->files->get('image');
                if ($imageFile) {
                    $imageUrl = $this->storage()->setPrefix('recommendation/')->put($imageFile);
                    break;
                }
            }
            if (RecommendationType::TOPIC === $type) {
                $topic = $this->topic()->fetchOne($targetId);
                if (null !== $topic) {
                    $imageUrl = $topic->indexImageUrl;
                    $this->recommendation()->addRecommendableTopics($topic->id);
                } else {
                    return $this->redirect($this->generateUrl('lychee_admin_error', [
                        'errorMsg' => '次元不存在, 请检查推荐ID',
                        'callbackUrl' => $request->headers->get('referer'),
                    ]));
                }
            }
        } while(0);
        $em = $this->getDoctrine()->getManager();
        if ($publishInstantly) {
            $recommendation = new RecommendationItem();
            $recommendation->setTargetId($targetId)->setType($type)->setReason($reason)->setImage($imageUrl);
            $em->persist($recommendation);
            if ($type === RecommendationType::POST) {
                if ($stickPost && $stickPost == '1') {
                    $em->flush();
                    $this->recommendation()->stickItem($recommendation->getId());
                }
                /** @var SettleProcessor $settleProcessor */
                $settleProcessor = $this->container()->get('lychee.module.recommendation.group_posts_settle_processor');
                /** @var GroupPostsService $groupPostsService */
                $groupPostsService = $this->container()->get('lychee.module.recommendation.group_posts');
                $result = $settleProcessor->process([$targetId]);
                foreach ($result->getIterator() as $groupId => $groupPostIds) {
                    $groupPostsService->addPostIdsToGroup($groupId, $groupPostIds, true);
                }
            }
        } else {
            $recommendationCronJob = new RecommendationCronJob();
            $recommendationCronJob->setRecommendationType($type);
            $recommendationCronJob->setRecommendationId($targetId);
            $recommendationCronJob->setPublishTime(new \DateTime($publishTime));
            $recommendationCronJob->setRecommendedReason($reason);
            $recommendationCronJob->setImage($imageUrl);
            if ($type === RecommendationType::POST && $stickPost && $stickPost == '1') {
                $recommendationCronJob->setAnnotation(json_encode([
                    'sticky_post' => true
                ]));
            }
            $em->persist($recommendationCronJob);
        }
        $em->flush();
        $this->clearCache();

        return $this->redirect($callbackUrl);
    }

    /**
     * @Route("/recommendation/delete")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function deleteRecommendationAction(Request $request) {
        $id = $request->request->get('id');
        $callbackUrl = $request->request->get('callback_url');
        /**
         * @var \Lychee\Module\Recommendation\Entity\RecommendationItem|null $item
         */
        $item = $this->recommendation()->fetchItemById($id);

        if (null === $item) {
            throw $this->createNotFoundException();
        }
        $this->recommendation()->removeRecommendedItem($item);
        $this->clearCache();

        return $this->redirect($callbackUrl);
    }

    /**
     * @Route("/special_subject")
     * @Method("GET")
     * @Template
     * @param Request $request
     * @return array
     */
    public function specialSubjectAction(Request $request) {
        $cursor = $request->query->get('cursor', 0);
        $specialSubjects = $this->specialSubject()->fetchSpecialSubjectByCursor($cursor, 20, $nextCursor);

        return $this->response('专题列表', [
            'specialSubjects' => $specialSubjects,
            'nextCursor' => $nextCursor,
        ]);
    }

    /**
     * @Route("/special_subject/delete")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeSpecialSubjectAction(Request $request) {
        $specialSubjectId = $request->request->get('id');
        $items = $this->recommendation()->fetchItemByTarget($specialSubjectId, RecommendationType::SPECIAL_SUBJECT);
        foreach ($items as $item) {
            $this->recommendation()->removeRecommendedItem($item);
        }
        $this->specialSubject()->delete($specialSubjectId);
        $this->clearCache();

        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_specialsubject'));
    }

    /**
     * @Route("/recommendation_special_subject/{cursor}", requirements={"cursor" = "\d+"})
     * @Template
     * @param int $cursor
     * @return array
     */
    public function recommendationSpecialSubjectAction($cursor = 0) {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $date = $request->query->get('date');
        if (!$date) {
            $date = null;
        } else {
            $date = new \DateTime($date);
        }
        $items = $this->recommendation()->listRecommendedItems(
            RecommendationType::SPECIAL_SUBJECT,
            $cursor,
            20,
            $nextCursor,
            $date
        );
        $specialSubjectIds = array_map(function ($item) {
            return $item->getTargetId();
        }, $items);
        $specialSubjects = $this->specialSubject()->fetch($specialSubjectIds);
        $specialSubjects = ArrayUtility::mapByColumn($specialSubjects, 'id');

        return $this->response('专题推荐', [
            'items' => $items,
            'specialSubjects' => $specialSubjects,
            'nextCursor' => $nextCursor,
        ]);
    }

    /**
     * @Route("/special_subject/create")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Exception
     */
    public function createSpecialSubjectAction(Request $request) {
        $desc = $request->request->get('description');
        if (!$request->files->has('banner')) {
            throw $this->createNotFoundException('Banner Not Found.');
        }
        $name = $request->request->get('name');
        $types = $request->request->get('type');
        $associatedIds = $request->request->get('associated_id');

        $banner = $request->files->get('banner');
        if (null === $banner) {
            throw $this->createNotFoundException('Banner Not Found.');
        }
        $bannerUrl = $this->storage()->setPrefix('special-subject/')->put($banner);
        $specialSubject = $this->specialSubject()->add($name, $bannerUrl, $desc);

        $posts = $topics = $users = [];
        for ($i = 0, $count = count($associatedIds); $i < $count; $i++) {
            if ($types[$i] == SpecialSubjectRelation::TYPE_POST) {
                $posts[] = $associatedIds[$i];
            } elseif ($types[$i] == SpecialSubjectRelation::TYPE_TOPIC) {
                $topics[] = $associatedIds[$i];
            } elseif ($types[$i] == SpecialSubjectRelation::TYPE_USER) {
                $users[] = $associatedIds[$i];
            }
        }
        $this->specialSubject()->addAssociated($specialSubject, $posts, $topics, $users);

        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_specialsubject'));
    }

    /**
     * @Route("/special_subject/detail/{id}", requirements={"id" = "\d+"})
     * @Template
     * @param $id
     * @return array
     */
    public function specialSubjectDetailAction($id) {
        $specialSubject = $this->specialSubject()->fetchOne($id);
        $relations = $specialSubject->getRelations();
        $posts = $topics = $users = [];
        if (null !== $relations) {
            foreach ($relations as $row) {
                $type = $row->getType();
                if (SpecialSubjectRelation::TYPE_POST === $type) {
                    $post = $this->post()->fetchOne($row->getAssociatedId());
                    if (null !== $post) {
                        $posts[] = $post;
                    }
                } elseif (SpecialSubjectRelation::TYPE_TOPIC === $type) {
                    $topic = $this->topic()->fetchOne($row->getAssociatedId());
                    if (null !== $topic) {
                        $topics[] = $topic;
                    }
                } elseif (SpecialSubjectRelation::TYPE_USER === $type) {
                    $user = $this->account()->fetchOne($row->getAssociatedId());
                    if (null !== $user) {
                        $users[] = $user;
                    }
                }
            }
        }

        return $this->response('专题详细', [
            'specialSubject' => $specialSubject,
            'posts' => $posts,
            'topics' => $topics,
            'users' => $users,
        ]);
    }

    /**
     * @Route("/special_subject/modify/{id}", requirements={"id" = "\d+"})
     * @Template
     * @param $id
     * @return array
     */
    public function modifySpecialSubjectAction($id) {
        $specialSubject = $this->getDoctrine()->getRepository(SpecialSubject::class)
            ->find($id);
        if (null === $specialSubject) {
            throw $this->createNotFoundException('Special Subject Not Found.');
        }

        return $this->response('编辑专题', [
            'specialSubject' => $specialSubject,
        ]);
    }

    /**
     * @Route("/modify_special_subject")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function doModifySpecialSubjectAction(Request $request) {
        $id = $request->request->get('id');
        /**
         * @var \Lychee\Module\Recommendation\Entity\SpecialSubject $specialSubject
         */
        $specialSubject = $this->getDoctrine()->getRepository(SpecialSubject::class)->find($id);
        if (null === $specialSubject) {
            throw $this->createNotFoundException('Special Subject Not Found.');
        }
        $name = $request->request->get('name');
        $specialSubject->getName() !== $name && $specialSubject->setName($name);
        $desc = $request->request->get('description');
        $specialSubject->getDescription() !== $desc && $specialSubject->setDescription($desc);
        if ($request->files->has('banner')) {
            $file = $request->files->get('banner');
            if (null !== $file) {
                $oldBanner = $specialSubject->getBanner();
                try {
                    $this->storage()->delete($oldBanner);
                } catch (StorageException $e) {

                }

                $bannerUrl = $this->storage()->setPrefix('special-subject/')->put($file);
                $specialSubject->setBanner($bannerUrl);
            }
        }
        $em = $this->getDoctrine()->getManager();
        foreach ($specialSubject->getRelations() as $r) {
            $em->remove($r);
        }
        $associatedIds = $request->request->get('associated_id');
        $types = $request->request->get('type');
        for ($i = 0, $len = count($associatedIds); $i < $len; $i++) {
            $type = $types[$i];
            if (in_array($type, [
                SpecialSubjectRelation::TYPE_POST,
                SpecialSubjectRelation::TYPE_TOPIC,
                SpecialSubjectRelation::TYPE_USER
            ])) {
                $relation = new SpecialSubjectRelation();
                $relation->setType($type);
                $relation->setAssociatedId($associatedIds[$i]);
                $relation->setSpecialSubject($specialSubject);
                $em->persist($relation);
                $specialSubject->addRelation($relation);
            }
        }
        $em->flush();
        $this->clearCache();

        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_modifyspecialsubject', [
            'id' => $id,
        ]));
    }

    /**
     * @Route("/app/create_recommendation")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function createRecommendationAppAction(Request $request) {
        $appId = $request->request->get('app_id');
        $app = $this->game()->fetchOne($appId);
        if (!$app) {
            throw $this->createNotFoundException('App Not Found.');
        }
        $this->recommendation()->addRecommendedItem(RecommendationType::APP, $appId, null, null);
        $this->clearCache();

        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_app'));
    }

    /**
     * @Route("/app/recommendation/delete")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteRecommendationAppAction(Request $request) {
        $itemId = $request->request->get('item_id');
        /**
         * @var \Lychee\Module\Recommendation\Entity\RecommendationItem $item
         */
        $item = $this->recommendation()->fetchItemById($itemId);
        if ($item) {
            $this->recommendation()->removeRecommendedItem($item);
            $this->clearCache();
        }

        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_app'));
    }
    
    private function clearCache() {
        $this->container->get('memcache.default')->delete('rec_web');
        $this->container->get('memcache.default')->delete('rec_web2');
    }

    /**
     * @Route("/column")
     * @Template
     * @param Request $request
     * @return array
     */
    public function columnAction(Request $request) {
        $type = $request->query->get('type');
        $count = $request->query->get('count', 20);
        $page = $request->query->get('page', 1);
        $endPage = 1;
        $topicColumns = $postColumns = $userColumns = $commentColumns = [];
        if ('unpublished' === $type) {
            $columns = $this->recommendationColumn()->fetchColumns($count, $page, false);
            $columnCount = $this->recommendationColumn()->getColumnCount(false, false);
            $endPage = ceil($columnCount / $count);
        } elseif ('deleted' === $type) {
            $columns = $this->recommendationColumn()->fetchColumns($count, $page, null, true);
            $columnCount = $this->recommendationColumn()->getColumnCount(true);
            $endPage = ceil($columnCount / $count);
        } else {
            $columns = $this->recommendationColumn()->fetchAllPublishedColumns();
            foreach ($columns as $c) {
                switch ($c->getType()) {
                    case Column::TYPE_TOPIC:
                        $topicColumns[] = $c;
                        break;
                    case Column::TYPE_POST:
                        $postColumns[] = $c;
                        break;
                    case Column::TYPE_USER:
                        $userColumns[] = $c;
                        break;
                    case Column::TYPE_COMMENT:
                        $commentColumns[] = $c;
                        break;
                    default:
                        break;
                }
            }
        }
        if ($endPage < 1) {
            $endPage = 1;
        }

        return $this->response('栏目推荐', [
            'type' => $type,
            'columns' => $columns,
            'page' => $page,
            'endPage' => $endPage,
            'pageCount' => 10,
            'topicColumns' => $topicColumns,
            'postColumns' => $postColumns,
            'userColumns' => $userColumns,
            'commentColumns' => $commentColumns,
        ]);
    }

    /**
     * @Route("/column/create")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function createColumnAction(Request $request) {
        $columnName = $request->request->get('column_name');
        $columnType = $request->request->get('column_type', Column::TYPE_POST);
        $column = new Column();
        $column->setName($columnName);
        $column->setType($columnType);
        $column->setOrder(1);
        $this->recommendationColumn()->createColumn($column);

        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_column', [
            'type' => 'unpublished',
        ]));
    }

    /**
     * @Route("/column/{columnId}", requirements={"columnId" = "\d+"})
     * @Template
     * @param $columnId
     * @return array
     */
    public function editColumnAction($columnId) {
        $column = $this->recommendationColumn()->fetchColumnById($columnId);
        $elements = $this->recommendationColumn()->fetchElements($columnId, 1000);
        switch ($column->getType()) {
            case Column::TYPE_POST:
                $typeName = '帖子';
                break;
            case Column::TYPE_TOPIC:
                $typeName = '次元';
                break;
            case Column::TYPE_USER:
                $typeName = '用户';
                break;
            case Column::TYPE_COMMENT:
                $typeName = '评论';
                break;
            default:
                $typeName = '位置';
                break;
        }

        return $this->response('栏目编辑', [
            'column' => $column,
            'typeName' => $typeName,
            'elements' => $elements,
        ]);
    }

    /**
     * @Route("/column/edit")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editColumnInfoAction(Request $request) {
        $columnId = $request->request->get('column_id');
        $columnName = $request->request->get('column_name');
        $column = $this->recommendationColumn()->fetchColumnById($columnId);
        if (null !== $column) {
            $column->setName($columnName);
            $this->recommendationColumn()->updateColumn($column);
        }

        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_editcolumn', [
            'columnId' => $columnId,
        ]));
    }

    /**
     * @Route("/column/remove")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeColumnAction(Request $request) {
        $columnId = $request->request->get('column_id');
        $this->recommendationColumn()->removeColumn($columnId);
        $this->clearCache();

        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_column', [
            'type' => 'deleted',
        ]));
    }

    /**
     * @Route("/column/recover")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function recoverColumnAction(Request $request) {
        $columnId = $request->request->get('column_id');
        $this->recommendationColumn()->recoverColumn($columnId);

        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_column', [
            'type' => 'unpublished',
        ]));
    }

    /**
     * @Route("/column/publish")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function publishColumnAction(Request $request) {
        $columnId = $request->request->get('column_id');
        $this->recommendationColumn()->publishColumn($columnId);
        $this->clearCache();

        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_column', [
            'type' => 'unpublished',
        ]));
    }

    /**
     * @Route("/column/unpublish")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function unpublishColumnAction(Request $request) {
        $columnId = $request->request->get('column_id');
        $this->recommendationColumn()->unpublishColumn($columnId);
        $this->clearCache();

        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_column'));
    }

    /**
     * @Route("/column/element/add")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function addColumnElementAction(Request $request) {
        $columnId = $request->request->get('column_id');
        $elementId = $request->request->get('element_id');
        $image = $request->request->get('image_url');
        $recommendationReason = $request->request->get('recommendation_reason');
        /**
         * @var \Lychee\Module\Recommendation\Entity\Column $column
         */
        $column = $this->recommendationColumn()->fetchOneColumn($columnId);
        do {
            if (null !== $column) {
                if ($column->getType() === Column::TYPE_POST) {
                    try {
                        $post = $this->post()->fetchOne($elementId);
                        if (null !== $post) {
                            break;
                        }
                    } catch (\Exception $e) {

                    }
                } elseif ($column->getType() === Column::TYPE_TOPIC) {
                    try {
                        $topic = $this->topic()->fetchOne($elementId);
                        if (null !== $topic) {
                            break;
                        }
                    } catch (\Exception $e) {

                    }
                } elseif ($column->getType() === Column::TYPE_USER) {
                    try {
                        $user = $this->account()->fetchOne($elementId);
                        if (null !== $user) {
                            break;
                        }
                    } catch (\Exception $e) {

                    }
                } elseif ($column->getType() === Column::TYPE_COMMENT) {
                    try {
                        $comment = $this->comment()->fetchOne($elementId);
                        if (null !== $comment) {
                            break;
                        }
                    } catch (\Exception $e) {

                    }
                }
            }
            return $this->redirect($this->generateUrl('lychee_admin_error', [
                'errorMsg' => '栏目或元素不存在',
                'callbackUrl' => $request->headers->get('referer'),
            ]));
        } while(0);
        $columnElement = new ColumnElement();
        $columnElement->setColumnId($columnId);
        $columnElement->setElementId($elementId);
        $columnElement->setRecommendationReason($recommendationReason);
        $columnElement->setOrder(1);
        if (true === $request->files->has('image')) {
            $image = $request->files->get('image');
            if ($image) {
                try {
                    $imageUrl = $this->storage()->put($image);
                    $columnElement->setImageUrl($imageUrl);
                } catch (StorageException $e) {

                }
            }
        }
        $this->recommendationColumn()->addElement($columnElement);
        $this->clearCache();

        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_editcolumn', [
            'columnId' => $columnId,
            'type' => 'element',
        ]));
    }

    /**
     * @Route("/column/element/remove")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeColumnElementAction(Request $request) {
        $columnId = $request->request->get('column_id');
        $elementId = $request->request->get('element_id');
        $this->recommendationColumn()->removeElement($columnId, $elementId);
        $this->recommendationColumn()->reorderElements($this->recommendationColumn()->fetchAllElements($columnId));
        $this->clearCache();

        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_editcolumn', [
            'columnId' => $columnId,
            'type' => 'element',
        ]));
    }

    /**
     * @Route("/column/element/order")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function orderElementAction(Request $request) {
        $columnId = $request->request->get('column_id');
        $elementIds = $request->request->get('elements');
        $this->recommendationColumn()->orderElements($columnId, $elementIds);

        return new JsonResponse();
    }

    /**
     * @Route("/column/order")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function orderColumnAction(Request $request) {
        $columnIds = $request->request->get('columns');
        $type = $request->request->get('type');
        $this->recommendationColumn()->orderColumns($columnIds, $type);

        return new JsonResponse();
    }

    /**
     * @Route("/sub_banner")
     * @Template
     * @param Request $request
     * @return array
     */
    public function subBannerAction(Request $request) {
        $type = $request->query->get('type');
        $count = $request->query->get('count', 20);
        $page = $request->query->get('page', 1);
        $endPage = 1;
        if ('unpublished' === $type) {
            $subBanners = $this->recommendationBanner()->fetchSubBanners($count, $page, false);
            $subBannerCount = $this->recommendationBanner()->getSubBannerCount(false, false);
            $endPage = ceil($subBannerCount / $count);
        } elseif ('deleted' === $type) {
            $subBanners = $this->recommendationBanner()->fetchSubBanners($count, $page, null, true);
            $subBannerCount = $this->recommendationBanner()->getSubBannerCount(true);
            $endPage = ceil($subBannerCount / $count);
        } else {
            $subBanners = $this->recommendationBanner()->fetchAllPublishedSubBanners();
        }
        if ($endPage < 1) {
            $endPage = 1;
        }

        return $this->response('广告位', [
            'type' => $type,
            'subBanners' => $subBanners,
            'page' => $page,
            'endPage' => $endPage,
            'pageCount' => 10,
        ]);
    }

    /**
     * @Route("/sub_banner/create")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function createSubBannerAction(Request $request) {
        $type = $request->request->get('type');
        $targetId = $request->request->get('target_id');
        $title = $request->request->get('title');
        $subBanner = new SubBanner();
        $subBanner->setPosition(0);
        $subBanner->setTargetId($targetId);
        $subBanner->setType($type);
        $subBanner->setTitle($title);
        do {
            if ($type === SubBanner::TYPE_GAME) {
                /**
                 * @var Game $target
                 */
                $target = $this->game()->fetchOne($targetId);
                if (null !== $target) {
                    $subBanner->setImageUrl($target->getBanner());
                    break;
                }
            } elseif ($type === SubBanner::TYPE_SPECIAL_SUBJECT) {
                /**
                 * @var \Lychee\Module\Recommendation\Entity\SpecialSubject $target
                 */
                $target = $this->specialSubject()->fetchOne($targetId);
                if (null !== $target) {
                    $subBanner->setImageUrl($target->getBanner());
                    break;
                }
            }

            return $this->redirect($this->generateUrl('lychee_admin_error', [
                'errorMsg' => '类型或目标不存在',
                'callbackUrl' => $request->headers->get('referer'),
            ]));
        } while(0);
        $this->recommendationBanner()->createSubBanner($subBanner);

        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_subbanner', [
            'type' => 'unpublished',
        ]));
    }

    /**
     * @Route("/sub_banner/remove")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeSubBannerAction(Request $request) {
        $id = $request->request->get('id');
        $this->recommendationBanner()->removeSubBanner($id);

        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_subbanner', [
            'type' => 'deleted',
        ]));
    }

    /**
     * @Route("/sub_banner/recover")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function recoverSubBannerAction(Request $request) {
        $id = $request->request->get('id');
        $this->recommendationBanner()->recoverSubBanner($id);

        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_subbanner', [
            'type' => 'unpublished',
        ]));
    }

    /**
     * @Route("/sub_banner/publish")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function publishSubBannerAction(Request $request) {
        $id = $request->request->get('id');
        $this->recommendationBanner()->publishSubBanner($id);
        $this->clearCache();

        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_subbanner', [
            'type' => 'unpublished',
        ]));
    }

    /**
     * @Route("/sub_banner/unpublish")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function unpublishSubBannerAction(Request $request) {
        $id = $request->request->get('id');
        $this->recommendationBanner()->unpublishSubBanner($id);
        $this->clearCache();

        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_subbanner'));
    }

    /**
     * @Route("/sub_banner/order")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function sortSubBannerAction(Request $request) {
        $sortIds = $request->request->get('sorted_ids');
        $this->recommendationBanner()->orderSubBanners($sortIds);

        return new JsonResponse();
    }

    /**
     * @Route("/sub_banner/title")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function getSubBannerTitleAction(Request $request) {
        $type = $request->request->get('type');
        $id = $request->request->get('id');
        if (SubBanner::TYPE_GAME === $type) {
            /**
             * @var Game $target
             */
            $target = $this->game()->fetchOne($id);
            if (null !== $target) {
                $title = $target->getTitle();
            }
        } elseif (SubBanner::TYPE_SPECIAL_SUBJECT === $type) {
            /**
             * @var \Lychee\Module\Recommendation\Entity\SpecialSubject $target
             */
            $target = $this->specialSubject()->fetchOne($id);
            $title = $target->getName();
        }
        $data = [];
        if (isset($title)) {
            $data['title'] = $title;
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/tyro_recommendation")
     * @Template()
     * @return array
     */
    public function tyroRecommendationAction() {
        $editorChoiceTopics = $this->recommendation()->fetchTopicsByEditorChoice();
        $categoryIds = array_keys($editorChoiceTopics);
        $em = $this->getDoctrine()->getManager();
        $topicCatRepo = $em->getRepository(TopicCategory::class);
        $categories = [];
        if ($categoryIds) {
            $categories = $topicCatRepo->findBy(['id' => $categoryIds]);
            $categories = ArrayUtility::mapByColumn($categories, 'id');
        }
        $topicIds = [];
        foreach ($editorChoiceTopics as $tids) {
            foreach ($tids as $tid) {
                $topicIds[] = $tid;
            }
        }
        if (!empty($topicIds)) {
            $topics = $this->topic()->fetch($topicIds);
            $topics = ArrayUtility::mapByColumn($topics, 'id');
        } else {
            $topics = [];
        }

        return $this->response('次元新手推荐', [
            'editorChoiceTopics' => $editorChoiceTopics,
            'categories' => $categories,
            'topics' => $topics,
        ]);
    }

    /**
     * @Route("/tyro_recommendation_topic_add")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function addTyroRecommendationTopicAction(Request $request) {
        $topicId = $request->request->get('target_id');
        $topic = $this->topic()->fetchOne($topicId);
        if (!$topic) {
            return $this->redirect($this->generateUrl('lychee_admin_error', [
                'errorMsg' => '次元不存在',
                'callbackUrl' => $request->headers->get('referer'),
            ]));
        }
        $categories = $this->get('lychee.module.topic.category')->getCategoriesByTopicId($topicId);
        $category = array_reduce($categories, function($result, $item) {
            if ($item['id'] >= 300) {
                $result = $item;
            }
            return $result;
        });
        if (!$category) {
            return $this->redirect($this->generateUrl('lychee_admin_error', [
                'errorMsg' => sprintf("次元 %s[%d]不存在分类,请先到次元详情页设置分类后再添加推荐。", $topic->title, $topic->id),
                'callbackUrl' => $request->headers->get('referer'),
            ]));
        }

        $this->recommendation()->addEditorChoiceTopic($category['id'], $topicId);

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/tyro_recommendation_category_sort")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function sortTyroRecommendationCatAction(Request $request) {
        $catIds = $request->request->get('cat_ids');
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(EditorChoiceTopicCategory::class);
        $cats = $repo->findAll();
        if ($cats) {
            $cats = ArrayUtility::mapByColumn($cats, 'categoryId');
            $position = 1;
            foreach ($catIds as $cid) {
                $cats[$cid]->position = $position;
                $position += 1;
            }
            $em->flush();
        }

        return new JsonResponse();
    }

    /**
     * @Route("/remove_tyro_recommendation_topic")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeTyroRecommendationTopicAction(Request $request) {
        $topicId = $request->request->get('tid');
        $catId = $request->request->get('cat_id');
        $this->recommendation()->removeEditorChoiceTopic($catId, $topicId);

        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_tyrorecommendation'));
    }

    /**
     * @Route("/sort_tyro_recommendation_topic")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function sortTyroRecommendationTopicAction(Request $request) {
        $catId = $request->request->get('cat_id');
        $topicIds = $request->request->get('topic_ids');
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(EditorChoiceTopic::class);
        $topics = $repo->findBy([
            'categoryId' => $catId
        ]);
        if ($topics) {
            $topics = ArrayUtility::mapByColumn($topics, 'topicId');
            $position = 1;
            foreach ($topicIds as $tid) {
                $topics[$tid]->position = $position;
                $position += 1;
            }
            $em->flush();
        }

        return new JsonResponse();
    }

    /**
     * @Route("/sticky_item/cancel")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function cancelStickyItemAction(Request $request) {
        $itemId = $request->request->get('id');
        $this->recommendation()->unstickItem($itemId);

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/recommendation/json")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function createRecommendationReturnJsonAction(Request $request) {
        $callbackUrl = $request->request->get('callback_url');
        $type = $request->request->get('type');
        $targetId = $request->request->get('target_id');
        $reason = $request->request->get('reason');
        $publishInstantly = $request->request->get('publish_instantly');
        $publishTime = $request->request->get('publish_time');
        $stickPost = $request->request->get('stick_post');
        $imageUrl = null;
        do {
            if ($request->files->has('image')) {
                $imageFile = $request->files->get('image');
                if ($imageFile) {
                    $imageUrl = $this->storage()->setPrefix('recommendation/')->put($imageFile);
                    break;
                }
            }
            if (RecommendationType::TOPIC === $type) {
                $topic = $this->topic()->fetchOne($targetId);
                if (null !== $topic) {
                    $imageUrl = $topic->indexImageUrl;
                    $this->recommendation()->addRecommendableTopics($topic->id);
                } else {
                    return $this->redirect($this->generateUrl('lychee_admin_error', [
                        'errorMsg' => '次元不存在, 请检查推荐ID',
                        'callbackUrl' => $request->headers->get('referer'),
                    ]));
                }
            }
        } while(0);
        $em = $this->getDoctrine()->getManager();
        if ($publishInstantly) {
            $recommendation = new RecommendationItem();
            $recommendation->setTargetId($targetId)->setType($type)->setReason($reason)->setImage($imageUrl);
            $em->persist($recommendation);
            if ($type === RecommendationType::POST && $stickPost && $stickPost == '1') {
                $em->flush();
                $this->recommendation()->stickItem($recommendation->getId());
            }
        } else {
            $recommendationCronJob = new RecommendationCronJob();
            $recommendationCronJob->setRecommendationType($type);
            $recommendationCronJob->setRecommendationId($targetId);
            $recommendationCronJob->setPublishTime(new \DateTime($publishTime));
            $recommendationCronJob->setRecommendedReason($reason);
            $recommendationCronJob->setImage($imageUrl);
            if ($type === RecommendationType::POST && $stickPost && $stickPost == '1') {
                $recommendationCronJob->setAnnotation(json_encode([
                    'sticky_post' => true
                ]));
            }
            $em->persist($recommendationCronJob);
        }
        $em->flush();
        $this->clearCache();

        return new JsonResponse();
    }

    /**
     * @Route("/live_recommendation")
     * @Template
     * @return array
     */
    public function liveRecommendationAction() {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $date = $request->query->get('date');
        $page = $request->query->get('page', 1);
        $count = $request->query->get('count', 20);
        if($date) {
            $dateTime = new \DateTime($date);
            $items = $this->live()->getLiveHistoryByDate($dateTime, $page, $count, $total);
            $pageCount = ceil($total / $count);
        }
        else {
            $items = $this->live()->fetchLiveHistoryByPage($page, $count);
            $pageCount = $this->live()->getLiveHistoryPages($count);
        }
        return $this->response('皮哲直播', [
            'items' => $items,
            'pageCount' => $pageCount,
            'page' => $page,
            'date' => $date
        ]);
    }

	/**
	 * @Route("/live/create")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function createLiveRecommendation(Request $request) {
		$pid = $request->request->get('pid');
		$liveInfo = $this->live()->getPizusLiveInfo($pid);
		$status = $liveInfo['s'];
		if ($status == 0 && isset($liveInfo['data'])) {
			$this->live()->saveLiveInfo($pid, $liveInfo['data']);
		} else {
			if ($status == 1) {
				return $this->redirectErrorPage('参数格式错误', $request);
			} elseif ($status == 2) {
				return $this->redirectErrorPage($liveInfo['m'], $request);
			}
			return $this->redirectErrorPage('未知错误', $request);
		}

		return $this->redirect($request->headers->get('referer'));
	}

    /**
     * @Route("/live/delete")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function deleteLiveRecommendationAction(Request $request) {
        $id = $request->request->get('id');
        $callbackUrl = $request->request->get('callback_url');
        /**
         * @var \Lychee\Module\Recommendation\Entity\RecommendationItem|null $item
         */
       $this->live()->deleteLiveRecommendationById($id);
        
        $this->clearCache();

        return $this->redirect($callbackUrl);
    }

    /**
     * @Route("/live_recommendation_inke")
     * @Template
     * @return array
     */
    public function liveRecommendationInkeAction() {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $page = $request->query->get('page', 1);
        $count = $request->query->get('count', 20);
        $uid = $request->query->get('uid');
        $total = 0;
        if ($uid) {
            $result[] = $this->live()->findOneInkeById($uid);
        }
        else {
            $result = $this->live()->fetchInkeHistoryByPage($page,$count,$total);
        }

        $pageCount = ceil($total / $count);
        return $this->response('映客直播', [
            'items' => $result,
            'pageCount' => $pageCount,
            'page' => $page,
        ]);
    }

    /**
     * @Route("/live/create_inke")
     * @Method("POST")
     * @param Request $request
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function createLiveRecommendationInke(Request $request) {
        $inkeUid = $request->request->get('inke_uid');
        $state = $this->live()->createInkeLiveRecommendation($inkeUid);
        if (!$state) {
            return $this->redirectErrorPage('该直播ID已经存在，请勿重复添加', $request);
        }
        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_liverecommendationinke'));
    }

    /**
     * @Route("/live/delete_inke")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function deleteLiveRecommendationInkeAction(Request $request) {
        $id = $request->request->get('id');
        $callbackUrl = $request->request->get('callback_url');
        /**
         * @var \Lychee\Module\Recommendation\Entity\RecommendationItem|null $item
         */
        $this->live()->deleteInkeLiveRecommendationById($id);

        $this->clearCache();

        return $this->redirect($callbackUrl);
    }

    /**
     * @Route("/live/stick_inke")
     * @param Request $request
     * @Method("POST")
     */
    public function stickLiveRecommendationInkeAction(Request $request) {
        $id = $request->request->get('id');
        $top = $request->request->get('top');
        $this->live()->stickInkeLiveRecommendationById($id, $top);
        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_liverecommendationinke'));
    }

    /**
     * @param Request $request
     * @Route("/jingxuan_live")
     * @Template
     */
    public function jingxuanRecommendationInkeAction(Request $request) {
        $page = $request->query->get('page', 1);
        $uid = $request->query->get('uid');
        $count = 20;
        $total = 0;
        if ($uid) {
            $item = $this->live()->findOneJingxuanById($uid);
            if ($item) {
                $results[] = $item;
            }
            else {
                $results = [];
            }
        }
        else {
            $results = $this->live()->getJingxuanLive($count, $page, $total);
        }

        return $this->response('精选直播', array(
            'items' => $results,
            'page' => $page,
            'pageCount' => ceil($total/$count)
        ));
    }

    /**
     * @param Request $request
     * @Route("/jingxuan_live/create")
     */
    public function createJingxuanLive(Request $request) {
        $inkeUid = $request->request->get('inke_uid');
        $nikename = $request->request->get('nikename');
        $description = $request->request->get('description');
        $startTime = $request->request->get('start_time');
        $endTime = $request->request->get('end_time');
        $avatar = '';
        $cover = '';
        /** @var QiniuStorage $storageService */
        $storageService = $this->storage();
        $storageService->setPrefix('jingxuanLive/');
        if ($request->files->has('avatar')) {
            $avatarFile = $request->files->get('avatar');
            if (file_exists($avatarFile)) {
                $avatar = $storageService->put($avatarFile);
            }
        }
        if ($request->files->has('cover')) {
            $coverFile = $request->files->get('cover');
            if (file_exists($coverFile)) {
                $cover = $storageService->put($coverFile);
            }
        }
        $status = $this->live()->addJingxuanLive($inkeUid, $nikename, $avatar, $cover, $description, new \DateTime($startTime), new \DateTime($endTime));
        if (!$status) {
            $this->redirectErrorPage('该精选直播已经存在，请勿重复添加', $request);
        }
        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_jingxuanrecommendationinke'));
    }

    /**
     * @param $id
     * @Route("/jingxuan_live/modify/{id}", requirements={"id" = "\d+"})
     * @Template
     */
    public function modifyJingxuanLive($id) {
        $item = $this->live()->findOneJingxuanById($id);

        return $this->response('修改精选直播', array(
            'item' => $item
        ));
    }

    /**
     * @param Request $request
     * @Route("/jingxuan_live/delete")
     */
    public function deleteJingxuanLive(Request $request) {
        $uid = $request->request->get('id');
        $callbackUrl = $request->request->get('callback_url');
        $this->live()->deleteJingxuanLive($uid);
        $this->clearCache();
        return $this->redirect($callbackUrl);
    }

    /**
     * @Route("/add_jingxuan_live")
     * @Template
     */
    public function addJingxuanLive() {
        return $this->response('添加精选直播', array());
    }

    /**
     * @param Request $request
     * @route ("/modify_Jingxuan")
     */
    public function modifyOneJingxuanlive(Request $request) {
        $id = $request->request->get('inke_uid');
        $nikename = $request->request->get('nikename');
        $description = $request->request->get('description');
        $start = $request->request->get('start_time');
        $end = $request->request->get('end_time');
        /** @var QiniuStorage $storageService */
        $storageService = $this->storage();
        $storageService->setPrefix('jingxuanLive/');
        if ($request->files->has('avatar')) {
            $avatarFile = $request->files->get('avatar');
            if (file_exists($avatarFile)) {
                $avatar = $storageService->put($avatarFile);
            }
            else {
                $avatar = '';
            }
        }
        else {
            $avatar = '';
        }
        if ($request->files->has('cover')) {
            $coverFile = $request->files->get('cover');
            if (file_exists($coverFile)) {
                $cover = $storageService->put($coverFile);
            }
            else {
                $cover = '';
            }
        }
        else {
            $cover = '';
        }
        $this->live()->modifyJingxuanLive($id, $nikename, $avatar, $cover, $description, new \DateTime($start), new \DateTime($end));
        return $this->redirect($this->generateUrl('lychee_admin_manualintervention_jingxuanrecommendationinke'));
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

	/**
	 * @Route("/official")
	 * @Template
	 * @param Request $request
	 * @return array
	 */
	public function officialAction(Request $request) {
		$cursor = $request->query->get('cursor', 0);
		$data = $this->officialNotification()->fetchAllOfficials($cursor, 50, $nextCursor);
		for ($i = 0, $len = count($data); $i < $len; $i++) {
			$data[$i]->image = ImageUtility::resize($data[$i]->image, 150, 999);
			switch ($data[$i]->type) {
				case 1:
					$data[$i]->type = '帖子';
					break;
				case 2:
					$data[$i]->type = $this->getParameter('topic_name');
					break;
				case 3:
					$data[$i]->type = '用户';
					break;
				case 4:
					$data[$i]->type = '评论';
					break;
				case 5:
					$data[$i]->type = '网页';
					$schema = $data[$i]->url;
					if (preg_match('/url=(.+)$/', $schema, $matches)) {
						$data[$i]->url = urldecode($matches[1]);
					}
					break;
				case 6:
					$data[$i]->type = '专题';
					break;
				case 7:
					$data[$i]->type = '直播';
					break;
			}
		}
		$officialNotificationTypes = $this->getNotificationTypes();
		$operationAccountIds = $this->get('lychee_admin.service.operation_accounts')->fetchIds();
		$operationAccountIds = array_reduce($operationAccountIds, function($result, $item) {
			$result[] = $item['id'];
			return $result;
		});
		$operationAccounts = $this->account()->fetch($operationAccountIds);
		$officialNotificationIds = array_map(function ($item) {
			return $item->id;
		}, $data);
		$pushData = $this->officialNotification()->fetchOfficialNotificationPush($officialNotificationIds);

		return $this->response('官方通知', [
			'previousCursor' => $request->query->get('previousCursor'),
			'nextCursor' => $nextCursor,
			'officialNotificationTypes' => $officialNotificationTypes,
			'operationAccounts' => $operationAccounts,
			'data' => $data,
			'pushData' => $pushData,
		]);
	}

	/**
	 * @return array
	 */
	private function getNotificationTypes() {
		$reflection = new \ReflectionClass(OfficialNotification::class);

		return $reflection->getConstants();
	}

	/**
	 * @Route("/official/create")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function createOfficialNotificationAction(Request $request) {
		$sender = $request->request->get('sender');
		$message = $request->request->get('message');
		$type = $request->request->get('type');
		$targetId = trim($request->request->get('target_id'));
		$url = $request->request->get('url');
		$image = $request->files->get('image');
		$isPush = $request->request->get('push');
		$pushTime = new \DateTime($request->request->get('push_time'));
		if (!file_exists($image)) {
			$image = $this->getOfficialNotificationImage($type, $targetId);
		} else {
			$image = $this->storage()->put($image);
		}
		$now = new \DateTime();
		if ($isPush) {
			$publishTime = $pushTime;
		} else {
			$publishTime = $now;
		}
		switch($type) {
			case OfficialNotification::TYPE_POST:
				$function = 'addOfficialPost';
				break;
			case OfficialNotification::TYPE_USER:
				$function = 'addOfficialUser';
				break;
			case OfficialNotification::TYPE_TOPIC:
				$function = 'addOfficialTopic';
				break;
			case OfficialNotification::TYPE_SUBJECT:
				$function = 'addOfficialSubject';
				break;
			case OfficialNotification::TYPE_SITE:
				$notification = $this->officialNotification()->addOfficialSite(
					$sender,
					$now,
					$message,
					$image,
					$url,
					$publishTime
				);
				break;
			case OfficialNotification::TYPE_LIVE:
				$function = 'addOfficialLive';
				break;
			default:
				throw new \UnexpectedValueException('unknown official notification type');
		}
		if (!isset($notification)) {
			$notification = $this->officialNotification()->$function(
				$sender,
				$now,
				$message,
				$image,
				$targetId,
				$publishTime
			);
		}
		if ($isPush) {
			$pushPlatform = $request->request->get('push_platform');
			$platform = 0;
			if ($pushPlatform) {
				if (count($pushPlatform) === 1 && $pushPlatform[0] == 1) {
					$platform = 1;
				} elseif (count($pushPlatform) === 1 && $pushPlatform[0] == 2) {
					$platform = 2;
				}
			}
			$groups = [];
			for ($i = 0; $i < 12; $i++) {
				$groups[] = '_g' . $i;
			}
			$this->officialNotification()->setOfficialNotificationPush(
				$notification->id,
				$request->request->get('push_message'),
				$pushTime,
				$platform,
				json_encode($groups)
			);
		}

		return $this->redirect($this->generateUrl('lychee_admin_manualintervention_official'));
	}

	/**
	 * @param $type
	 * @param $id
	 * @return mixed|null|string
	 */
	private function getOfficialNotificationImage($type, $id) {
		if ($type == OfficialNotification::TYPE_POST) {
			$post = $this->post()->fetchOne($id);
			if ($post) {
				if ($post->type === Post::TYPE_VIDEO) {
					$annotation = json_decode($post->annotation, true);
					if ($annotation) {
						return $annotation[PostAnnotation::RESOURCE_VIDEO_COVER];
					}
				}
				if ($post->imageUrl) {
					return $post->imageUrl;
				} else {
					$annotation = json_decode($post->annotation, true);
					if ($annotation) {
						if (isset($annotation[PostAnnotation::MULTI_PHOTOS])) {
							$photos = $annotation[PostAnnotation::MULTI_PHOTOS];
							if (!empty($photos)) {
								return $photos[0];
							}
						}
					}
				}
				return null;
			}
		} elseif ($type == OfficialNotification::TYPE_TOPIC) {
			$topic = $this->topic()->fetchOne($id);
			if ($topic) {
				return $topic->indexImageUrl;
			}
		} elseif ($type == OfficialNotification::TYPE_USER) {
			$user = $this->account()->fetchOne($id);
			if ($user) {
				return $user->avatarUrl;
			}
		} elseif ($type == OfficialNotification::TYPE_SUBJECT) {
			$subject = $this->specialSubject()->fetchOne($id);
			if ($subject) {
				return $subject->getBanner();
			}
		}

		return null;
	}

	/**
	 * @Route("/official/remove")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function removeOfficialAction(Request $request) {
		$id = $request->request->get('id');
		$notifications = $this->officialNotification()->fetchOfficialsByIds([$id]);
		$em = $this->getDoctrine()->getManager();
		foreach ($notifications as $notification) {
			$em->remove($notification);
		}
		$pushInfos = $this->officialNotification()->fetchOfficialNotificationPush([$id]);
		foreach ($pushInfos as $info) {
			$em->remove($info);
		}
		$em->flush();

		return $this->redirect($this->generateUrl('lychee_admin_manualintervention_official'));
	}
}
