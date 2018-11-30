<?php
namespace Lychee\Module\IM;

use Lychee\Module\Topic\Following\TopicFollowingEvent;
use Lychee\Module\Topic\TopicEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Lychee\Module\IM\GroupService;
use Lychee\Module\Topic\TopicDefaultGroupService;
use Lychee\Module\Topic\TopicService;

class EventSubscriber implements EventSubscriberInterface {

    private $groupService;
    private $topicDefaultGroupService;
    private $topicService;

    /**
     * EventSubscriber constructor.
     * @param GroupService $groupService
     * @param TopicService $topicService
     * @param TopicDefaultGroupService $topicDefaultGroupService
     */
    public function __construct($groupService, $topicService, $topicDefaultGroupService) {
        $this->groupService = $groupService;
        $this->topicService = $topicService;
        $this->topicDefaultGroupService = $topicDefaultGroupService;
    }

    public static function getSubscribedEvents() {
        return array(
            TopicFollowingEvent::FOLLOW => 'onFollowTopic',
            TopicFollowingEvent::UNFOLLOW => 'onUnfollowTopic',
            TopicEvent::CREATE => 'onTopicCreate',
        );
    }

    public function onFollowTopic(TopicFollowingEvent $event) {

        try {
            $defaultGroupId = $this->topicDefaultGroupService->getDefaultGroup($event->getTopicId());
            if ($defaultGroupId > 0) {
                $this->groupService->join($defaultGroupId, $event->getUserId());
            }
        } catch (\Exception $e) {
            //发生错误就当作不加入默认群聊了
        }
    }

    public function onUnfollowTopic(TopicFollowingEvent $event) {
        $this->groupService->leaveGroupsOfTopic($event->getUserId(), $event->getTopicId());
    }

    public function onTopicCreate(TopicEvent $event) {
        $topic = $this->topicService->fetchOne($event->getTopicId());
        if (!$topic) {
            return;
        }

        try {
            $group = $this->groupService->create($topic->creatorId, mb_substr($topic->title, 0, 20, 'utf8'),
                null, '本次元默认群聊', $topic->id);
            $this->topicDefaultGroupService->updateDefaultGroup($topic->id, $group->id);
        } catch (\Exception $e) {
            //发生错误就当作不创建默认群聊了
        }
    }

}