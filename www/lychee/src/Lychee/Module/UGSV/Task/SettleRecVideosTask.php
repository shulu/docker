<?php
namespace Lychee\Module\UGSV\Task;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Task\Task;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;


class SettleRecVideosTask implements Task {
    use ContainerAwareTrait;
    use ModuleAwareTrait;


    /**
     * @return string
     */
    public function getName() {
        return 'settle-rec-videos';
    }

    /**
     * @return integer
     */
    public function getDefaultInterval() {
        return 5*60;
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger() {
        return $this->container->get('monolog.logger.task');
    }

    /**
     * @return \Predis\Client|\Redis
     */
    private function redis() {
        return $this->container()->get('snc_redis.recommendation_video');
    }

    public function getListKey() {
        return 'rec_videos';
    }

    private function updatePostsQueue() {
        $this->getLogger()->info('开始更新...');
        $redis = $this->redis();
        $maxLen = 450;
        $maxIndex = $maxLen - 1;
        $pushPostIds = [];
        try {
            $pushPostIds = $redis->lrange('hot_videos', 0, $maxIndex);
            $pushPostIds = array_reverse($pushPostIds);
            if (empty($pushPostIds[$maxIndex])) {
                $maxLen = count($pushPostIds);
                $maxIndex = $maxLen - 1;
            }
        } catch (\Exception $e) {
            $this->getLogger()->info($e->__toString());
        }
        shuffle($pushPostIds);
        $listkey = $this->getListKey();

        $this->getLogger()->info('记录数:'.count($pushPostIds));
        try {
            while ($pushPostIds) {
                $postIds = array_splice($pushPostIds, 0, 200);
                $params = [];
                $params[] = $listkey;
                $params = array_merge($params, $postIds);
                call_user_func_array([$redis, 'lpush'], $params);
            }
            $redis->lTrim($listkey, 0, $maxIndex);
        } catch (\Exception $e) {
            $this->getLogger()->info($e->__toString());
        }
        $this->getLogger()->info('...执行完毕');
    }

    /**
     * @return void
     */
    public function run() {
        $this->updatePostsQueue();
    }
} 