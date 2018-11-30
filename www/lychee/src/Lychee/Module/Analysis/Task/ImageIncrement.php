<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 8/12/15
 * Time: 12:12 PM
 */

namespace Lychee\Module\Analysis\Task;


use Lychee\Component\Task\Task;
use Lychee\Module\Analysis\AnalysisType;

class ImageIncrement implements Task {

    use CounterTaskTrait;

    private $analysisType = AnalysisType::IMAGE_INCREMENT;

    public function getName() {
        return 'image increment';
    }

    public function run() {
        /**
         * @var \Doctrine\ORM\EntityManager $entityManager
         */
        $entityManager = $this->container()->get('doctrine')->getManager();
        $conn = $entityManager->getConnection();
        $sql = "
            SELECT *
            FROM admin_daily_analysis
            WHERE type = :type
            ORDER BY date DESC
            LIMIT 1
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam('type', $this->analysisType);
        $stmt->execute();
        $result = $stmt->fetch();
        if (null == $result) {
            $stmt = $conn->prepare("SELECT create_time FROM post ORDER BY id ASC LIMIT 1");
            $stmt->execute();
            $postResult = $stmt->fetch();
            $date = new \DateTime($postResult['create_time']);
            $date->modify('yesterday');
            $total = 0;
        } else {
            $date = new \DateTime($result['date']);
            $total = $result['totalCount'];
        }
        $today = new \DateTime('today');
        $date->modify('tomorrow');
        while ($date < $today) {
            $nextDate = clone $date;
            $nextDate->modify('tomorrow');
            $sum = 0;
            $sql = "SELECT * FROM post WHERE create_time >= :startDate AND create_time < :endDate";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':startDate', $date->format('Y-m-d'));
            $stmt->bindValue(':endDate', $nextDate->format('Y-m-d'));
            $stmt->execute();
            $result = $stmt->fetchAll();
            if ($result) {
                foreach ($result as $post) {
                    $annotation = json_decode($post['annotation']);
                    do {
                        if ($annotation) {
                            if (isset($annotation->multi_photos)) {
                                $sum += count($annotation->multi_photos);
                                break;
                            }
                        }
                        if ($post['image_url']) {
                            $sum += 1;
                        }
                    } while (0);
                }
            }
            $total += $sum;
            $sql = "
                INSERT INTO admin_daily_analysis(`date`, `type`, `dailyCount`, `totalCount`)
                VALUE(:date, :type, :dailyCount, :totalCount)
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':date', $date->format('Y-m-d'));
            $stmt->bindValue(':type', $this->analysisType);
            $stmt->bindValue(':dailyCount', $sum);
            $stmt->bindValue(':totalCount', $total);
            $stmt->execute();
            printf("[%s] Daily: %s, Total: %s\n", $date->format('Y-m-d'), $sum, $total);
            $date = $nextDate;
        }
    }
}