<?php
namespace Lychee\Module\Topic\Tasks;

use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Task\Task;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Lychee\Module\Topic\Following\ApplyService;

class ClearTimeoutApplicationTask implements Task {
    use ContainerAwareTrait;
    use ModuleAwareTrait;

    public function getName() {
        return 'clear-timeout-application';
    }

    public function getDefaultInterval() {
        return 3600 * 24;
    }

    public function run() {
        /** @var ApplyService $service */
        $service = $this->container->get('lychee.module.topic.following_apply');
        $service->clearTimeoutApplications();
    }
}