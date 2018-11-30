<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 19/01/2017
 * Time: 2:21 PM
 */

namespace Lychee\Module\Payment\Task;


use Doctrine\ORM\EntityManager;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Database\DoctrineUtility;
use Lychee\Component\Task\Task;
use Lychee\Module\Payment\Entity\AppStoreReceipt;
use Lychee\Module\Payment\Entity\PaymentIAPCheckRefundLog;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class CheckIAPCancel
 * @package Lychee\Module\Payment\Task
 */
class CheckIAPCancel implements Task {

	use ContainerAwareTrait;

	use ModuleAwareTrait;

	/**
	 * @return string
	 */
	public function getName() {
		return 'check-iap-cancel';
	}

	/**
	 * @return int
	 */
	public function getDefaultInterval() {
		return 3600;
	}

	public function run() {
		/** @var EntityManager $em */
		$em = $this->container->get('doctrine')->getManager();
		/** @var Logger $logger */
		$logger = $this->container->get('monolog.logger.thirdparty_invoke');
		$today = new \DateTimeImmutable('today');

		// Check if log exist
		/**
		 * @var PaymentIAPCheckRefundLog|null $lastLog
		 */
		$lastLog = $this->iapRefundManager()->getLastLog();
		if ($lastLog) {
			if ($lastLog->checkTime > $today) {
				$minTransactionId = $lastLog->transactionId;
			} else {
				$startDate = $today->sub(new \DateInterval('P15D'));
				$minTransactionId = DoctrineUtility::getMinIdWithTime(
					$em,
					AppStoreReceipt::class,
					'id',
					'time',
					$startDate
				);
			}
		} else {
			$minTransactionId = 0;
		}

		$count = 100; // 每次任务检查100张订单
		/** @var $receiptRow AppStoreReceipt */
		foreach ($this->iapRefundManager()->fetchAppStoreReceipts($minTransactionId, $count) as $receiptRow) {
			$receiptUrl = $receiptRow->env === 'Sandbox' ?
				'https://sandbox.itunes.apple.com/verifyReceipt' : 'https://buy.itunes.apple.com/verifyReceipt';

			$ch = curl_init($receiptUrl);
			$dataJson = json_encode([
				'receipt-data' => $receiptRow->receipt,
			]);
			curl_setopt(
				$ch,
				CURLOPT_HTTPHEADER,
				array('Content-Type: application/json','Content-Length: ' . strlen($dataJson))
			);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POSTFIELDS, $dataJson);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$execStartTime = time();
			$response  = curl_exec($ch);
			$execFinishTime = time();
			$responseArr = json_decode($response, true);
			do {
				if (is_array($responseArr) && !empty($responseArr)) {
					if (isset($responseArr['status']) && $responseArr['status'] == 0) {
						if (isset($responseArr['receipt']) && isset($responseArr['receipt']['in_app'])) {
							$inAppField = $responseArr['receipt']['in_app'];
							if (is_array($inAppField)) {
								$isRefund = false;
								foreach ($inAppField as $inAppElem) {
									if (isset($inAppElem['cancellation_date'])) {
										$cancellationDate = $inAppElem['cancellation_date'];
										$transactionId = isset($inAppElem['transaction_id'])? $inAppElem['transaction_id'] : '';
										$productId = isset($inAppElem['product_id'])? $inAppElem['product_id'] : '';
										if ($transactionId == $receiptRow->transactionId && $receiptRow->productId == $productId) {
											$this->iapRefundManager()->logRefund($transactionId, new \DateTime($cancellationDate), true);
											$isRefund = true;
											// 扣除次元币
											$product = $this->productManager()->getProductByAppStoreId($productId);
											$user = $this->account()->fetchOne($receiptRow->payer);
											if ($product && $user) {
												$logger->info(sprintf(
													"User: %s\tProductID: %s\tBalance: %s\t Product: %s\n",
													$user->id,
													$product->appStoreId,
													$user->ciyoCoin,
													$product->ciyoCoin
												));
												$user->ciyoCoin -= $product->ciyoCoin;
												if ($user->ciyoCoin < 0) {
													$user->ciyoCoin = 0;
												}
												$em->flush($user);
											}
										}
									}
								}
								if (true === $isRefund) {
									break;
								}
							}
						}
					}
				}
				$this->iapRefundManager()->logRefund($receiptRow->transactionId, null);
			} while(0);
			curl_close($ch);

			printf("ID: %s\tTID: %s\t(%s)\n", $receiptRow->id, $receiptRow->transactionId, $execFinishTime - $execStartTime);
		}
	}
}