<?php
namespace Lychee\Module\UGSV\Task;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Task\Task;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;


class MovePlayStatStoreTask implements Task {
    use ContainerAwareTrait;
    use ModuleAwareTrait;


    /**
     * @return string
     */
    public function getName() {
        return 'move-video-play-stat-store';
    }

    /**
     * @return integer
     */
    public function getDefaultInterval() {
        return 12*3600;
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger() {
        return $this->container->get('monolog.logger.task');
    }

    public function move() {
        $startTime = strtotime('-30 days');
        $endTime = strtotime('-1 days');

        $startDate = date('Y-m-d', $startTime);
        $endDate = date('Y-m-d', $endTime);
        $this->getLogger()->info('开始迁移 ('.$startDate.' ~ '.$endDate.') 的日志...');
        $r = $this->post()->movePlayStatStore($startTime, $endTime);
        $this->getLogger()->info('处理了'.count($r).'个文件');
        foreach ($r as $date => $item) {
            $this->getLogger()->info($date.':'.$item);
        }
        $this->getLogger()->info('...处理完毕');
    }

    /**
     * @return void
     */
    public function run() {
        $this->move();
    }
} 