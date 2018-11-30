<?php
namespace Lychee\Module\Operation\Tasks;


use Lychee\Component\Task\Task;
use Lychee\Module\Operation\LikingBot\LikingBot;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LikingBotTask implements Task {

    use ContainerAwareTrait;

    public function getName() {
        return 'liking-bot';
    }

    public function getDefaultInterval() {
        return 60 * 5;
    }

    public function run() {
        $bot = $this->container->get('lychee.module.robot.like_post');
        $bot->processWaitingTasks();
    }

}