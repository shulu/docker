<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Bundle\ApiBundle\Controller\Controller;
use Lychee\Module\Notification\Entity\GroupEventNotification;
use Lychee\Module\Notification\Entity\TopicLikeNotification;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\GraphStorage\FollowingResolver;
use Lychee\Component\GraphStorage\FollowingCounter;
use Lychee\Module\Notification\EventNotificationAction;
use Lychee\Module\Notification\LikeNotificationType;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Bundle\CoreBundle\Entity\Comment;
use Lychee\Module\Activity\Entity\Activity;
use Lychee\Module\Recommendation\Entity\SpecialSubject;
use Lychee\Module\Recommendation\Entity\SpecialSubjectRelation;
use Lychee\Module\Topic\TopicCategoryService;
use Lychee\Module\Notification\Entity\OfficialNotification;
use Lychee\Module\Topic\TopicTagService;
use Symfony\Component\HttpFoundation\Request;
use Lychee\Module\Topic\CoreMember\TopicCoreMemberService;
use Lychee\Module\Topic\CoreMember\CoreMemberTitleResolver;

class SynthesizerBuilder {
    use ContainerAwareTrait, ModuleAwareTrait;

    private function extractIdsAndEntities(
        $entitiesOrIds, $fetchFunction, $idColumnName = 'id'
    ) {
        if (count($entitiesOrIds) == 0) {
            return array(array(), array());
        }

        if (is_object(current($entitiesOrIds))) {
            if (isset($entitiesOrIds[0])) {
                //it is a index base array not a id associate array
                //need to transfomr to id associate array
                $entities = ArrayUtility::mapByColumn($entitiesOrIds, $idColumnName);
                $ids = array_keys($entities);
            } else {
                $ids = array_keys($entitiesOrIds);
                $entities = $entitiesOrIds;
            }
        } else {
            $ids = $entitiesOrIds;
            $entitiesUnordered = $fetchFunction($ids);
            $entities = array();
            foreach ($ids as $id) {
                if (isset($entitiesUnordered[$id])) {
                    $entities[$id] = $entitiesUnordered[$id];
                }
            }
        }

        return array($ids, $entities);
    }

    /**
     * @param array $usersOrIds
     * @param int $accountId
     * @param int $relationResolverHint
     *
     * @return Synthesizer
     */
    public function buildUserSynthesizer(
        $usersOrIds, $accountId, $relationResolverHint = FollowingResolver::HINT_NONE
    ) {
        list($userIds, $users) = $this->extractIdsAndEntities($usersOrIds, function($ids){
            return $this->account()->fetch($ids);
        });

        $isPersonal = false;
        // 判断是否本人
        if ($accountId
            && reset($userIds)==$accountId) {
            $isPersonal = true;
        }

        $relationResolver = $this->relation()->buildRelationResolver(
            $accountId, $userIds, $relationResolverHint
        );

        $relationCounter = $this->relation()->buildRelationCounter($userIds);
        $topicFollowingCounter = $this->topicFollowing()->getUsersFollowingCounter($userIds);

        $blackListResolver = $this->relation()->userBlackListBuildResolver($accountId, $userIds);

        $countingSynthesizer = new UserCountingSynthesizer(
            $this->account()->fetchCountings($userIds)
        );
        $vipSynthesizer = new UserVipSynthesizer($this->account()->fetchVipInfosByUserIds($userIds));
        return new UserSynthesizer(
            $users, $relationResolver, $relationCounter,
            $topicFollowingCounter, $countingSynthesizer, null,
            $blackListResolver, null, $vipSynthesizer,
            null,null, $isPersonal
        );
    }

    /**
     * @param array $usersOrIds
     * @param CoreMemberTitleResolver $coreMemberTitleResolver
     * @param FollowingResolver $followingResolver
     *
     * @return Synthesizer
     */
    public function buildSimpleUserSynthesizer($usersOrIds, $coreMemberTitleResolver = null, $followingResolver = null) {
        list($userIds, $users) = $this->extractIdsAndEntities($usersOrIds, function($ids){
            return $this->account()->fetch($ids);
        });
        $vipSynthesizer = new UserVipSynthesizer($this->account()->fetchVipInfosByUserIds($userIds));
        $sensitiveWordCheckerService = $this->sensitiveWordChecker();
        return new UserSynthesizer($users, $followingResolver,
            null, null, null, null, null,
            $coreMemberTitleResolver, $vipSynthesizer, $sensitiveWordCheckerService);
    }

    public  function sensitiveWordChecker() {
        return $this->container()->get('lychee_core.sensitive_word_checker');
    }

    /**
     * @param array $usersOrIds
     *
     * @return Synthesizer
     */
    public function buildProfiledUserSynthesizer(
        $usersOrIds, $accountId, $relationResolverHint = FollowingResolver::HINT_NONE
    ) {
        list($userIds, $users) = $this->extractIdsAndEntities($usersOrIds, function($ids){
            return $this->account()->fetch($ids);
        });

        $favoriteService = null;
        $isPersonal = false;
        // 判断是否本人
        if ($accountId
            && reset($userIds)==$accountId) {
            $isPersonal = true;
            $favoriteService = $this->container()->get('lychee.module.favorite');
        }

        $relationResolver = $this->relation()->buildRelationResolver(
            $accountId, $userIds, $relationResolverHint
        );
        $relationCounter = $this->relation()->buildRelationCounter($userIds);
        $topicFollowingCounter = $this->topicFollowing()->getUsersFollowingCounter($userIds);

        $countingSynthesizer = new UserCountingSynthesizer(
            $this->account()->fetchCountings($userIds)
        );

        $profiles = $this->account()->fetchUserProfiles($userIds);
        $profileSynthesizer = new UserProfileSynthesizer($profiles);

        $blackListResolver = $this->relation()->userBlackListBuildResolver($accountId, $userIds);
        $vipSynthesizer = new UserVipSynthesizer($this->account()->fetchVipInfosByUserIds($userIds));
        $sensitiveWordCheckerService = $this->sensitiveWordChecker();

        return new UserSynthesizer(
            $users, $relationResolver, $relationCounter,
            $topicFollowingCounter, $countingSynthesizer, $profileSynthesizer,
            $blackListResolver, null, $vipSynthesizer, $sensitiveWordCheckerService,
            $favoriteService, $isPersonal
        );
    }

    /**
     * @param array $topicsOrIds
     * @param int $accountId
     *
     * @return Synthesizer
     */
    public function buildTopicSynthesizer($topicsOrIds, $accountId) {
        list($topicIds, $topics) = $this->extractIdsAndEntities($topicsOrIds, function($ids){
            return $this->topic()->fetch($ids);
        });

        $followerIdsByTopicIds = array();
        $followerIds = array();
        foreach ($topicIds as $topicId) {
            $followerItor = $this->topicFollowing()->getTopicFollowerIterator($topicId);
            $topicNewFollowerIds = $followerItor->setStep(10)->current();
            $followerIdsByTopicIds[$topicId] = $topicNewFollowerIds;
            $followerIds = array_merge($followerIds, $topicNewFollowerIds);
        }
        $managerIds = ArrayUtility::filterValuesNonNull(ArrayUtility::columns($topics, 'managerId'));

        $userIds = array_unique(array_merge($followerIds, $managerIds));
        $userSynthesizer = $this->buildSimpleUserSynthesizer($userIds);
        $followerSynthesizer = new TopicFollowerSynthesizer(
            $followerIdsByTopicIds, $userSynthesizer
        );

        $followingResolver = $this->topicFollowing()->getUserFollowingResolver($accountId, $topicIds);

        /** @var TopicCategoryService $categoryService */
        $categoryService = $this->container->get('lychee.module.topic.category');
        $categoriesByTopicIds = $categoryService->categoriesByTopicIds($topicIds);
        $categorySynthesizer = new TopicCategorySynthesizer($categoriesByTopicIds);

        return new TopicSynthesizer(
            $topics, $followingResolver, $followerSynthesizer,
            $userSynthesizer, $categorySynthesizer
        );
    }

    /**
     * @return bool
     */
    private function votingShowFourOptionsAtMost() {
        /** @var Request $request */
        $request = $this->container()->get('request');
        $clientVersion = $request->get(Controller::CLIENT_APP_VERSION_KEY);
        return $clientVersion && version_compare($clientVersion, '2.0', '<');
    }

    /**
     * @param array $topicsOrIds
     * @param int $accountId
     *
     * @return Synthesizer
     */
    public function buildSimpleTopicSynthesizer($topicsOrIds, $accountId) {
        list($topicIds, $topics) = $this->extractIdsAndEntities($topicsOrIds, function($ids){
            return $this->topic()->fetch($ids);
        });
        $followingResolver = $this->topicFollowing()->getUserFollowingResolver($accountId, $topicIds);

        $managerIds = ArrayUtility::filterValuesNonNull(ArrayUtility::columns($topics, 'managerId'));
        $userIds = array_unique($managerIds);
        $userSynthesizer = $this->buildSimpleUserSynthesizer($userIds);

        /** @var TopicCategoryService $categoryService */
        $categoryService = $this->container->get('lychee.module.topic.category');
        $categoriesByTopicIds = $categoryService->categoriesByTopicIds($topicIds);
        $categorySynthesizer = new TopicCategorySynthesizer($categoriesByTopicIds);

        return new TopicSynthesizer($topics, $followingResolver, null,
            $userSynthesizer, $categorySynthesizer);
    }

    /**
     * @param array $topicsOrIds
     * @param int $accountId
     *
     * @return Synthesizer
     */
    public function buildRoughTopicSynthesizer($topicsOrIds, $accountId) {
        list($topicIds, $topics) = $this->extractIdsAndEntities($topicsOrIds, function($ids){
            return $this->topic()->fetch($ids);
        });
        $followingResolver = $this->topicFollowing()->getUserFollowingResolver($accountId, $topicIds);
        return new TopicSynthesizer($topics, $followingResolver, null, null, null);
    }

    /**
     * @param array $topicsOrIds
     * @param int $accountId
     *
     * @return Synthesizer
     */
    public function buildBasicTopicSynthesizer($topicsOrIds, $accountId) {
        list(, $topics) = $this->extractIdsAndEntities($topicsOrIds, function($ids){
            return $this->topic()->fetch($ids);
        });
        return new TopicSynthesizer($topics, null, null, null, null);
    }

    /**
     * @param int $accountId
     * @param array $postsOrIds
     *
     * @return PostSynthesizer
     */
    public function buildPostSynthesizer($postsOrIds, $accountId) {
        if (count($postsOrIds) === 0) {
            return new PostSynthesizer(array(), null, null, null, null, null, null, null, null, null, null);
        }
        list($posts, $userIds, $topicIds, $likerIdsByPostIds, $allPostIds,
            $groupIds, $scheduleIds, $votingIds) =
            $this->fetchPostInfos($postsOrIds, true);

        $userSynthesizer = $this->buildPostUserSynthesizer($posts, $userIds, $accountId);
        $topicSynthesizer = $this->buildRoughTopicSynthesizer($topicIds, $accountId);
        $countingSynthesizer = new PostCountingSynthesizer($this->post()->fetchCountings($allPostIds));
        $likeResolver = $this->like()->buildPostLikeResolver($accountId, $allPostIds);
        $favoriteResolver = $this->favorite()->userBuildPostFavoriteResolver($accountId, $allPostIds);

        if ($likerIdsByPostIds) {
            $likerSynthesizer = new LikerSynthesizer($likerIdsByPostIds, $userSynthesizer);
        }

        if (count($groupIds) > 0) {
            $imGroupSynthesizerBuilder = new IMGroupSynthesizerBuilder();
            $imGroupSynthesizerBuilder->setContainer($this->container());
            $imGroupSynthesizer = $imGroupSynthesizerBuilder->build($groupIds);
        } else {
            $imGroupSynthesizer = null;
        }
        if (count($scheduleIds) > 0) {
            $scheduleSynthesizerBuilder = new ScheduleSynthesizerBuilder();
            $scheduleSynthesizerBuilder->setContainer($this->container());
            $scheduleSynthesizer = $scheduleSynthesizerBuilder->build($scheduleIds, $accountId);
        } else {
            $scheduleSynthesizer = null;
        }
        if (count($votingIds) > 0) {
            $votingSynthesizerBuilder = new VotingSynthesizerBuilder();
            $votingSynthesizerBuilder->setContainer($this->container());
            $votingSynthesizer = $votingSynthesizerBuilder->build($votingIds, $accountId,
                array('four_options_at_most' => $this->votingShowFourOptionsAtMost()));
        } else {
            $votingSynthesizer = null;
        }
        $contentResolver = $this->post()->buildContentResolver($posts);

        return new PostSynthesizer(
            $posts, $userSynthesizer, $topicSynthesizer,
            $countingSynthesizer, $likeResolver, isset($likerSynthesizer) ? $likerSynthesizer : null,
            $favoriteResolver, null, $imGroupSynthesizer, $scheduleSynthesizer,
            $votingSynthesizer, $contentResolver
        );
    }

    /**
     * @param Post[] $posts
     * @param int[] $userIds
     * @return UserSynthesizer
     */
    private function buildPostUserSynthesizer($posts, $userIds, $accountId) {
        $param = array();
        foreach ($posts as $post) {
            if ($post->topicId && $post->authorId) {
                $param[] = array($post->topicId, $post->authorId);
            }
        }

        /** @var TopicCoreMemberService $coreMemberService */
        $coreMemberService = $this->container()->get('lychee.module.topic.core_member');
        $resolver = $coreMemberService->getTitleResolver($param);

        if ($accountId > 0) {
            $relationResolver = $this->relation()->buildRelationResolver(
                $accountId, $userIds
            );
        } else {
            $relationResolver = null;
        }


        return $this->buildSimpleUserSynthesizer($userIds, $resolver, $relationResolver);
    }

    /**
     * @param array $postsOrIds
     * @param int $accountId
     *
     * @return Synthesizer
     */
    public function buildListPostSynthesizer($postsOrIds, $accountId) {
        if (count($postsOrIds) === 0) {
            return new PostSynthesizer(array(), null, null, null, null, null, null, null, null, null, null);
        }
        list($posts, $userIds, $topicIds, , $allPostIds,
            $groupIds, $scheduleIds, $votingIds) = $this->fetchPostInfos($postsOrIds, false);

        $userSynthesizer = $this->buildPostUserSynthesizer($posts, $userIds, $accountId);
        $topicSynthesizer = $this->buildRoughTopicSynthesizer($topicIds, $accountId);
        $countingSynthesizer = new PostCountingSynthesizer(
            $this->post()->fetchCountings($allPostIds)
        );
        $likeResolver = $this->like()->buildPostLikeResolver($accountId, $allPostIds);
        $favoriteResolver = $this->favorite()->userBuildPostFavoriteResolver($accountId, $allPostIds);

        if (count($groupIds) > 0) {
            $imGroupSynthesizerBuilder = new IMGroupSynthesizerBuilder();
            $imGroupSynthesizerBuilder->setContainer($this->container());
            $imGroupSynthesizer = $imGroupSynthesizerBuilder->build($groupIds);
        } else {
            $imGroupSynthesizer = null;
        }

        if (count($scheduleIds) > 0) {
            $scheduleSynthesizerBuilder = new ScheduleSynthesizerBuilder();
            $scheduleSynthesizerBuilder->setContainer($this->container());
            $scheduleSynthesizer = $scheduleSynthesizerBuilder->build($scheduleIds, $accountId);
        } else {
            $scheduleSynthesizer = null;
        }
        if (count($votingIds) > 0) {
            $votingSynthesizerBuilder = new VotingSynthesizerBuilder();
            $votingSynthesizerBuilder->setContainer($this->container());
            $votingSynthesizer = $votingSynthesizerBuilder->build($votingIds, $accountId,
                array('four_options_at_most' => $this->votingShowFourOptionsAtMost()));
        } else {
            $votingSynthesizer = null;
        }
        $contentResolver = $this->post()->buildContentResolver($posts);
        return new PostSynthesizer(
            $posts, $userSynthesizer, $topicSynthesizer,
            $countingSynthesizer, $likeResolver, null, $favoriteResolver,
            null, $imGroupSynthesizer, $scheduleSynthesizer,
            $votingSynthesizer, $contentResolver
        );
    }


    /**
     * @param array $postsOrIds
     * @param int $accountId
     *
     * @return Synthesizer
     */
    public function buildBasicPostSynthesizer($postsOrIds, $accountId) {
        list($posts, $userIds, $topicIds, , $allPostIds, ) =
            $this->fetchPostInfos($postsOrIds, false);


        $userSynthesizer = $this->buildPostUserSynthesizer($posts, $userIds, $accountId);
        $topicSynthesizer = $this->buildBasicTopicSynthesizer($topicIds, $accountId);
        $countingSynthesizer = new PostCountingSynthesizer(
            $this->post()->fetchCountings($allPostIds)
        );


        $contentResolver = $this->post()->buildContentResolver($posts);

        return new PostSynthesizer(
            $posts, $userSynthesizer, $topicSynthesizer,
            $countingSynthesizer, null, null,
            null, null, null,
            null, null, $contentResolver
        );
    }

    private function fetchPostInfos($postsOrIds, $includeLiker = true) {
        list($postIds, $posts) = $this->extractIdsAndEntities($postsOrIds, function($ids){
            return $this->post()->fetch($ids);
        });
        list($userIds, $topicIds, $groupIds, $scheduleIds, $votingIds) =
            $this->extractPostColumns($posts, 'authorId', 'topicId', 'imGroupId', 'scheduleId', 'votingId');

        $topicIds = array_unique($topicIds);

        if ($includeLiker) {
            $likerIdsByPostIds = $this->like()->fetchPostsLatestLikerIds($postIds, 10);
            if (count($likerIdsByPostIds) > 0) {
                $likerIds = call_user_func_array('array_merge', array_values($likerIdsByPostIds));
                $userIds = array_unique(array_merge($userIds, $likerIds));
            }
        } else {
            $userIds = array_unique($userIds);
        }

        return array(
            $posts,
            $userIds, $topicIds,
            isset($likerIdsByPostIds) ? $likerIdsByPostIds : null,
            $postIds,
            $groupIds,
            $scheduleIds,
            $votingIds
        );
    }

    private function extractPostColumns($posts) {
        $columnsCount = func_num_args() - 1;
        assert($columnsCount);

        $columns = func_get_args();
        array_shift($columns);

        $values = array_fill(0, $columnsCount, array());
        foreach ($posts as $post) {
            /** @var Post $post */
            for ($i = 0; $i < $columnsCount; ++$i) {
                $columnName = $columns[$i];
                if ($post->$columnName !== null) {
                    $values[$i][] = $post->$columnName;
                }
            }
        }

        return $values;
    }

    private function extractCommentColumns($comments, $column) {
        $commentsNoDeleted = array_filter($comments, function($comment){
            return $comment->deleted !== true;
        });
        $arguments = func_get_args();
        $arguments[0] = $commentsNoDeleted;
        return call_user_func_array(array($this, 'extractColumns'), $arguments);
    }

    private function extractColumns($entities) {
        $columnsCount = func_num_args() - 1;
        assert($columnsCount);

        $columns = func_get_args();
        array_shift($columns);

        $values = array_fill(0, $columnsCount, array());
        foreach ($entities as $entity) {
            for ($i = 0; $i < $columnsCount; ++$i) {
                $columnName = $columns[$i];
                if ($entity->$columnName !== null) {
                    $values[$i][] = $entity->$columnName;
                }
            }
        }

        return $values;
    }

    public function buildCommentSynthesizer($commentsOrIds, $accountId) {
        list($comments, $repliedComments, $userIds, $postIds) = $this->fetchCommentInfos($commentsOrIds);
        $userSynthesizer = $this->buildUserSynthesizer($userIds, $accountId);

        if (count($repliedComments) > 0) {
            $contentResolver = $this->comment()->buildContentResolver($repliedComments);
            $repliedSynthesizer = new CommentSynthesizer($repliedComments, $userSynthesizer,
                null, null, null, $contentResolver);
        } else {
            $repliedSynthesizer = null;
        }

        $contentResolver = $this->comment()->buildContentResolver($comments);
        return new CommentSynthesizer($comments, $userSynthesizer, $repliedSynthesizer,
            null, null, $contentResolver);
    }

    public function buildSimpleCommentSynthesizer($commentsOrIds, $accountId=0) {
        list($comments, $repliedComments, $userIds, $postIds) = $this->fetchCommentInfos($commentsOrIds);
        $userSynthesizer = $this->buildSimpleUserSynthesizer($userIds);

        if (count($repliedComments) > 0) {
            $contentResolver = $this->comment()->buildContentResolver($repliedComments);
            $repliedSynthesizer = new CommentSynthesizer($repliedComments, $userSynthesizer,
                null, null, null, $contentResolver);
        } else {
            $repliedSynthesizer = null;
        }

        $likeResolver = $this->like()->buildCommentLikeResolver($accountId, $commentsOrIds);
        $contentResolver = $this->comment()->buildContentResolver($comments);

        return new CommentSynthesizer($comments, $userSynthesizer, $repliedSynthesizer, null, $likeResolver, $contentResolver);
    }

    private function fetchCommentInfos($commentsOrIds) {
        list($commentIds, $comments) = $this->extractIdsAndEntities(
            $commentsOrIds, function($ids){
                return $this->comment()->fetch($ids);
            }
        );
        list($repliedIds, $postIds) = $this->extractCommentColumns(
            $comments, 'repliedId', 'postId'
        );
        list($userIds) = $this->extractColumns($comments, 'authorId');

        if (count($repliedIds) > 0) {
            $repliedComments = $this->comment()->fetch(array_unique($repliedIds));
            list($replyAuthorIds) = $this->extractColumns($repliedComments, 'authorId');
            list($replyPostIds) = $this->extractCommentColumns($repliedComments, 'postId');
            $userIds = array_merge($userIds, $replyAuthorIds);
            $postIds = array_merge($postIds, $replyPostIds);
        }
        $userIds = array_unique($userIds);
        $postIds = array_unique($postIds);

        return array($comments,
            isset($repliedComments) ? $repliedComments : array(),
            $userIds,
            $postIds
        );
    }

    public function buildEventNotificationSynthesizer($notificationsOrIds, $accountId) {
        list(, $notifications) = $this->extractIdsAndEntities(
            $notificationsOrIds, function(){
                throw new \LogicException('event notification synthesizer can not reach here.');
            }
        );
        $userIds = array();
        $commentIds = array();
        $postIds = array();
        $topicIds = array();
        $scheduleIds = array();
        foreach ($notifications as $notification) {
            /** @var GroupEventNotification $notification */
            if ($notification->action === EventNotificationAction::COMMENT) {
                $commentIds[] = $notification->targetId;
                if ($notification->topicId) {
                    $topicIds[] = $notification->topicId;
                }
            } else if ($notification->action === EventNotificationAction::MENTION_IN_POST) {
                $postIds[] = $notification->targetId;
                if ($notification->topicId) {
                    $topicIds[] = $notification->topicId;
                }
            } else if ($notification->action === EventNotificationAction::MENTION_IN_COMMENT) {
                $commentIds[] = $notification->targetId;
                if ($notification->topicId) {
                    $topicIds[] = $notification->topicId;
                }
            } else if ($notification->action === EventNotificationAction::TOPIC_APPLY_TO_FOLLOW
                || $notification->action === EventNotificationAction::TOPIC_APPLY_CONFIRMED
                || $notification->action === EventNotificationAction::TOPIC_APPLY_REJECTED
            ) {
                $topicIds[] = $notification->targetId;
                $userIds[] = $notification->actorId;
            } else if ($notification->action === EventNotificationAction::TOPIC_KICKOUT) {
                $topicIds[] = $notification->targetId;
            } else if ($notification->action === EventNotificationAction::SCHEDULE_CANCELLED) {
                $scheduleIds[] = $notification->targetId;
                $userIds[] = $notification->actorId;
                if ($notification->topicId) {
                    $topicIds[] = $notification->topicId;
                }
            } else if ($notification->action === EventNotificationAction::SCHEDULE_ABOUT_TO_START) {
                $scheduleIds[] = $notification->targetId;
                $userIds[] = $notification->actorId;
                if ($notification->topicId) {
                    $topicIds[] = $notification->topicId;
                }
            } else if ($notification->action === EventNotificationAction::TOPIC_ANNOUNCEMENT) {
                $postIds[] = $notification->targetId;
                if ($notification->topicId) {
                    $topicIds[] = $notification->topicId;
                }
            } else if ($notification->action === EventNotificationAction::BECOME_CORE_MEMBER) {
                $topicIds[] = $notification->targetId;
            } else if ($notification->action === EventNotificationAction::REMOVE_CORE_MEMBER) {
                $topicIds[] = $notification->targetId;
            } else if ($notification->action === EventNotificationAction::TOPIC_CREATE_CONFIRMED) {
                $topicIds[] = $notification->targetId;
                $userIds[] = $notification->actorId;
            } else if ($notification->action === EventNotificationAction::TOPIC_CREATE_REJECTED) {
                $userIds[] = $notification->actorId;
            } else if ($notification->action === EventNotificationAction::ILLEGAL_POST_DELETED) {
                $postIds[] = $notification->targetId;
                $userIds[] = $notification->actorId;
                $topicIds[] = $notification->topicId;
            } else if ($notification->action === EventNotificationAction::MY_ILLEGAL_POST_DELETED) {
                $postIds[] = $notification->targetId;
                $userIds[] = $notification->actorId;
                $topicIds[] = $notification->topicId;
            }
        }

        list($comments, $repliedComments, $commentAuthorIds, $commentPostIds) = $this->fetchCommentInfos($commentIds);
        $postIds = array_unique(array_merge($postIds, $commentPostIds));
        list($posts, $postAuthorIds, $postTopicIds, , ) = $this->fetchPostInfos($postIds, false);

        $userIds = array_unique(array_merge($userIds, $commentAuthorIds, $postAuthorIds));
        $userSynthesizer = $this->buildSimpleUserSynthesizer($userIds);

        $topicIds = array_unique(array_merge($topicIds, $postTopicIds));
        $topicSynthesizer = $this->buildSimpleTopicSynthesizer($topicIds, $accountId);
        $postSynthesizer = new PostSynthesizer(
            $posts, $userSynthesizer, $topicSynthesizer,
            null, null, null, null, null, null, null, null
        );

        if (count($repliedComments) > 0) {
            $contentResolver = $this->comment()->buildContentResolver($repliedComments);
            $repliedSynthesizer = new CommentSynthesizer($repliedComments, $userSynthesizer,
                null, $postSynthesizer, null, $contentResolver);
        } else {
            $repliedSynthesizer = null;
        }
        $contentResolver = $this->comment()->buildContentResolver($comments);
        $commentSynthesizer = new CommentSynthesizer($comments, $userSynthesizer,
            $repliedSynthesizer, $postSynthesizer, null, $contentResolver);

        if (count($scheduleIds)) {
            $scheduleSynthesizerBuilder = new ScheduleSynthesizerBuilder();
            $scheduleSynthesizerBuilder->setContainer($this->container());
            $scheduleSynthesizer = $scheduleSynthesizerBuilder->build($scheduleIds, 0);
        } else {
            $scheduleSynthesizer = null;
        }

        return new EventNotificationSynthesizer(
            $notifications, $userSynthesizer, $commentSynthesizer, $postSynthesizer,
            $topicSynthesizer, $scheduleSynthesizer
        );
    }

    public function buildLikeNotificationSynthesizer($notificationsOrIds, $accountId) {
        list(, $notifications) = $this->extractIdsAndEntities(
            $notificationsOrIds, function(){
                throw new \LogicException('like notification synthesizer can not reach here.');
            }
        );
        $likerIds = array();
        $commentIds = array();
        $postIds = array();
        foreach ($notifications as $notification) {
            /** @var TopicLikeNotification $notification */
            $likerIds[] = $notification->likerId;
            if ($notification->type === LikeNotificationType::POST) {
                $postIds[] = $notification->likeeId;
            } else if ($notification->type === LikeNotificationType::COMMENT) {
                $commentIds[] = $notification->likeeId;
            }
        }

        list($comments, $repliedComments, $commentAuthorIds, $commentPostIds) = $this->fetchCommentInfos($commentIds);
        $postIds = array_unique(array_merge($postIds, $commentPostIds));
        list($posts, $postUserIds, $topicIds, , ) =
            $this->fetchPostInfos($postIds, false);

        $userIds = array_unique(array_merge($likerIds, $commentAuthorIds, $postUserIds));
        $userSynthesizer = $this->buildSimpleUserSynthesizer($userIds);

        $topicSynthesizer = $this->buildSimpleTopicSynthesizer($topicIds, $accountId);
        $postSynthesizer = new PostSynthesizer(
            $posts, $userSynthesizer, $topicSynthesizer,
            null, null, null, null, null, null, null, null
        );

        if (count($repliedComments) > 0) {
            $repliedSynthesizer = new CommentSynthesizer($repliedComments, $userSynthesizer, null, $postSynthesizer);
        } else {
            $repliedSynthesizer = null;
        }
        $commentSynthesizer = new CommentSynthesizer($comments, $userSynthesizer, $repliedSynthesizer, $postSynthesizer);

        return new LikeNotificationSynthesizer(
            $notifications, $userSynthesizer, $postSynthesizer, $commentSynthesizer
        );
    }

    public function buildOfficialNotificationSynthesizer($notificationsOrIds, $accountId) {
        list(, $notifications) = $this->extractIdsAndEntities(
            $notificationsOrIds, function($ids){
                return $this->officialNotification()->fetchOfficialsByIds($ids);
            }
        );

        $userIds = array();
        foreach ($notifications as $notification) {
            /** @var OfficialNotification $notification */
            $userIds[] = $notification->fromId;
        }
        $userIds = array_unique($userIds);

        $userSynthesizer = $this->buildSimpleUserSynthesizer($userIds);
        return new OfficialNotificationSynthesizer($notifications, $userSynthesizer);
    }

    public function buildActivitySynthesizer($activitiesOrIds, $accountId) {
        list(, $activities) = $this->extractIdsAndEntities(
            $activitiesOrIds, function($ids){
                return $this->activity()->fetch($ids);
            }
        );

        list($userIds, $postIds, $commentIds, $topicIds) =
            $this->fetchActivityInfos($activities);

        list($comments, $repliedComments, $commentAuthorIds, $commentPostIds) = $this->fetchCommentInfos($commentIds);
        $postIds = array_unique(array_merge($postIds, $commentPostIds));
        list($posts, $postUserIds, $postTopicIds, , ) =
            $this->fetchPostInfos($postIds, false);

        $userIds = array_unique(array_merge($userIds, $commentAuthorIds, $postUserIds));
        $userSynthesizer = $this->buildSimpleUserSynthesizer($userIds);

        $topicIds = array_unique(array_merge($topicIds, $postTopicIds));
        $topicSynthesizer = $this->buildSimpleTopicSynthesizer($topicIds, $accountId);

        $postSynthesizer = new PostSynthesizer(
            $posts, $userSynthesizer, $topicSynthesizer,
            null, null, null, null, null, null, null, null
        );

        if (count($repliedComments) > 0) {
            $repliedSynthesizer = new CommentSynthesizer($repliedComments, $userSynthesizer, null, $postSynthesizer);
        } else {
            $repliedSynthesizer = null;
        }
        $commentSynthesizer = new CommentSynthesizer($comments, $userSynthesizer, $repliedSynthesizer, $postSynthesizer);

        return new ActivitySynthesizer(
            $activities, $userSynthesizer, $topicSynthesizer,
            $commentSynthesizer, $postSynthesizer
        );
    }

    private function fetchActivityInfos($activities) {
        $userIds = array();
        $commentIds = array();
        $postIds = array();
        $topicIds = array();
        foreach ($activities as $activity) {
            /** @var Activity $activity */
            $userIds[] = $activity->userId;
            switch ($activity->action) {
                case Activity::ACTION_POST:
                    $postIds[] = $activity->targetId;
                    break;
                case Activity::ACTION_FOLLOW_USER:
                    $userIds[] = $activity->targetId;
                    break;
                case Activity::ACTION_FOLLOW_TOPIC:
                    $topicIds[] = $activity->targetId;
                    break;
                case Activity::ACTION_LIKE_POST:
                    $postIds[] = $activity->targetId;
                    break;
                case Activity::ACTION_LIKE_COMMENT:
                    $commentIds[] = $activity->targetId;
                    break;
                case Activity::ACTION_COMMENT_IMAGE:
                    $commentIds[] = $activity->targetId;
                    break;
                case Activity::ACTION_TOPIC_CREATE:
                    $topicIds[] = $activity->targetId;
                    break;
            }
        }
        return array($userIds, $postIds, $commentIds, $topicIds);
    }

    /**
     * @param array $specialSubjectsOrIds
     * @param int $accountId
     *
     * @return SpecialSubjectSynthesizer
     */
    public function buildSpecialSubjectSynthesizer($specialSubjectsOrIds, $accountId) {
        list(, $subjects) = $this->extractIdsAndEntities(
            $specialSubjectsOrIds, function($ids){
                return $this->specialSubject()->fetch($ids);
            }
        );

        $userIdsBySubjectIds = array();
        $topicIdsBySubjectIds = array();
        $postIdsBySubjectIds = array();
        $userIds = array();
        $topicIds = array();
        $postIds = array();

        foreach ($subjects as $subject) {
            /** @var SpecialSubject $subject */
            list($subjectPostIds, $subjectUserIds, $subjectTopicIds) =
                $this->getSpecialSubjectRelateItems($subject);

            if (!empty($subjectUserIds)) {
                $userIds = array_merge($userIds, $subjectUserIds);
                $userIdsBySubjectIds[$subject->getId()] = $subjectUserIds;
            }
            if (!empty($subjectPostIds)) {
                $postIds = array_merge($postIds, $subjectPostIds);
                $postIdsBySubjectIds[$subject->getId()] = $subjectPostIds;
            }
            if (!empty($subjectTopicIds)) {
                $topicIds = array_merge($topicIds, $subjectTopicIds);
                $topicIdsBySubjectIds[$subject->getId()] = $subjectTopicIds;
            }
        }

        $postSynthesizer = $this->buildListPostSynthesizer($postIds, $accountId);
        $userSynthesizer = $this->buildProfiledUserSynthesizer(
            $userIds, $accountId, FollowingResolver::HINT_NONE
        );
        $topicSynthesizer = $this->buildSimpleTopicSynthesizer($topicIds, $accountId);


        $userListSynthesizer = new ListSynthesizer($userIdsBySubjectIds, $userSynthesizer);
        $topicListSynthesizer = new ListSynthesizer($topicIdsBySubjectIds, $topicSynthesizer);
        $postListSynthesizer = new ListSynthesizer($postIdsBySubjectIds, $postSynthesizer);

        return new SpecialSubjectSynthesizer(
            $subjects, $postListSynthesizer,
            $topicListSynthesizer, $userListSynthesizer
        );
    }

    /**
     * @param SpecialSubject $subject
     * @return array
     */
    private function getSpecialSubjectRelateItems($subject) {
        /** @var SpecialSubjectRelation[] $relations */
        $relations = $subject->getRelations();
        $userIds = array();
        $postIds = array();
        $topicIds = array();
        foreach ($relations as $relation) {
            switch($relation->getType()) {
                case SpecialSubjectRelation::TYPE_POST:
                    $postIds[] = $relation->getAssociatedId();
                    break;
                case SpecialSubjectRelation::TYPE_USER:
                    $userIds[] = $relation->getAssociatedId();
                    break;
                case SpecialSubjectRelation::TYPE_TOPIC:
                    $topicIds[] = $relation->getAssociatedId();
                    break;
                default:
                    throw new \LogicException('unknown type.');
            }
        }

        return array($postIds, $userIds, $topicIds);
    }

    /**
     * @param array $specialSubjectsOrIds
     * @param int $accountId
     *
     * @return SpecialSubjectSynthesizer
     */
    public function buildSimpleSpecialSubjectSynthesizer($specialSubjectsOrIds, $accountId) {
        list(, $subjects) = $this->extractIdsAndEntities(
            $specialSubjectsOrIds, function($ids){
                $subjects = $this->specialSubject()->fetch($ids);
                $result = array();
                foreach ($subjects as $subject) {
                    /** @var SpecialSubject $subject */
                    $result[$subject->getId()] = $subject;
                }
                return $result;
            }
        );
        return new SpecialSubjectSynthesizer(
            $subjects, null, null, null
        );
    }

    /**
     * @param array $postsOrIds
     * @param int $accountId
     *
     * @return Synthesizer
     */
    public function buildListShortVideoPostSynthesizer($postsOrIds, $accountId) {
        if (count($postsOrIds) === 0) {
            return new ShortVideoPostSynthesizer(array(), null, null, null, null, null, null, null, null, null, null);
        }
        list($posts, $userIds, $topicIds, , $allPostIds,
            $groupIds, $scheduleIds, $votingIds) = $this->fetchPostInfos($postsOrIds, false);

        $userSynthesizer = $this->buildPostUserSynthesizer($posts, $userIds, $accountId);
        $topicSynthesizer = $this->buildRoughTopicSynthesizer($topicIds, $accountId);
        $countingSynthesizer = new PostCountingSynthesizer(
            $this->post()->fetchCountings($allPostIds)
        );
        $likeResolver = $this->like()->buildPostLikeResolver($accountId, $allPostIds);
        $favoriteResolver = $this->favorite()->userBuildPostFavoriteResolver($accountId, $allPostIds);

        if (count($groupIds) > 0) {
            $imGroupSynthesizerBuilder = new IMGroupSynthesizerBuilder();
            $imGroupSynthesizerBuilder->setContainer($this->container());
            $imGroupSynthesizer = $imGroupSynthesizerBuilder->build($groupIds);
        } else {
            $imGroupSynthesizer = null;
        }

        if (count($scheduleIds) > 0) {
            $scheduleSynthesizerBuilder = new ScheduleSynthesizerBuilder();
            $scheduleSynthesizerBuilder->setContainer($this->container());
            $scheduleSynthesizer = $scheduleSynthesizerBuilder->build($scheduleIds, $accountId);
        } else {
            $scheduleSynthesizer = null;
        }
        if (count($votingIds) > 0) {
            $votingSynthesizerBuilder = new VotingSynthesizerBuilder();
            $votingSynthesizerBuilder->setContainer($this->container());
            $votingSynthesizer = $votingSynthesizerBuilder->build($votingIds, $accountId,
                array('four_options_at_most' => $this->votingShowFourOptionsAtMost()));
        } else {
            $votingSynthesizer = null;
        }

        return new ShortVideoPostSynthesizer(
            $posts, $userSynthesizer, $topicSynthesizer,
            $countingSynthesizer, $likeResolver, null, $favoriteResolver,
            null, $imGroupSynthesizer, $scheduleSynthesizer, $votingSynthesizer
        );
    }


    /**
     * @param array $postsOrIds
     * @param int $accountId
     *
     * @return Synthesizer
     */
    public function buildListLiveShortVideoPostSynthesizer($postsOrIds, $accountId) {
        $synthesizer = $this->buildListShortVideoPostSynthesizer($postsOrIds, $accountId);
        $synthesizer->declareOnlyLive();
        return $synthesizer;
    }

}