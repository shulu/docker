<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 8/12/15
 * Time: 6:29 PM
 */

namespace Lychee\Module\Analysis\Task;


use Lychee\Component\Task\Task;
use Lychee\Module\Analysis\AnalysisType;

class ChatMessage implements Task {

    use CounterTaskTrait;

    private $analysisType = AnalysisType::CHAT_MESSAGE;

    public function getName() {
        return 'chat message';
    }

    public function run() {
        /**
         * @var \Doctrine\ORM\EntityManager $entityManager
         */
        $entityManager = $this->container()->get('doctrine')->getManager();
        $conn = $entityManager->getConnection();
        $stmt = $conn->prepare("SELECT date FROM admin_daily_analysis WHERE type=:type ORDER BY date DESC LIMIT 1");
        $stmt->bindParam(':type', $this->analysisType);
        $stmt->execute();
        $result = $stmt->fetch();
        if ($result) {
            $startDate = new \DateTime($result['date']);
            $startDate->modify('tomorrow');
        } else {
            $stmt = $conn->prepare("SELECT time FROM ciyocon_chatto.status ORDER BY id ASC LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch();
            $startDate = new \DateTime($result['time']);
            $startDate->modify('midnight');
        }
        $today = new \DateTime('today');
        while ($startDate < $today) {
            $stmt = $conn->prepare("
                SELECT SUM(message_in_count) msg_count FROM ciyocon_chatto.status
                WHERE time >= :startDate AND time < :endDate
            ");
            $endDate = clone $startDate;
            $endDate->modify('tomorrow');
            $stmt->bindValue(':startDate', $startDate->format('Y-m-d'));
            $stmt->bindValue(':endDate', $endDate->format('Y-m-d'));
            $stmt->execute();
            $result = $stmt->fetch();
            if ($result && null !== $result['msg_count']) {
                $stmt = $conn->prepare("
                    INSERT INTO admin_daily_analysis(`date`,`type`,`dailyCount`,`totalCount`)
                    VALUE(:date, :type, :dailyCount, 0)
                ");
                $stmt->bindValue(':date', $startDate->format('Y-m-d'));
                $stmt->bindValue(':type', $this->analysisType);
                $stmt->bindValue(':dailyCount', $result['msg_count']);
                $stmt->execute();
                printf("[%s] Daily: %s\n", $startDate->format('Y-m-d'), $result['msg_count']);
            }
            $startDate = $endDate;
        }
    }
}