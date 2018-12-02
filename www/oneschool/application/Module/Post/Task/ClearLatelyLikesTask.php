<?php
namespace Lychee\Module\Post\Task;

use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Task\Task;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ClearLatelyLikesTask implements Task {
    use ContainerAwareTrait;
    use ModuleAwareTrait;

    public function getName() {
        return 'clear-post-lately-like-record';
    }

    public function getDefaultInterval() {
        return 3600;
    }

    /**
     * @return Connection
     */
    private function getConnection() {
        return $this->container()->get('doctrine')->getManager()->getConnection();
    }


    /**
     * @return LoggerInterface
     */
    protected function getLogger() {
        return $this->container()->get('monolog.logger.task');
    }


    public function run() {
        // 每天3点期间进行清表动作
        $hourInDay = idate('H');
        $targetHour = 3;
        if ($targetHour != $hourInDay) {
            $this->getLogger()->info('每天'.$targetHour.'点才执行操作');
            return;
        }

        $conn = $this->getConnection();
        $sql = "SELECT 1 FROM like_post_period_count LIMIT 1";
        $statement = $conn->executeQuery($sql);
        $result = $statement->fetch(\PDO::FETCH_ASSOC);
        if (empty($result)) {
            $this->getLogger()->info('统计表已经是空的了');
            return;
        }

        $sql = "TRUNCATE TABLE like_post_period_count";
        $conn->executeUpdate($sql);

        $this->getLogger()->info('统计表清空完毕');

    }
}