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
use Lychee\Module\Like\LikeState;
use Lychee\Module\Operation\LikingBot\LikingBot;
use Lychee\Module\Operation\LikingBot\TimeRange;

/**
 * Class CountPostLike
 * @package Lychee\Module\Analysis\Task
 */
class CountPostLike implements Task
{
    use CounterTaskTrait;

    /**
     * @var string
     */
    private $analysisType = AnalysisType::POST_LIKE;

    /**
     * @return string
     */
    public function getName()
    {
        return 'count post like';
    }

    /**
     *
     */
    public function run() {
        /** @var \PDO $conn */
        $conn = $this->container()->get('doctrine')->getManager()->getConnection();
        $bot = new LikingBot(
            $this->container()->get('doctrine'),
            $this->container()->get('lychee.module.like'),
            $this->container()->get('monolog.logger.task')
        );
        $tomorrow = new \DateTime('tomorrow');
        $stmt = $conn->prepare(
            "SELECT * FROM admin_daily_analysis
                WHERE `type`=:type
                ORDER BY `date` DESC 
                LIMIT 2"
        );
        $stmt->bindValue(':type', AnalysisType::POST_LIKE);
        $stmt->execute();
        $totalCount = 0;
        if ($lastResult = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $startDate = new \DateTime($lastResult['date']);
            if ($secLastResult = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $totalCount = $secLastResult['totalCount'];
            }
        } else {
            $startDate = new \DateTime('midnight');
        }

        while ($startDate < $tomorrow) {
            $endDate = clone $startDate;
            $endDate->modify('+1 day');
            $stmt = $conn->prepare("SELECT COUNT(id) like_count FROM like_post
                WHERE update_time>=:startDate AND update_time<:endDate AND state=:state
            ");
            $stmt->bindValue(':startDate', $startDate->format('Y-m-d'));
            $stmt->bindValue(':endDate', $endDate->format('Y-m-d'));
            $stmt->bindValue(':state', LikeState::NORMAL);
            $count = 0;
            if ($stmt->execute()) {
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                $count = $result['like_count'];
            }
            $botStart = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $startDate->format('Y-m-d H:i:s'));
            $botEndInterval = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $endDate->format('Y-m-d H:i:s'));
            $botCount = $bot->countLikePerformByBotAtTimeRange(new TimeRange($botStart, $botEndInterval));
            $count -= $botCount;

            $totalCount += $count;
            $stmt = $conn->prepare(
                "INSERT INTO admin_daily_analysis(`date`, `type`, `dailyCount`, `totalCount`)
                VALUES(:date, :type, :dailyCount, :totalCount)
                ON DUPLICATE KEY UPDATE `dailyCount`=:dailyCount, `totalCount`=:totalCount"
            );
            $stmt->bindValue(':date', $startDate->format('Y-m-d'));
            $stmt->bindValue(':type', AnalysisType::POST_LIKE);
            $stmt->bindValue(':dailyCount', $count);
            $stmt->bindValue(':totalCount', $totalCount);
            $stmt->execute();
            printf(
                "Start: %s\tEnd: %s\tDailyCount: %s\tTotalCount: %s\n",
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
                $count,
                $totalCount
            );
            $startDate = clone $endDate;
        }
    }
}