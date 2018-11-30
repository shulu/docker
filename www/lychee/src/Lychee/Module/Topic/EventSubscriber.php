<?php
namespace Lychee\Module\Topic;

use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Module\Account\AccountEvent;
use Lychee\Module\Account\LevelUpEvent;
use Lychee\Module\Post\PostEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Lychee\Module\Topic\TopicService;
use Lychee\Module\Post\PostService;
use Lychee\Module\Account\AccountService;
use Lychee\Module\Topic\TopicTagService;

class EventSubscriber implements EventSubscriberInterface {

    public static function getSubscribedEvents() {
        return array(
            LevelUpEvent::NAME => 'onAccountLevelup',
        );
    }

    private $topicService;

    /**
     * @param TopicService $topicService
     */
    public function __construct($topicService) {
        $this->topicService = $topicService;
    }

    public function onAccountLevelup(LevelUpEvent $event) {
        $oldLevel = $event->getOldLevel();
        $newLevel = $event->getNewLevel();
        $quota = 0;
        if ($oldLevel < 10 && $newLevel >= 10) {
            $quota += 2;
        } else if ($oldLevel < 20 && $newLevel >= 20) {
            $quota += 2;
        } else if ($oldLevel < 30 && $newLevel >= 30) {
            $quota += 2;
        } else if ($oldLevel < 40 && $newLevel >= 40) {
            $quota += 2;
        } else if ($oldLevel < 50 && $newLevel >= 50) {
            $quota += 2;
        }

        if ($quota > 0) {
            $this->topicService->increaseUserCreatingQuota($event->getAccountId(), $quota);
        }
    }
} 