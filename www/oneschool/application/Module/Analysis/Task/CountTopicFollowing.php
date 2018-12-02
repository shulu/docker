<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14/11/7
 * Time: 下午7:00
 */

namespace Lychee\Module\Analysis\Task;


use Lychee\Component\Task\Task;
use Lychee\Module\Analysis\AnalysisType;

/**
 * Class CountTopic
 * @package Lychee\Module\Analysis\Task
 */
class CountTopicFollowing implements Task
{
    use CounterTaskTrait;

    /**
     * @var string
     */
    private $analysisType = AnalysisType::TOPIC_FOLLOWING;

    /**
     * @return string
     */
    public function getName()
    {
        return 'count topic following';
    }

    /**
     *
     */
    public function run()
    {
        $entityManager = $this->container()->get('doctrine')->getManager();
        $conn = $entityManager->getConnection();

        $latestDate = $this->getLatestDate();
        if (!$latestDate) {
            $startDate = new \DateTime('yesterday');
        } else {
            $startDate = $latestDate;
        }
        $endDate = clone $startDate;
        $endDate->modify('+1 day');
        $todayMidnight = new \DateTime('today');

        while ($endDate <= $todayMidnight) {
            $stmt = $conn->prepare("SELECT 1 FROM admin_daily_analysis WHERE type=:type AND date=:date");
            $stmt->bindValue(':type', $this->analysisType);
            $stmt->bindValue(':date', $startDate->format('Y-m-d'));
            $stmt->execute();
            $result = $stmt->fetch();
            if (!$result) {
                $stmt = $conn->prepare('SELECT COUNT(topic_id) following_count FROM topic_user_following WHERE create_time>=:startDate AND create_time<:endDate');
                $stmt->bindValue(':startDate', $startDate->format('Y-m-d H:i:s'));
                $stmt->bindValue(':endDate', $endDate->format('Y-m-d H:i:s'));
                $stmt->execute();
                $followingCount = $stmt->fetch();
                if ($followingCount) {
                    $insert = 'INSERT INTO admin_daily_analysis(date,type,dailyCount,totalCount) VALUE(:date,:type,:dailyCount,:totalCount)';
                    $insertStmt = $conn->prepare($insert);
                    $insertStmt->bindValue(':date', $startDate->format('Y-m-d'));
                    $insertStmt->bindValue(':type', $this->analysisType);
                    $insertStmt->bindValue(':dailyCount', $followingCount['following_count']);
                    $insertStmt->bindValue(':totalCount', 0);
                    $insertStmt->execute();
                }
            }

            $startDate = $endDate;
            $endDate = clone $startDate;
            $endDate->modify('+1 day');
        }
    }

    /**
     * @return \DateTime
     */
    private function getLatestDate() {
        /**
         * @var \PDO $conn
         */
        $conn = $this->container()->get('doctrine')->getManager()->getConnection();
        $stmt = $conn->prepare('SELECT date FROM admin_daily_analysis WHERE type=:type ORDER BY date DESC LIMIT 1');
        $stmt->bindValue(':type', $this->analysisType);
        $stmt->execute();
        $result = $stmt->fetch();

        if (!$result) {
            return new \DateTime('-30 days midnight');
        } else {
            $latestDate = new \DateTime($result['date']);

            return $latestDate->modify('+1 day');
        }
    }
}