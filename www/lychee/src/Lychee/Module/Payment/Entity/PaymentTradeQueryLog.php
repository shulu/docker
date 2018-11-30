<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 24/01/2017
 * Time: 8:34 PM
 */

namespace Lychee\Module\Payment\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="payment_trade_query_log", schema="ciyo_payment", indexes={
 *     @ORM\Index(name="transaction_idx", columns={"transaction_id"})
 * })
 */
class PaymentTradeQueryLog {

	/**
	 * @var int
	 * @ORM\Id
	 * @ORM\Column(name="id", type="bigint")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	public $id;

	/**
	 * @var int
	 * @ORM\Column(name="transaction_id", type="bigint")
	 */
	public $transactionId;

	/**
	 * @var \DateTime
	 * @ORM\Column(name="query_time", type="datetime")
	 */
	public $queryTime;

	/**
	 * @var string
	 * @ORM\Column(name="trade_info", type="text", nullable=true)
	 */
	public $tradeInfo;
}