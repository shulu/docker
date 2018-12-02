<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 06/02/2017
 * Time: 4:48 PM
 */

namespace Lychee\Module\Payment\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="iap_check_refund_log", schema="ciyo_payment")
 */
class PaymentIAPCheckRefundLog {

	/**
	 * @var int
	 * @ORM\Id
	 * @ORM\Column(name="id", type="bigint")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	public $id;

	/**
	 * @var int
	 * @ORM\Column(name="transaction_id", type="string", length=100)
	 */
	public $transactionId;

	/**
	 * @var string
	 * @ORM\Column(name="payer", type="string", length=64)
	 */
	public $payer;

	/**
	 * @var \DateTime
	 * @ORM\Column(name="check_time", type="datetime")
	 */
	public $checkTime;

	/**
	 * @var \DateTime
	 * @ORM\Column(name="cancellation_date", type="datetime", nullable=true)
	 */
	public $cancellationDate;

	/**
	 * @var bool
	 * @ORM\Column(name="is_refund", type="boolean", options={"default":false})
	 */
	public $isRefund = false;
}