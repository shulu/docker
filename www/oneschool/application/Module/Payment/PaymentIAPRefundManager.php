<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 06/02/2017
 * Time: 6:25 PM
 */

namespace Lychee\Module\Payment;


use Doctrine\ORM\EntityManager;
use Lychee\Module\Payment\Entity\AppStoreReceipt;
use Lychee\Module\Payment\Entity\PaymentIAPCheckRefundLog;

/**
 * Class PaymentIAPRefundManager
 * @package Lychee\Module\Payment
 */
class PaymentIAPRefundManager {

	/**
	 * @var EntityManager
	 */
	private $em;

	/**
	 * @var \Doctrine\ORM\EntityRepository
	 */
	private $refundLogRepo;

	/**
	 * @var \Doctrine\ORM\EntityRepository
	 */
	private $appStoreReceiptRepo;

	/**
	 * PaymentIAPRefundManager constructor.
	 *
	 * @param $doctrine
	 */
	public function __construct($doctrine) {
		$this->em = $doctrine->getManager();
		$this->refundLogRepo = $this->em->getRepository(PaymentIAPCheckRefundLog::class);
		$this->appStoreReceiptRepo = $this->em->getRepository(AppStoreReceipt::class);
	}

	/**
	 * @return null|PaymentIAPCheckRefundLog
	 */
	public function getLastLog() {
		return $this->refundLogRepo->findOneBy([], [
			'id' => 'DESC'
		]);
	}

	/**
	 * @param $minTransactionId
	 * @param $count
	 *
	 * @return array
	 */
	public function fetchAppStoreReceipts($minTransactionId, $count) {
		$query = $this->appStoreReceiptRepo->createQueryBuilder('r')
			->where('r.id>:minId')
			->andWhere('r.valid=1')
			->setParameters(['minId' => $minTransactionId])
			->orderBy('r.id')
			->setMaxResults($count)
			->getQuery();

		$result = $query->getResult();
		if (!$result) {
			$result = [];
		}

		return $result;
	}

	public function logRefund($transactionId, $cancellationDate, $isRefund = false) {
		$transaction = $this->getAppStoreTransaction($transactionId);
		if (!$transaction) {
			return false;
		}
		$log = new PaymentIAPCheckRefundLog();
		$log->transactionId = $transactionId;
		$log->payer = $transaction->payer;
		$log->cancellationDate = $cancellationDate;
		$log->checkTime = new \DateTime();
		$log->isRefund = $isRefund;

		$this->em->persist($log);
		$this->em->flush();
	}

	/**
	 * @param $transactionId
	 *
	 * @return null|AppStoreReceipt
	 */
	private function getAppStoreTransaction($transactionId) {
		return $this->appStoreReceiptRepo->findOneBy([
			'transactionId' => $transactionId
		]);
	}
}