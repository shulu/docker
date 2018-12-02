<?php
/**
 * Created by PhpStorm.
 * User: ys160726
 * Date: 2016/12/28
 * Time: 下午4:20
 */

namespace Lychee\Module\Analysis\Task;


use Lychee\Component\Task\Task;
use Lychee\Module\Analysis\AnalysisType;

/**
 * Class CountRechargeUser
 * @package Lychee\Module\Analysis\Task
 */
class CountRechargeUser implements Task
{
    use CounterTaskTrait;

    /**
     * @var string
     */
    private $analysisType = AnalysisType::RECHARGE_USER;

    public function getName()
    {
        return 'count recharge user';
    }

    public function run()
    {
        $entityManager = $this->container()->get('doctrine')->getManager();
        $latestResult = $this->getLastCountDate($entityManager, $this->analysisType);
        $this->conn = $this->container()->get('Doctrine')->getConnection();
        if (!$latestResult) {
            $androidQuery = $this->conn->prepare(
                'SELECT pt.payer, pt.end_time FROM ciyo_payment.payment_transaction AS pt INNER JOIN ciyo_payment.payment_product_purchased AS ppp ON pt.id=ppp.transaction_id WHERE ppp.product_id > 20000 AND ppp.product_id < 40000 ORDER BY pt.end_time ASC'
            );
            $androidQuery->execute();
            $androidResult = $androidQuery->fetchAll();
            $appleQuery = $this->conn->prepare(
                'SELECT asr.payer,asr.time FROM ciyo_payment.app_store_receipt AS asr INNER JOIN ciyo_payment.payment_product_purchased AS ppp ON asr.transaction_id=ppp.appstore_transaction_id WHERE ppp.product_id > 20000 AND ppp.product_id < 40000 ORDER BY asr.time'
            );
            $appleQuery->execute();
            $appleResult = $appleQuery->fetchAll();

        }
        else {
            $latestDate = $latestResult->date->format('Y-m-d H:i:s');
            $androidQuery = $this->conn->prepare(
                'SELECT pt.payer, pt.end_time FROM ciyo_payment.payment_transaction AS pt INNER JOIN ciyo_payment.payment_product_purchased AS ppp ON pt.id=ppp.transaction_id WHERE pt.end_time >= :latestDate AND ppp.product_id > 20000 AND ppp.product_id < 40000 ORDER BY pt.end_time ASC'
            );
            $androidQuery->execute(array(
                ':latestDate' => $latestDate
            ));
            $androidResult = $androidQuery->fetchAll();
            $appleQuery = $this->conn->prepare(
                'SELECT asr.payer,asr.time FROM ciyo_payment.app_store_receipt AS asr INNER JOIN ciyo_payment.payment_product_purchased AS ppp ON asr.transaction_id=ppp.appstore_transaction_id WHERE asr.time >= :latestDate AND ppp.product_id > 20000 AND ppp.product_id < 40000 ORDER BY asr.time'
            );
            $appleQuery->execute(array(
                ':latestDate' => $latestDate
            ));
            $appleResult = $appleQuery->fetchAll();
        }
        $resultCount = [];
        $sumCount = [];
        $dailyCount = [];
        foreach ($appleResult as $apple) {
            $date = new \DateTime($apple['time']);
            $date = $date->format('Y-m-d');
            $resultCount[$date][] =  $apple['payer'];
        }
        foreach ($androidResult as $android) {
            $date = new \DateTime($android['end_time']);
            $date = $date->format('Y-m-d');
            $resultCount[$date][] = $android['payer'];
        }
        ksort($resultCount);

        $totalQuery = $this->conn->prepare(
            'SELECT COUNT(DISTINCT ppp.payer) AS total FROM ciyo_payment.payment_product_purchased AS ppp WHERE ppp.purchase_time < :latestDate AND ppp.product_id > 20000 AND ppp.product_id < 40000'
        );
        foreach ($resultCount as $key=>$result) {
            $resultCount[$key] = array_unique($result);
            $dailyCount[$key] = count($resultCount[$key]);
            $totalQuery->execute(
                array(
                    ':latestDate' => (new \DateTime($key))->modify('+1 day')->format('Y-m-d')
                )
            );
            $total = $totalQuery->fetchAll();
            $sumCount[$key] = array_column($total,'total')[0];
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