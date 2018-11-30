<?php
namespace Lychee\Module\Notification\Task;

use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Task\Task;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ClearOldNotificationTask implements Task {

    use ContainerAwareTrait;
    use ModuleAwareTrait;

    public function getName() {
        return 'clear-old-notification';
    }

    public function getDefaultInterval() {
        return 24 * 60 * 60;
    }

    public function run() {
        $now = new \DateTime();
        $now->setTime(0, 0, 0);
        $oldTime = $now->sub(new \DateInterval('P6M'));

        $topicNotificationService = $this->container()->get('lychee.module.notification');
        $topicNotificationService->clearNotificationsBefore($oldTime);
    }
}