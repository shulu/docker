<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 29/12/2016
 * Time: 5:18 PM
 */

namespace Lychee\Module\Payment;


use Doctrine\ORM\EntityManagerInterface;
use Lychee\Bundle\ApiBundle\Error\ErrorsException;
use Lychee\Bundle\ApiBundle\Error\LiveError;
use Lychee\Bundle\CoreBundle\Entity\User;
use Lychee\Module\Account\AccountService;
use Lychee\Module\Payment\Entity\PaymentCiyoCoinPurchased;

/**
 * Class CiyoCoinPurchaseRecorder
 * @package Lychee\Module\Payment
 */
class CiyoCoinPurchaseRecorder {



	/**
	 * @var EntityManagerInterface
	 *
	 */
	private $em;

	/**
	 * @var AccountService
	 */
	private $accountService;

	/**
	 * CiyoCoinPurchaseRecorder constructor.
	 *
	 * @param $doctrine
	 * @param AccountService $accountService
	 */
	public function __construct($doctrine, AccountService $accountService) {
		$this->em = $doctrine->getManager();
		$this->accountService = $accountService;
	}

	/**
	 * @param $orderId
	 *
	 * @return object
	 */
	public function fetchTransactionByOutTradeNo($orderId) {
		return $this->em->getRepository(PaymentCiyoCoinPurchased::class)->findOneBy([
			'outTradeNo' => $orderId
		]);
	}

	/**
	 * @param $outTradeNo
	 * @param $userId
	 * @param $ciyoTotalFee
	 * @param $totalFee
	 * @param $item
	 * @param $itemName
	 * @param $itemFee
	 *
	 * @return PaymentCiyoCoinPurchased
	 */
	public function createCiyoCoinTransaction($outTradeNo, $userId, $ciyoTotalFee, $totalFee, $item, $itemName, $itemFee) {
		$ciyoCoinPurchased = new PaymentCiyoCoinPurchased();
		$ciyoCoinPurchased->outTradeNo = $outTradeNo;
		$ciyoCoinPurchased->userId = $userId;
		$ciyoCoinPurchased->createTime = new \DateTime();
		$ciyoCoinPurchased->ciyoTotalFee = $ciyoTotalFee;
		$ciyoCoinPurchased->totalFee = $totalFee;
		$ciyoCoinPurchased->item = $item;
		$ciyoCoinPurchased->itemName = $itemName;
		$ciyoCoinPurchased->itemFee = $itemFee;

		$this->em->persist($ciyoCoinPurchased);
		$this->em->flush();

		return $ciyoCoinPurchased;
	}

	/**
	 * @param $id
	 *
	 * @return object
	 */
	public function getTransactionById($id) {
		return $this->em->getRepository(PaymentCiyoCoinPurchased::class)->find($id);
	}

	/**
	 * @param PaymentCiyoCoinPurchased $transaction
	 *
	 * @return \DateTime
	 */
	public function isTransactionFinished(PaymentCiyoCoinPurchased $transaction) {
		return null !== $transaction->finishTime;
	}

	/**
	 * @param PaymentCiyoCoinPurchased $transaction
	 *
	 * @return PaymentCiyoCoinPurchased
	 */
	public function finishCiyoCoinTransaction(PaymentCiyoCoinPurchased $transaction) {
		$transaction->finishTime = new \DateTime();
		$this->em->flush();

		return $transaction;
	}

	/**
	 * @param $userOrId
	 * @param $fee
	 *
	 * @return bool|User|null
	 * @throws ErrorsException
	 */
	public function deductCiyoCoin($userOrId, $fee) {
		if (is_numeric($userOrId)) {
			$user = $this->accountService->fetchOne($userOrId);
		} else {
			$user = $userOrId;
		}
		if ($user instanceof User) {
			if ($user->ciyoCoin < $fee) {
				throw new ErrorsException(LiveError::InsufficientBalance());
			}
			$user->ciyoCoin -= $fee;
			$this->em->flush();

			return $user;
		}

		return false;
	}

	/**
	 * @param $outTradeNo
	 *
	 * @return object
	 */
	public function getTransactionByOutTradeNo($outTradeNo) {
		return $this->em->getRepository(PaymentCiyoCoinPurchased::class)->findOneBy([
			'outTradeNo' => $outTradeNo
		]);
	}
}