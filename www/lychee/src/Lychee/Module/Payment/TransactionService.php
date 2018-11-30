<?php
namespace Lychee\Module\Payment;

use Lychee\Module\Payment\Entity\PaymentTransaction;
use Lychee\Module\Payment\Entity\PaymentTransactionFinish;
use Lychee\Module\Payment\Entity\PaymentTransactionRequest;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManagerInterface;

class TransactionService {

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
     * @param integer $payerType
     * @param string $payer
     * @param int $productId
     * @param string $totalFee
     * @param string $clientIp
     * @param string $payType
     * @return PaymentTransaction
     */
    public function createTransaction($payerType, $payer, $productId, $totalFee, $clientIp, $payType) {
        $t            = new PaymentTransaction();
	    $t->payerType = $payerType;
        $t->payer  = $payer;
        $t->productId = $productId;
        $t->clientIp  = $clientIp;
        $t->totalFee  = $totalFee;
        $t->payType   = $payType;
        $now          = new \DateTimeImmutable();
        $t->startTime = $now;
        $t->endTime   = $now->add(new \DateInterval('PT10M'));
        $this->em->persist($t);
        $this->em->flush();
        return $t;
    }

    /**
     * @param int $id
     * @return PaymentTransaction|null
     */
    public function getTransactionById($id) {
        return $this->em->find(PaymentTransaction::class, $id);
    }

    /**
     * @param int $transactionId
     * @param string $requestParams
     */
    public function logTransactionRequest($transactionId, $requestParams) {
        $sql = 'INSERT INTO ciyo_payment.payment_transaction_request(transaction_id, request_params) VALUES(?, ?) ON DUPLICATE KEY UPDATE request_params = request_params';
        $this->em->getConnection()->executeUpdate($sql, array($transactionId, $requestParams), array(\PDO::PARAM_INT, \PDO::PARAM_STR));
    }

    /**
     * @param int $transactionId
     * @param \DateTime $payTime
	 * @param int $notifyId
	 */
    public function logTransactionFinish($transactionId, $payTime, $notifyId) {
        $dtFormat = $this->em->getConnection()->getDatabasePlatform()->getDateTimeFormatString();
        $nowStr = (new \DateTime())->format($dtFormat);
        $payTimeStr = $payTime->format($dtFormat);

        $sql = 'INSERT INTO ciyo_payment.payment_transaction_finish(transaction_id, notify_receive_time, pay_time, notify_id)
 				VALUES(?, ?, ?, ?) ON DUPLICATE KEY UPDATE notify_id = ?, notify_receive_time = ?';
        $this->em->getConnection()->executeUpdate(
        	$sql,
	        array($transactionId, $nowStr, $payTimeStr, $notifyId, $notifyId, $nowStr),
	        array(\PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_STR)
        );
    }

    /**
     * @param string $type
     * @param string $body
     * @return int
     */
    public function logThridPartyNotify($type, $body) {
        $dtFormat = $this->em->getConnection()->getDatabasePlatform()->getDateTimeFormatString();
        $nowStr = (new \DateTime())->format($dtFormat);
        $sql = 'INSERT INTO ciyo_payment.payment_thridparty_notify(receive_time, type, body) VALUES(?, ?, ?)';
        $this->em->getConnection()->executeUpdate($sql, array($nowStr, $type, $body), array(\PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR));
        return $this->em->getConnection()->lastInsertId();
    }

	/**
	 * @param $transactionId
	 *
	 * @return bool
	 */
	public function isTransactionFinished($transactionId) {
    	$finishedTransaction = $this->em->getRepository(PaymentTransactionFinish::class)->find($transactionId);
    	if ($finishedTransaction) {
    		return true;
	    }
	    return false;
    }

	/**
	 * @param \DateTimeImmutable $deadline
	 * @param int $cursor
	 * @param int $count
	 * @param null $nextCursor
	 *
	 * @return array
	 */
	public function fetchUnfinishedTransactions(\DateTimeImmutable $deadline, $cursor = 0, $count = 100, &$nextCursor = null) {
		$conn = $this->em->getConnection();
		$stmt = $conn->prepare("SELECT t.* FROM ciyo_payment.payment_transaction AS t
				LEFT OUTER JOIN ciyo_payment.payment_transaction_finish AS f ON f.transaction_id=t.id
				WHERE t.id>:cursor AND t.start_time < :deadline AND f.transaction_id IS NULL
				ORDER BY t.id ASC
				LIMIT $count");
		$stmt->bindValue(':cursor', $cursor);
		$stmt->bindValue(':deadline', $deadline->format('Y-m-d H:i:s'));
		if ($stmt->execute()) {
			$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			if (count($result) < $count) {
				$nextCursor = 0;
			} else {
				$lastRow = end($result);
				$nextCursor = $lastRow['id'];
				reset($result);
			}

			return $result;
		}

		return [];
	}
}