<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 24/01/2017
 * Time: 7:08 PM
 */

namespace Lychee\Module\Payment\Task;


use Doctrine\ORM\EntityManager;
use Lychee\Bundle\CoreBundle\Entity\User;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Task\Task;
use Lychee\Module\Account\AccountService;
use Lychee\Module\Payment\Entity\PaymentTradeQueryLog;
use Lychee\Module\Payment\PayerType;
use Lychee\Module\Payment\PayType;
use Lychee\Module\Payment\ProductManager;
use Lychee\Module\Payment\ThridParty\Alipay\AlipayRequester;
use Lychee\Module\Payment\ThridParty\Wechat\WechatRequester;
use Lychee\Module\Payment\TransactionService;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class CheckUnfinishedTrade
 * @package Lychee\Module\Payment\Task
 */
class CheckUnfinishedTrade implements Task {

	use ContainerAwareTrait;

	use ModuleAwareTrait;

	/**
	 * @return string
	 */
	public function getName() {
		return 'check-unfinished-trade';
	}

	/**
	 * @return int
	 */
	public function getDefaultInterval() {
		return 300;
	}

	/**
	 *
	 */
	public function run() {
		/** @var TransactionService $transactionService */
		$transactionService = $this->container->get('lychee.module.payment.transaction');
		$deadline = (new \DateTimeImmutable())->sub(new \DateInterval('P1D'));
		$transactions = $transactionService->fetchUnfinishedTransactions(
			$deadline,
			$this->getLastQueryTradeNo(),
			300,
			$nextCursor
		);
		foreach ($transactions as $transaction) {
			printf("TID: %s\n", $transaction['id']);
			$tradeInfo = $this->queryTransaction($transaction);
			if ($tradeInfo) {
				$this->logQuery($transaction['id'], $tradeInfo);
			}
		}
	}

	private function getLastQueryTradeNo() {
		/** @var EntityManager $em */
		$em = $this->container->get('doctrine')->getManager();
		/** @var PaymentTradeQueryLog|null $queryLog */
		$queryLog = $em->getRepository(PaymentTradeQueryLog::class)->findOneBy([], ['id' => 'DESC']);
		if ($queryLog) {
			return $queryLog->transactionId;
		} else {
			return 0;
		}
	}

	private function queryTransaction($transaction) {
		$payer = $transaction['payer'];
		$payType = $transaction['pay_type'];
		$payerType = $transaction['payer_type'];
		$transactionId = $transaction['id'];
		$productId = $transaction['product_id'];
		$payTime = $transaction['end_time'];
		/** @var TransactionService $transactionService */
		$transactionService = $this->container->get('lychee.module.payment.transaction');
		/** @var AccountService $accountService */
		$accountService = $this->container->get('lychee.module.account');
		/** @var ProductManager $productManager */
		$productManager = $this->container->get('lychee.module.payment.product_manager');
		if ('wechat' === $payType) {
			/** @var WechatRequester $wechat */
			$wechat = $this->container->get('lychee.module.payment.wechat');
			if (PayerType::DEVICE == $payerType) {
				$wechat->setAccount(
					$this->container->getParameter('yiciyuan_wechat_key'),
					$this->container->getParameter('yiciyuan_wechat_mch_id'),
					$this->container->getParameter('yiciyuan_wechat_app_id')
				);
			} elseif (PayerType::USER == $payerType) {
				$wechat->setAccount(
					$this->container->getParameter('ciyocon_wechat_key'),
					$this->container->getParameter('ciyocon_wechat_mch_id'),
					$this->container->getParameter('ciyocon_wechat_app_id')
				);
			} else {
				return null;
			}
			$tradeInfo = $wechat->orderquery($transactionId);
			if (is_array($tradeInfo)) {
				if (isset($tradeInfo['trade_state']) && 'SUCCESS' === $tradeInfo['trade_state']) {
					// 查询结果是支付成功，则马上把商品到账
					$product = $productManager->getProductById($productId);
					if (false === $transactionService->isTransactionFinished($transactionId)) {
						/**
						 * 必须先判断订单是否已完成，未完成才进行充值操作，最后再log下订单的finish状态
						 */
						if (PayerType::USER == $payerType) {
							// 多次查询必须确保只到账一次
							$user = $accountService->fetchOne($payer);
							$this->rechargeCiyoCoin($user, $product->ciyoCoin);
						}
						$transactionService->logTransactionFinish($transactionId, new \DateTime($tradeInfo['time_end']), 0);
					}
					$this->purchaseRecorder()->record($payer, $transactionId, $productId, $product->price, $payType, '', new \DateTime($payTime));
				}

				return $tradeInfo;
			}
		} elseif ('alipay' === $payType) {
			if (PayerType::DEVICE == $payerType) {
				/** @var AlipayRequester $alipay */
				$alipay = $this->container->get('lychee.module.payment.alipay.yiciyuan');
			} elseif (PayerType::USER == $payerType) {
				/** @var AlipayRequester $alipay */
				$alipay = $this->container->get('lychee.module.payment.alipay');
			} else {
				return null;
			}
			$tradeInfo = $alipay->queryOrderInfo($transactionId);
			if (is_array($tradeInfo)) {
				if (
					isset($tradeInfo['trade_status']) &&
					('TRADE_SUCCESS' === $tradeInfo['trade_status'] || 'TRADE_FINISHED' === $tradeInfo['trade_status'])
				) {
					/**
					 * 必须先判断订单是否已完成，未完成才进行充值操作，最后再log下订单的finish状态
					 */
					$product = $productManager->getProductById($productId);
					if (false === $transactionService->isTransactionFinished($transactionId)) {
						if (PayerType::USER == $payerType) {
							// 多次查询必须确保只到账一次
							$user = $accountService->fetchOne($payer);
							$this->rechargeCiyoCoin($user, $product->ciyoCoin);
						}
						$transactionService->logTransactionFinish($transactionId, new \DateTime($tradeInfo['send_pay_date']), 0);
					}
					$this->purchaseRecorder()->record($payer, $transactionId, $productId, $product->price, $payType, '', new \DateTime($payTime));
				}

				return $tradeInfo;
			}
		}

		return null;
	}

	/**
	 * @param User $user
	 * @param $coin
	 */
	private function rechargeCiyoCoin(User $user, $coin) {
		$user->ciyoCoin += $coin;
		$this->container->get('doctrine')->getManager()->flush($user);
	}

	private function logQuery($transactionId, $tradeInfo) {
		/** @var EntityManager $em */
		$em = $this->container->get('doctrine')->getManager();
		$queryLog = new PaymentTradeQueryLog();
		$queryLog->transactionId = $transactionId;
		$queryLog->queryTime = new \DateTime();
		$queryLog->tradeInfo = json_encode($tradeInfo);
		$em->persist($queryLog);
		$em->flush();
		printf("[%s]\t%s\n", $transactionId, json_encode($tradeInfo));
	}
}