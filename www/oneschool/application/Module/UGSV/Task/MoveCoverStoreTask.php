<?php
namespace Lychee\Module\UGSV\Task;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Task\Task;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;


class MoveCoverStoreTask implements Task {
    use ContainerAwareTrait;
    use ModuleAwareTrait;


    /**
     * @return string
     */
    public function getName() {
        return 'move-video-cover-store';
    }

    /**
     * @return integer
     */
    public function getDefaultInterval() {
        return 12*3600;
    }

    /**
     * @return Connection
     */
    private function getConnection() {
        return $this->container->get('doctrine')->getManager()->getConnection();
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger() {
        return $this->container->get('monolog.logger.task');
    }

    public function move() {
        $startTime = time() - 2*24*3600;
        $endTime = $startTime + 24*3600;
        $startDate = date('Y-m-d H:i:s', $startTime);
        $endDate = date('Y-m-d H:i:s', $endTime);
        $this->getLogger()->info('开始查找 ('.$startDate.'~'.$endDate.') 的记录...');
        $sql = "select min(id) min_id, max(id) max_id from post where create_time > ? and create_time <= ?";
        $conn = $this->getConnection();

        $statement = $conn->executeQuery($sql, array($startDate, $endDate));
        $result = $statement->fetch(\PDO::FETCH_ASSOC);
        $this->getLogger()->info('...处理完毕');

        if (empty($result)) {
            $this->getLogger()->info('没有符合条件的记录');
            return false;
        }

        $minId = $result['min_id'];
        $maxId = $result['max_id'];

        $this->getLogger()->info('开始迁移图片...');

        $sql = <<<'SQL'
SELECT post_id FROM ugsv_post WHERE post_id > ? and post_id <= ? ORDER BY post_id ASC LIMIT 1000
SQL;
        $sum = 0;
        $cursorId = $minId;
        $postModule = $this->post();
        while (true) {
            $statement = $conn->executeQuery($sql, array($cursorId, $maxId));
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($result as $item) {
                $postModule->moveShortVideoCoverStoreById($item['post_id']);
                usleep(100000);
            }

            $presum = count($result);
            $this->getLogger()->info('处理了 '.$presum.' 条记录');

            $sum += $presum;

            if ($presum < 1000) {
                break;
            }

            $cursorId = $result[$presum - 1]['post_id'];

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