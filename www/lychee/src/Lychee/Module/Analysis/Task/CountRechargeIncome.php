<?php
/**
 * Created by PhpStorm.
 * User: ys160726
 * Date: 2016/12/29
 * Time: 下午3:12
 */

namespace Lychee\Module\Analysis\Task;


use Lychee\Component\Task\Task;
use Lychee\Module\Analysis\AnalysisType;

/**
 * Class CountIncome
 * @package Lychee\Module\Analysis\Task
 */
class CountRechargeIncome implements Task
{
    use CounterTaskTrait;
    
    private $analysisType = AnalysisType::RECHARGE_INCOME;
    
    public function getName()
    {
        return 'count recharge income';
    }

    public function run()
    {
        $entityManager = $this->container()->get('doctrine')->getManager();
        $latestResult = $this->getLastCountDate($entityManager, $this->analysisType);
        $this->conn = $this->container()->get('Doctrine')->getConnection();
        if (!$latestResult) {
            $sum = 0;
            $Query = $this->conn->prepare(
                'SELECT ppp.total_fee,ppp.purchase_time FROM ciyo_payment.payment_product_purchased AS ppp WHERE ppp.product_id > 20000 AND ppp.product_id < 40000 ORDER BY ppp.purchase_time'
            );
            $Query->execute();
            $Results = $Query->fetchAll();
        }
        else {
            $latestDate = $latestResult->date->format('Y-m-d H:i:s');
            $sum = $latestResult->totalCount - $latestResult->dailyCount;
            $Query = $this->conn->prepare(
                'SELECT ppp.total_fee,ppp.purchase_time FROM ciyo_payment.payment_product_purchased AS ppp WHERE ppp.purchase_time >= :latestDate AND ppp.product_id > 20000 AND ppp.product_id < 40000 ORDER BY ppp.purchase_time'
            );
            $Query->execute(array(
                ':latestDate' => $latestDate
            ));
            $Results = $Query->fetchAll();
        }
        $resultCount = [];
        $sumCount = [];
        $dailyCount = [];
        foreach ($Results as $result) {
            $date = new \DateTime($result['purchase_time']);
            $date = $date->format('Y-m-d');
            $resultCount[$date][] = $result['total_fee'];
        }
        foreach ($resultCount as $key => $values) {
            $dailyIncome = 0;
            foreach ($values as $value) {
                $dailyIncome += $value;
            }
            $dailyCount[$key] = $dailyIncome;
            $sum += $dailyCount[$key];
            $sumCount[$key] = $sum;
        }
        $insertQuery = $this->conn->prepare(
            'insert into admin_daily_analysis(`date`,`type`,dailyCount,totalCount) VALUES (:date, :type, :dailyCount, :totalCount)'
        );
        $updateQuery = $this->conn->prepare(
            'UPDATE admin_daily_analysis SET dailyCount=:dailyCount,totalCount=:totalCount WHERE `date`=:latestDate AND `type`=:type'
        );
        if ($latestResult) {
            $lastDate = $latestResult->date->format('Y-m-d');

        }
        foreach ($dailyCount as $key=>$daily) {
            $date = $key;
            if (!$latestResult || ($latestResult && $date != $lastDate)){
                $insertQuery->execute(array(
                    ':date' => $date,
                    ':type' =>$this->analysisType,
                    ':dailyCount' => $daily,
                    ':totalCount' => $sumCount[$key]
                ));
            }
            else {
                $updateQuery->execute(array(
                    ':dailyCount' => $daily,
                    ':totalCount' => $sumCount[$key],
                    ':latestDate' => $date,
                    ':type' => $this->analysisType
                ));
            }
        }
    }
}