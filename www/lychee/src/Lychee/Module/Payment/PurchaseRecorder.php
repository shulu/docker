<?php
namespace Lychee\Module\Payment;

use Doctrine\ORM\EntityManager;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Foundation\StringUtility;
use Lychee\Module\Payment\Entity\AppStoreReceipt;
use Lychee\Module\Payment\Entity\PaymentProduct;
use Lychee\Module\Payment\Entity\PaymentProductPurchased;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManagerInterface;

class PurchaseRecorder {

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * ProductManager constructor.
     * @param RegistryInterface $doctrine
     */
    public function __construct($doctrine) {
        $this->em = $doctrine->getManager();
    }

	/**
	 * @param $payer
	 * @param $transactionId
	 * @param $productId
	 * @param $totalFee
	 * @param $payType
	 * @param string $appstoreTransactionId
	 * @param \DateTime|null $purchaseTime
	 * @param $promotionCodeId
	 */
    public function record($payer, $transactionId, $productId, $totalFee, $payType = '', $appstoreTransactionId = '',
	    \DateTime $purchaseTime = null, $promotionCodeId = 0) {

    	if (!$purchaseTime) {
		    $purchaseTime = new \DateTime();
	    }
        $sql = 'INSERT INTO 
				ciyo_payment.payment_product_purchased(payer, transaction_id, product_id, appstore_transaction_id, promotion_code_id, total_fee, purchase_time, pay_type)
				VALUES(?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE product_id = product_id';
        $this->em->getConnection()->executeUpdate(
        	$sql,
	        array($payer, $transactionId, $productId, $appstoreTransactionId, $promotionCodeId, $totalFee, $purchaseTime->format('Y-m-d H:i:s'), $payType),
            array(\PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR)
        );
    }

//	public function listRecords($cursor = 0, $count = 20, &$next_cursor = null, $appId = null ) {
//
//    	if (!$cursor) {
//    		$cursor = PHP_INT_MAX;
//	    }
//
//		$qb = $this->em->createQueryBuilder()
//			->select('r')
//			->from(PaymentProductPurchased::class, 'r')
//			->where('r.id < :cursor');
//		if (!is_null($appId)) {
//			$qb->where('r.appId = :appId')->setParameter('appId', $appId);
//		}
//		$qb->setParameter('cursor', $cursor)->setMaxResults($count);
//		$results = $qb->getQuery()->execute();
//		return $results;
//    }

	/**
	 * @param string $payer
	 *
	 * @return array
	 */
    public function listUserRecords($payer, $appId) {

    	$conn = $this->em->getConnection();
    	$sql = "SELECT p.id, p.payer, p.product_id, p.transaction_id, p.appstore_transaction_id, p.promotion_code_id, p.total_fee, p.purchase_time, pp.app_store_id
				FROM ciyo_payment.`payment_product_purchased` AS p
				JOIN ciyo_payment.payment_products AS pp ON pp.id=p.product_id
				WHERE p.payer=" . $conn->quote($payer) . " AND pp.app_id = ". $appId ." 
				ORDER BY p.id ASC";
    	$stmt = $conn->query($sql);
    	if (false !== $stmt) {
    		$result = $stmt->fetchAll();
	    }

	    return isset($result)? $result:[];
    }

    public function transferAppStoreReceiptDeviceId($deviceId, $userId, $appId){

	    $sql = 'UPDATE ciyo_payment.app_store_receipt SET payer=? WHERE payer=? AND product_id IN 
				(SELECT app_store_id FROM ciyo_payment.payment_products WHERE app_id = ?)';
	    $this->em->getConnection()->executeUpdate(
		    $sql,
		    array($userId, $deviceId, $appId),
		    array(\PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_INT)
	    );
    }

    public function transferPayerDeviceIdToUserId($deviceId, $userId, $appId){

	    $sql = 'UPDATE ciyo_payment.payment_product_purchased SET payer=? WHERE payer=? AND product_id IN 
				(SELECT id FROM ciyo_payment.payment_products WHERE app_id = ?)';
	    $this->em->getConnection()->executeUpdate(
		    $sql,
		    array($userId, $deviceId, $appId),
		    array(\PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_INT)
	    );
    }

    public function getPurchaseRecord($payer, $productId){

    	$record = $this->em->getRepository(PaymentProductPurchased::class)->findOneBy([
		    'payer' => $payer,
		    'productId' => $productId,
	    ]);

    	return $record;
    }

    public function getSumTotalFeeRecord($payer, $storyId){

	    $conn = $this->em->getConnection();
	    $sql = "SELECT SUM(total_fee) fee FROM ciyo_payment.`payment_product_purchased` WHERE product_id IN 
				(SELECT product_id FROM ciyo_extramessage.`product_promotion_config` WHERE story_id=".$storyId.")  
				GROUP BY payer HAVING payer=" . $conn->quote($payer);
	    $stmt = $conn->query($sql);
	    if (false !== $stmt) {
		    $result = $stmt->fetchAll();
	    }

	    if(!empty($result)) {
		    return $result[0]['fee'];
	    }else{
	    	return 0;
	    }
    }

	public function findById($paymentProductPurchaseId){

		$record = $this->em->getRepository(PaymentProductPurchased::class)->findOneBy([
			'id' => $paymentProductPurchaseId
		]);

		return $record;
	}

    public function getRecordByAppStoreTransactionId($appStoreTransactionId){

	    $record = $this->em->getRepository(PaymentProductPurchased::class)->findOneBy([
		    'appStoreTransactionId' => $appStoreTransactionId
	    ]);

	    return $record;
    }

	/**
	 * @param $transactionId
	 *
	 * @return null|PaymentProductPurchased
	 */
	public function getRecordByTransactionId($transactionId, $payType = null){

		$filter = [
			'transactionId' => $transactionId
		];
		if (!is_null($payType)) {
			$filter['payType'] = $payType;
		}
		$record = $this->em->getRepository(PaymentProductPurchased::class)->findOneBy($filter);

		return $record;
	}

    public function getRecordByPayer($payer){

        $record = $this->em->getRepository(PaymentProductPurchased::class)->findBy([
            'payer' => $payer
        ]);

        return $record;
    }

	/**
	 * @param $payer
	 * @param $transactionId
	 * @param $appStoreProductId
	 * @param $time
	 * @param $receipt
	 * @param $env
	 * @param bool $valid
     * @param float $totalFee
	 *
	 * @return AppStoreReceipt|null
	 */
    public function recordAppStoreReceipt($payer, $transactionId, $appStoreProductId, $time, $receipt, $totalFee, $env, $valid = false) {
        $d = new \DateTime($time);
        $d->setTimezone(new \DateTimeZone(date_default_timezone_get()));

	    $appStoreReceipt = $this->em->getRepository(AppStoreReceipt::class)->findOneBy([
	    	'transactionId' => $transactionId
	    ]);
	    if (!$appStoreReceipt) {
	    	$appStoreReceipt                = new AppStoreReceipt();
		    $appStoreReceipt->payer         = $payer;
		    $appStoreReceipt->transactionId = $transactionId;
		    if (StringUtility::endsWith($appStoreProductId, '_ios')) {
		    	$appStoreReceipt->newProductId = $appStoreProductId;
		    	$appStoreReceipt->productId = substr($appStoreProductId, 0, -4);
		    }
		    else {
			    $appStoreReceipt->productId     = $appStoreProductId;
			    $appStoreReceipt->newProductId = '';
		    }
		    $appStoreReceipt->time          = $d;
		    $appStoreReceipt->receipt       = $receipt;
		    $appStoreReceipt->env           = $env;
		    $appStoreReceipt->valid         = $valid;
		    $appStoreReceipt->totalFee      = $totalFee;
		    $this->em->persist($appStoreReceipt);
		    $this->em->flush();
	    }
	    return $appStoreReceipt;
    }

    public function makeAppStoreReceiptValid(AppStoreReceipt $appStoreReceipt, $productId, $price, $createDate, $env) {
        $appStoreReceipt->valid = true;
	    $appStoreReceipt->time = (new \DateTime($createDate))->setTimezone(new \DateTimeZone(date_default_timezone_get()));
	    $appStoreReceipt->env = $env;
	    $this->em->beginTransaction();
	    try {
		    $this->em->flush();
		    $this->record($appStoreReceipt->payer, 0, $productId, $price, '', $appStoreReceipt->transactionId);
		    $this->em->commit();
	    } catch (\Exception $e) {
	        $this->em->rollback();
		    throw $e;
	    }

	    return $appStoreReceipt;
    }

	public function updateAppStoreReceipt( $receipt ) {
		$this->em->flush($receipt);
    }

    public function hasUploadedReceipt($payer, $appId){

    	$hasUpload = false;
	    $conn = $this->em->getConnection();
	    $sql = "SELECT p.id
				FROM ciyo_payment.`app_store_receipt` AS p
				JOIN ciyo_payment.payment_products AS pp ON pp.app_store_id=p.product_id
				WHERE p.payer=" . $conn->quote($payer) . " AND pp.app_id = ". $appId ." 
				ORDER BY p.id ASC";
	    $stmt = $conn->query($sql);
	    if (false !== $stmt) {
		    $result = $stmt->fetchAll();
		    $hasUpload = (count($result) > 0);
	    }

	    return $hasUpload;
    }

	/**
	 * @param $payer
	 * @param $cursor
	 * @param $count
	 * @param null $nextCursor
	 *
	 * @return array
	 */
	public function listSuccessfulPurchasedRecords($payer, $cursor, $count, &$nextCursor = null) {
		if (0 == $cursor) {
			$cursor = PHP_INT_MAX;
		}
		$conn = $this->em->getConnection();
		$sql = 'SELECT pp.id, pp.payer, pp.transaction_id, pp.appstore_transaction_id, pp.product_id, p.price, p.ciyo_coin
				FROM ciyo_payment.payment_product_purchased pp
 				JOIN ciyo_payment.payment_products p ON p.id=pp.product_id
 				WHERE pp.id < ' . $conn->quote($cursor) . ' AND pp.payer=' . $conn->quote($payer) . '
 				ORDER BY pp.id DESC
 				LIMIT ' . (int)$count;
		$stmt = $conn->query($sql);
		$purchasedRecords = $stmt->fetchAll();
		if (is_array($purchasedRecords) && count($purchasedRecords) > 0) {
			$nextCursor = $purchasedRecords[count($purchasedRecords) - 1]['id'];
		} else {
			$nextCursor = 0;
		}

		$transactionIds = [];
		$appStoreTransactionIds = [];
		foreach ($purchasedRecords as $record) {
			if ($record['transaction_id'] != 0) {
				// Third Payment
				$transactionIds[] = $record['transaction_id'];
			} elseif ($record['appstore_transaction_id']) {
				// AppStore Payment
				$appStoreTransactionIds[] = $record['appstore_transaction_id'];
			}
		}
		$transactionsInfo = $this->getTransactionsInfo($transactionIds);
		$appStoreTransactionsInfo = $this->getAppStoreTransactionInfo($appStoreTransactionIds);
		$result = [];
		foreach ($purchasedRecords as $record) {
			$tmp = [
				'id' => $record['id'],
				'ciyo_coin' => $record['ciyo_coin']
			];
			if ($record['transaction_id'] != 0) {
				$tmp['total_fee'] = $transactionsInfo[$record['transaction_id']]['total_fee'];
				$tmp['pay_time'] = $transactionsInfo[$record['transaction_id']]['pay_time'];
			} elseif ($record['appstore_transaction_id']) {
				$tmp['total_fee'] = $record['price'];
				$tmp['pay_time'] = $appStoreTransactionsInfo[$record['appstore_transaction_id']]['time'];
			}

			$result[] = $tmp;
		}

		return $result;
    }

    private function getTransactionsInfo($ids) {
    	if (empty($ids)) {
    		return [];
	    }
    	$conn = $this->em->getConnection();
	    $sql = 'SELECT f.transaction_id, f.pay_time, t.total_fee, t.pay_type
				FROM ciyo_payment.payment_transaction_finish f
				JOIN ciyo_payment.payment_transaction t ON t.id=f.transaction_id
				WHERE f.transaction_id IN (' . implode(',', $ids) . ')';
	    $stmt = $conn->query($sql);
	    $result = $stmt->fetchAll();
	    if (!$result) {
	    	return [];
	    }

	    return ArrayUtility::mapByColumn($result, 'transaction_id');
    }

    private function getAppStoreTransactionInfo($ids) {
	    if (empty($ids)) {
		    return [];
	    }
	    $queryIds = [];
	    foreach ($ids as $id) {
	        $queryIds[] = "'$id'";
        }
    	$conn = $this->em->getConnection();
	    $sql = 'SELECT transaction_id, `time` FROM ciyo_payment.app_store_receipt
				WHERE transaction_id IN(' . implode(',', $queryIds) . ')';
	    $stmt = $conn->query($sql);
	    $result = $stmt->fetchAll();
	    if (!$result) {
	    	return [];
	    }

	    return ArrayUtility::mapByColumn($result, 'transaction_id');
    }

	public function getRechargeDetailByDate($page=1, $count=20, &$total, $start, $end) {
		$cursor = $count * ($page - 1);
		$end = (new \DateTime($end))->modify('+1 day')->modify('-1 second')->format('Y-m-d H:i:s');
		$conn = $this->em->getConnection();
		$sql = 'SELECT tb.payer,tb.purchase_time,tb.total_fee,tb.ciyo_coin,tb.transaction_id, tb.appstore_transaction_id, tb.product_id,tb.pay_type FROM (SELECT ppp.payer,ppp.purchase_time,ppp.total_fee,pp.ciyo_coin,ppp.transaction_id, ppp.appstore_transaction_id, ppp.product_id, pt.pay_type, asr.env FROM ciyo_payment.payment_product_purchased AS ppp INNER JOIN ciyo_payment.payment_products AS pp ON ppp.product_id=pp.id LEFT JOIN ciyo_payment.payment_transaction AS pt ON pt.id=ppp.transaction_id LEFT JOIN ciyo_payment.app_store_receipt AS asr ON ppp.appstore_transaction_id=asr.transaction_id WHERE ppp.product_id > 20000 AND ppp.product_id < 40000 AND ppp.purchase_time >= "'.$start.'" And ppp.purchase_time <= "'.$end.'" ORDER BY ppp.purchase_time ASC ) AS tb WHERE tb.env IS null OR tb.env=\'Production\' ORDER BY tb.purchase_time ASC';
		$sqls = $sql.' LIMIT '.$cursor.', '.$count;
		$total = count($conn->query($sql)->fetchAll());
		$result = $conn->query($sqls)->fetchAll();
		if (!$result) {
			return array();
		}
		return $result;
	}

	public function getAllRechargeDetailByDate($start, $end) {
		$end = (new \DateTime($end))->modify('+1 day')->modify('-1 second')->format('Y-m-d H:i:s');
		$conn = $this->em->getConnection();
		$sql = 'SELECT tb.payer,tb.purchase_time,tb.total_fee,tb.ciyo_coin,tb.transaction_id, tb.appstore_transaction_id, tb.product_id,tb.pay_type FROM (SELECT ppp.payer,ppp.purchase_time,ppp.total_fee,pp.ciyo_coin,ppp.transaction_id, ppp.appstore_transaction_id, ppp.product_id, pt.pay_type, asr.env FROM ciyo_payment.payment_product_purchased AS ppp INNER JOIN ciyo_payment.payment_products AS pp ON ppp.product_id=pp.id LEFT JOIN ciyo_payment.payment_transaction AS pt ON pt.id=ppp.transaction_id LEFT JOIN ciyo_payment.app_store_receipt AS asr ON ppp.appstore_transaction_id=asr.transaction_id WHERE ppp.product_id > 20000 AND ppp.product_id < 40000 AND ppp.purchase_time >= "'.$start.'" And ppp.purchase_time <= "'.$end.'" ORDER BY ppp.purchase_time ASC ) AS tb WHERE tb.env IS null OR tb.env=\'Production\' ORDER BY tb.purchase_time ASC';
		$result = $conn->query($sql)->fetchAll();
		if (!$result) {
			return array();
		}
		return $result;
	}

	public function getRechargeDetailByAuthorId($authorId, $count=20, $page=1, &$total) {
		$cursor = $count * ($page - 1);
		$conn = $this->em->getConnection();
		$sql = 'SELECT tb.payer,tb.purchase_time,tb.total_fee,tb.ciyo_coin,tb.transaction_id, tb.appstore_transaction_id, tb.product_id,tb.pay_type FROM (SELECT ppp.payer,ppp.purchase_time,ppp.total_fee,pp.ciyo_coin,ppp.transaction_id, ppp.appstore_transaction_id, ppp.product_id, pt.pay_type, asr.env FROM ciyo_payment.payment_product_purchased AS ppp INNER JOIN ciyo_payment.payment_products AS pp ON ppp.product_id=pp.id LEFT JOIN ciyo_payment.payment_transaction AS pt ON pt.id=ppp.transaction_id LEFT JOIN ciyo_payment.app_store_receipt AS asr ON ppp.appstore_transaction_id=asr.transaction_id WHERE ppp.product_id > 20000 AND ppp.product_id < 40000 AND ppp.payer = '.$authorId.' ORDER BY ppp.purchase_time DESC) AS tb WHERE tb.env IS null OR tb.env=\'Production\' ORDER BY tb.purchase_time DESC';
		$sqls = $sql.' LIMIT '.$cursor.', '.$count;
		$total = count($conn->query($sql)->fetchAll());
		$results = $conn->query($sqls)->fetchAll();
		return $results;
	}

	public function getExchangeDetailByDate($page=1, $count=20, &$total, $start, $end) {
		$cursor = $count * ($page - 1);
		$end = (new \DateTime($end))->modify('+1 day')->modify('-1 second')->format('Y-m-d H:i:s');
		$conn = $this->em->getConnection();
		$sql = 'SELECT pcp.user_id, pcp.finish_time, pcp.ciyo_total_fee, pcp.out_trade_no, pcp.item_name FROM  ciyo_payment.payment_ciyocoin_purchased AS pcp WHERE pcp.finish_time IS NOT NULL AND pcp.finish_time >= "'.$start.'" AND pcp.finish_time <= "'.$end.'" ORDER BY pcp.finish_time ASC';
		$sqls = $sql.' LIMIT '.$cursor.', '.$count;
		$total = count($conn->query($sql)->fetchAll());
		$result = $conn->query($sqls)->fetchAll();
		if (!$result) {
			return array();
		}
		return $result;
	}

	public function getAllExchangeDetailByDate($start, $end) {
		$end = (new \DateTime($end))->modify('+1 day')->modify('-1 second')->format('Y-m-d H:i:s');
		$conn = $this->em->getConnection();
		$sql = 'SELECT pcp.user_id, pcp.finish_time, pcp.ciyo_total_fee, pcp.out_trade_no, pcp.item_name FROM  ciyo_payment.payment_ciyocoin_purchased AS pcp WHERE pcp.finish_time IS NOT NULL AND pcp.finish_time >= "'.$start.'" AND pcp.finish_time <= "'.$end.'" ORDER BY pcp.finish_time ASC';
		$result = $conn->query($sql)->fetchAll();
		if (!$result) {
			return array();
		}
		return $result;
	}

	public function getExchangeDetailByAuthorId($authorId, $count=20, $page=1, &$total) {
		$cursor = $count * ($page - 1);
		$conn = $this->em->getConnection();
		$sql = 'SELECT pcp.finish_time, pcp.ciyo_total_fee, pcp.out_trade_no, pcp.item_name FROM  ciyo_payment.payment_ciyocoin_purchased AS pcp WHERE pcp.finish_time IS NOT NULL AND pcp.user_id = '.$authorId.' ORDER BY pcp.finish_time DESC';
		$sqls = $sql.' LIMIT '.$cursor.', '.$count;
		$total = count($conn->query($sql)->fetchAll());
		$results = $conn->query($sqls)->fetchAll();
		return $results;
	}

	public function saveEntity($item){
        $this->em->persist($item);
        $this->em->flush();
    }
}