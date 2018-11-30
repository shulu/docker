<?php
namespace Lychee\Module\Post\Task;

use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Task\Task;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ClearPostExposureTask implements Task {
    use ContainerAwareTrait;
    use ModuleAwareTrait;

    public function getName() {
        return 'clear-post-exposure-record';
    }

    public function getDefaultInterval() {
        return 3600 * 24;
    }

    public function run() {
        $recorder = $this->container()->get('lychee.module.post.exposure_recorder');
        $time = new \DateTime();
        $time->setTime(0, 0, 0);
        $time->sub(new \DateInterval('P1D'));
        $recorder->clearRecordBefore($time);
    }
}