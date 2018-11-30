<?php
/**
 * Created by PhpStorm.
 * User: ys160726
 * Date: 2016/12/20
 * Time: 下午4:30
 */

namespace Lychee\Bundle\AdminBundle\Command;


use Lychee\Module\Analysis\DailyCountTrait;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Lychee\Component\Foundation\ArrayUtility;

class ExportPurchaseAndGiftAnalysisCommand extends ContainerAwareCommand
{

    use DailyCountTrait;
    private $conn = null;
    protected function configure() {
        $this->setName('lychee-admin:analysis-purchase-gift')
            ->setDescription('export purchase and gift record')
            ->addArgument('startDate', InputArgument::REQUIRED, 'input start date')
            ->addArgument('endDate', InputArgument::REQUIRED, 'input end date');
    }
    protected function execute(InputInterface $input, OutputInterface $output) {
        $startDate = new \DateTime($input->getArgument('startDate'));
        $endDate = new \DateTime($input->getArgument('endDate'));
        $this->conn = $this->getContainer()->get('Doctrine')->getConnection();
        $purchaseQuery = $this->conn->prepare(
            "select pt.id, pt.payer, pt.total_fee, pt.start_time, pt.end_time, pp.ciyo_coin, pt.pay_type from ciyo_payment.payment_transaction as pt inner join ciyo_payment.payment_product_purchased as ppp ON pt.id=ppp.transaction_id inner join ciyo_payment.payment_products as pp ON ppp.product_id=pp.id where pt.end_time>=:startDate and pt.end_time<:endDate and pt.payer_type=2"
        );
        $purchaseQuery->bindValue(':startDate', $startDate->format('Y-m-d'));
        $purchaseQuery->bindValue(':endDate', $endDate->format('Y-m-d'));
        $purchaseQuery->execute();
        $purchaseResults = $purchaseQuery->fetchAll();
        $giftQuery = $this->conn->prepare(
            "SELECT lgp.id, lgp.user_id, lgp.pizus_id, lgp.gift_json, lgp.unit_price, lgp.gift_count, lgp.price, lgp.purchased_time FROM ciyo_live.live_gifts_purchased AS lgp WHERE purchased_time >= :startDate AND purchased_time < :endDate"
        );
        $giftQuery->bindValue(':startDate', $startDate->format('Y-m-d'));
        $giftQuery->bindValue(':endDate', $endDate->format('Y-m-d'));
        $giftQuery->execute();
        $giftResults = $giftQuery->fetchAll();
        $gifts = array_reduce($giftResults, function($result, $item){
            $result[$item['id']] = \GuzzleHttp\json_decode($item['gift_json']);
            return $result;
        });
        $giftResults = ArrayUtility::mapByColumn($giftResults, 'id');
        $fp = fopen($startDate->format('Y-m-d').'~'.$endDate->format('Y-m-d').'_ciyocoin_purchased_record.csv', 'w');
        fputcsv($fp, ['订单ID', '起始时间', '结束时间', '购买者ID', '总金额', '次元币数', '支付方式']);
        foreach ($purchaseResults as $pr) {
            fputcsv($fp, [
                $pr['id'],
                $pr['start_time'],
                $pr['end_time'],
                $pr['payer'],
                $pr['total_fee'],
                $pr['ciyo_coin'],
	            $pr['pay_type']
            ]);
        }
        fclose($fp);
        $fp = fopen($startDate->format('Y-m-d').'~'.$endDate->format('Y-m-d').'_gift_purchased_record.csv', 'w');
        fputcsv($fp, ['ID', '购买者ID', '购买者的皮哲ID', '主播ID', '礼物名称', '单价', '数量', '总价', '支付时间']);
        foreach ($giftResults as $id=>$value) {
            fputcsv($fp, [
                $id,
                $value['user_id'],
                $value['pizus_id'],
                $gifts[$id]->masterPid,
                $gifts[$id]->giftName,
                $value['unit_price'],
                $value['gift_count'],
                $value['price'],
                $value['purchased_time']
            ]);
        }
        fclose($fp);
    }
}