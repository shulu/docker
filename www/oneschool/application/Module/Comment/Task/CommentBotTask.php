<?php
namespace Lychee\Module\Comment\Task;


use Lychee\Component\Task\Task;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class CommentBotTask implements Task {

    use ContainerAwareTrait;

    public function getName() {
        return 'comment-bot';
    }

    public function getDefaultInterval() {
        return 60 * 5;
    }

    public function run() {
        $bot = $this->container->get('lychee.module.robot.comment');
        $bot->processWaitingTasks();
    }

}