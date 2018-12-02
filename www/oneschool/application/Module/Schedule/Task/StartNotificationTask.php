<?php
namespace Lychee\Module\Schedule\Task;

use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Task\Task;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Psr\Log\LoggerInterface;
use Lychee\Module\Schedule\ScheduleService;
use Lychee\Module\Notification\NotificationService;
use Lychee\Module\Post\PostService;
use Lychee\Module\Schedule\Entity\Schedule;

class StartNotificationTask implements Task {
    use ContainerAwareTrait;
    use ModuleAwareTrait;

    /**
     * @return string
     */
    public function getName() {
        return 'schedule_notification';
    }

    /**
     * @return integer
     */
    public function getDefaultInterval() {
        return 60 * 5;
    }

    /**
     * @return ScheduleService
     */
    private function scheduleService() {
        return $this->container()->get('lychee.module.schedule');
    }

    /**
     * @return NotificationService
     */
    private function topicNotificationService() {
        return $this->container()->get('lychee.module.notification');
    }

    /**
     * @return PostService
     */
    private function postService() {
        return $this->container()->get('lychee.module.post');
    }

    /**
     * @return void
     */
    public function run() {
        $now = new \DateTime();
        $twoHourSinceNow = clone $now;
        $twoHourSinceNow->add(new \DateInterval('PT2H'));
        $this->notifyScheduleAtTime($twoHourSinceNow, '活动还有2小时就要开始了，小伙伴都在等着你哦~');

        $tenHourSinceNow = clone $now;
        $tenHourSinceNow->add(new \DateInterval('PT24H'));
        $this->notifyScheduleAtTime($tenHourSinceNow, null);
    }

    /**
     * @param \DateTime $dateTime
     */
    private function notifyScheduleAtTime($dateTime, $message = null) {
        $tenMinuteAfter = clone $dateTime;
        $tenMinuteAfter->add(new \DateInterval('PT10M'));

        $scheduleService = $this->scheduleService();
        $notificationService = $this->topicNotificationService();
        $postService = $this->postService();
        $schedules = $scheduleService->fetchByStartTimeBetween($dateTime, $tenMinuteAfter);

        $now = time();
        foreach ($schedules as $schedule) {
            /** @var Schedule $schedule */
            if ($schedule->lastNotifyTime >= ($now - 3600)) {
                continue;
            }

            $post = $postService->fetchOne($schedule->postId);
            if ($post == null) {
                continue;
            }
            $joinerItor = $scheduleService->getJoinerIdIterator($schedule->id);
            $joinerItor->setStep(100);
            if ($message == null) {
                $startTime = $schedule->startTime->format('Y-m-d H:i');
                $message = $schedule->name . '活动将于'.$startTime.'开始，请大家准时参加。';
            } else {
                $message = $schedule->name . $message;
            }
            foreach ($joinerItor as $joinerIds) {
                $notificationService->notifyScheduleAboutToStartEvent($joinerIds,
                    $post->topicId, $schedule->id, $schedule->creatorId, $message);
            }
            $scheduleService->updateNotifyTime($schedule->id, $now);
        }
    }
}