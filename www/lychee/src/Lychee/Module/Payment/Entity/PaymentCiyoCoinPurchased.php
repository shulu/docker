<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 29/12/2016
 * Time: 2:52 PM
 */

namespace Lychee\Module\Payment\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="payment_ciyocoin_purchased", schema="ciyo_payment", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="out_trade_udx", columns={"out_trade_no"})
 * })
 *
 * Class PaymentCiyoCoinPurchased
 * @package Lychee\Module\Payment\Entity
 */
class PaymentCiyoCoinPurchased {

	const ITEM_PIZUS_GIFT = 1;

	const ITEM_INKE_COIN = 2;

	/**
	 * @var int
	 * @ORM\Id
	 * @ORM\Column(name="id", type="bigint")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	public $id;

	/**
	 * @var string
	 * @ORM\Column(name="out_trade_no", type="string", length=100)
	 */
	public $outTradeNo;

	/**
	 * @var int
	 * @ORM\Column(name="user_id", type="bigint")
	 */
	public $userId;

	/**
	 * @var \DateTime
	 * @ORM\Column(name="create_time", type="datetime")
	 */
	public $createTime;

	/**
	 * @var \DateTime
	 * @ORM\Column(name="finish_time", type="datetime", nullable=true)
	 */
	public $finishTime;

	/**
	 * @var string
	 * @ORM\Column(name="ciyo_total_fee", type="decimal", precision=20, scale=2)
	 */
	public $ciyoTotalFee;

	/**
	 * @var string
	 * @ORM\Column(name="total_fee", type="decimal", precision=20, scale=2)
	 */
	public $totalFee;

	/**
	 * @var int
	 * @ORM\Column(name="item", type="smallint")
	 */
	public $item;

	/**
	 * @var string
	 * @ORM\Column(name="item_name", type="string", length=100)
	 */
	public $itemName;

	/**
	 * @var string
	 * @ORM\Column(name="item_fee", type="decimal", precision=20, scale=2)
	 */
	public $itemFee;
}