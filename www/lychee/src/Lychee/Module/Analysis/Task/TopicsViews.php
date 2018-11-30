<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 10/12/15
 * Time: 12:15 PM
 */

namespace Lychee\Module\Analysis\Task;


use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Task\Task;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * 次元排行
 * Class TopicsViews
 * @package Lychee\Module\Analysis\Task
 */
class TopicsViews implements Task {

    use ContainerAwareTrait;
    use ModuleAwareTrait;

    public function getName() {
        return 'topics views';
    }

    public function getDefaultInterval() {
        return 3600 - 60;
    }

    public function run() {
        $now = new \DateTime();
        $hour = $now->format('H');
        if ($hour >= 3 && $hour < 4) {
//        if (1) {
            $today = new \DateTime('today');
            /**
             * @var \Doctrine\ORM\EntityManager $entityManager
             */
            $entityManager = $this->container()->get('doctrine')->getManager();
            $conn = $entityManager->getConnection();
            $latestViewsLogStmt = $conn->prepare("SELECT date FROM admin_topics_views ORDER BY id DESC LIMIT 1");
            $latestViewsLogStmt->execute();
            $latestViewsLogRow = $latestViewsLogStmt->fetch(\PDO::FETCH_NUM);
            if ($latestViewsLogRow) {
                $startLogDate = new \DateTime($latestViewsLogRow[0]);
                $startLogDate->modify('+1 day');
            } else {
                $latestLogStmt = $conn->prepare("SELECT create_time FROM topic_visitor_log ORDER BY id ASC LIMIT 1");
                $latestLogStmt->execute();
                $latestLogRow = $latestLogStmt->fetch(\PDO::FETCH_NUM);
                if ($latestLogRow) {
                    $startLogDate = new \DateTime($latestLogRow[0]);
                    $startLogDate->modify('midnight');
                } else {
                    $startLogDate = new \DateTime('yesterday');
                }
            }
            $endLogDate = clone $startLogDate;
            $endLogDate->modify('+1 day');
            $addLogStmt = $conn->prepare(
                'INSERT IGNORE INTO admin_topics_views(topic_id,`date`,uni_views,views)
                VALUE (:topicId, :date, :uniViews, :views)'
            );
            $dateCount = 0;
            while ($endLogDate <= $today && $dateCount < 5) {
                $startTestTime = time();
                $logStmt = $conn->prepare(
                    "SELECT * FROM topic_visitor_log WHERE create_time>=:start AND create_time<:end"
                );
                $logStmt->execute(array(
                    ':start' => $startLogDate->format('Y-m-d'),
                    ':end' => $endLogDate->format('Y-m-d')
                ));
                $logArr = [];
                while ($row = $logStmt->fetch(\PDO::FETCH_ASSOC)) {
                    $topicId = $row['topic_id'];
                    $userId = $row['user_id'];
                    if (isset($logArr[$topicId])) {
                        $logArr[$topicId]['views'] += 1;
                        if (!in_array($userId, $logArr[$topicId]['uni_views'])) {
                            array_push($logArr[$topicId]['uni_views'], $userId);
                        }
                    } else {
                        $logArr[$topicId] = array(
                            'uni_views' => array($userId),
                            'views' => 1
                        );
                    }
                }
                foreach ($logArr as $topicId => $log) {
                    $addLogStmt->execute(array(
                        ':topicId' => $topicId,
                        ':date' => $startLogDate->format('Y-m-d'),
                        ':uniViews' => count($log['uni_views']),
                        ':views' => $log['views']
                    ));
                }
                printf(
                    "Date: %s\tLogs: %s\tTime: %ss\n",
                    $startLogDate->format('Y-m-d'),
                    count($logArr),
                    time() - $startTestTime
                );

                $startLogDate->modify('+1 day');
                $endLogDate->modify('+1 day');
                $dateCount += 1;
            }
        }
    }
}