<?php
namespace Lychee\Module\Relation\Task;


use Lychee\Component\Task\Task;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class UserFollowingBotTask implements Task {

    use ContainerAwareTrait;

    public function getName() {
        return 'user-following-bot';
    }

    public function getDefaultInterval() {
        return 60 * 5;
    }

    public function run() {
        $bot = $this->container->get('lychee.module.robot.follow_user');
        $bot->processWaitingTasks();
    }

}