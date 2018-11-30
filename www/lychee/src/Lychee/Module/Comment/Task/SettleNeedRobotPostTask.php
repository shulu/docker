<?php
namespace Lychee\Module\Comment\Task;


use Lychee\Component\Task\Task;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class SettleNeedRobotPostTask implements Task {

    use ContainerAwareTrait;

    public function getName() {
        return 'settle-need-robot-post';
    }

    public function getDefaultInterval() {
        return 60 * 5;
    }

    public function run() {
        $bot = $this->container->get('lychee.module.comment.robot');
        $endTime = strtotime('-1 days');
        $startDateTime = date('Y-m-d H:i:s', $endTime-$this->getDefaultInterval());
        $endDateTime = date('Y-m-d H:i:s', $endTime);
        $bot->dispatchCommentTaskWhenPostCoolDown($startDateTime, $endDateTime);
    }

}