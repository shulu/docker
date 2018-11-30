<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 8/12/15
 * Time: 6:29 PM
 */

namespace Lychee\Module\Analysis\Task;


use Doctrine\ORM\EntityManager;
use Lychee\Component\Task\Task;
use Lychee\Module\Analysis\AnalysisType;

class TopicViews implements Task {

    use CounterTaskTrait;

    private $analysisType = AnalysisType::TOPIC_VIEWS;

    public function getName() {
        return 'topic views';
    }

    public function run() {
        /**
         * @var \Doctrine\ORM\EntityManager $entityManager
         */
        $entityManager = $this->container()->get('doctrine')->getManager();
        $conn = $entityManager->getConnection();
        /**
         * @var \Lychee\Module\Analysis\Entity\AdminDailyAnalysis $lastLog
         */
        $lastLog = $this->getLastCountDate($entityManager, $this->analysisType);
        if (null !== $lastLog) {
            $startDate = $lastLog->date;
            $startDate->modify('next day');
        } else {
            $stmt = $conn->prepare("SELECT create_time FROM topic_visitor_log ORDER BY id ASC LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch();
            if ($result) {
                $startDate = new \DateTime($result['create_time']);
                $startDate->modify('midnight');
            } else {
                $startDate = new \DateTime('today');
            }
        }
        $originStartDate = clone $startDate;
        $today = new \DateTime('today');
        while ($startDate < $today) {
            $endDate = clone $startDate;
            $endDate->modify('next day');
            $stmt = $conn->prepare("SELECT COUNT(topic_id) topic_count FROM (
                SELECT DISTINCT(topic_id) FROM topic_visitor_log
                WHERE create_time >= :startDate AND create_time < :endDate) topic");
            $stmt->bindValue(':startDate', $startDate->format('Y-m-d'));
            $stmt->bindValue(':endDate', $endDate->format('Y-m-d'));
            $stmt->execute();
            $result = $stmt->fetch();
            if ($result) {
                $topicCount = $result['topic_count'];
            } else {
                $topicCount = 0;
            }
            $stmt = $conn->prepare("INSERT INTO admin_daily_analysis(date,type,dailyCount,totalCount)
                VALUE(:date, :type, :dailyCount, 0)");
            $stmt->bindValue(':date', $startDate->format('Y-m-d'));
            $stmt->bindValue(':type', $this->analysisType);
            $stmt->bindValue(':dailyCount', $topicCount);
            $stmt->execute();
            printf("[%s]Daily: %s\n", $startDate->format('Y-m-d'), $topicCount);
            $startDate = $endDate;
        }
        $this->topicVisitor($entityManager, $originStartDate);
//        echo 'done';exit;
        $this->removeLog($entityManager);
    }

    private function topicVisitor(EntityManager $entityManager, \DateTime $startDate) {
        $analysisType = AnalysisType::TOPIC_VISITOR;
        $conn = $entityManager->getConnection();
        $today = new \DateTime('today');
        while ($startDate < $today) {
            $endDate = clone $startDate;
            $endDate->modify('next day');
            $stmt = $conn->prepare("
                SELECT COUNT(user_id) user_count
                FROM (
                    SELECT user_id, topic_id
                    FROM topic_visitor_log
                    WHERE create_time >= :startDate AND create_time < :endDate
                    GROUP BY user_id, topic_id
                ) topic_user
            ");
            $stmt->bindValue(':startDate', $startDate->format('Y-m-d'));
            $stmt->bindValue(':endDate', $endDate->format('Y-m-d'));
            $stmt->execute();
            $result = $stmt->fetch();
            if (null === $result) {
                $userSum = 0;
            } else {
                $userSum = (int)$result['user_count'];
            }
            $user80 = ceil($userSum * 0.8);
            $stmt = $conn->prepare("
                SELECT topic_id, COUNT(user_id) user_count
                FROM (
                    SELECT topic_id, user_id FROM `topic_visitor_log`
                    WHERE create_time >= :startDate AND create_time < :endDate
                    GROUP BY topic_id, user_id
                ) topic_visitor
                GROUP BY topic_id
                ORDER BY user_count DESC
            ");
            $stmt->bindValue(':startDate', $startDate->format('Y-m-d'));
            $stmt->bindValue(':endDate', $endDate->format('Y-m-d'));
            $stmt->execute();
            $result = $stmt->fetchAll();
            $topicCount = 0;
            $userCount = 0;
            foreach ($result as $row) {
                $userCount += $row['user_count'];
                if ($userCount >= $user80) {
                    break;
                }
                $topicCount += 1;
            }
            // Insert
            $stmt = $conn->prepare("
                INSERT INTO admin_daily_analysis(`date`, `type`, `dailyCount`, `totalCount`)
                VALUE(:date, :type, :dailyCount, 0)
            ");
            $stmt->bindValue(':date', $startDate->format('Y-m-d'));
            $stmt->bindValue(':type', $analysisType);
            $stmt->bindValue(':dailyCount', $topicCount);
            $stmt->execute();
            printf("[%s]Daily: %s\n", $startDate->format('Y-m-d'), $topicCount);
            $startDate = $endDate;
        }
    }

    private function removeLog(EntityManager $entityManager) {
        $endDate = new \DateTime('-30 days midnight');
        $conn = $entityManager->getConnection();
        $stmt = $conn->prepare("
            DELETE FROM topic_visitor_log WHERE create_time < :endDate"
        );
        $stmt->bindValue(':endDate', $endDate->format('Y-m-d'));
        $stmt->execute();
    }
}