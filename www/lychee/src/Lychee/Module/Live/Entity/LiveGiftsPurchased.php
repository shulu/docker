<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 24/11/2016
 * Time: 2:48 PM
 */

namespace Lychee\Module\Live\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class LiveGiftsPurchased
 * @package Lychee\Module\Live\Entity
 * @ORM\Entity()
 * @ORM\Table(name="live_gifts_purchased", schema="ciyo_live", indexes={
 *   @ORM\Index(name="user_idx", columns={"user_id"}),
 *   @ORM\Index(name="pizus_idx", columns={"pizus_id"})
 * }, uniqueConstraints={
 *   @ORM\UniqueConstraint(name="tid_udx", columns={"transaction_id"})
 * })
 */
class LiveGiftsPurchased {

	/**
	 * @var integer
	 * @ORM\Id
	 * @ORM\Column(name="id", type="bigint")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	public $id;

	/**
	 * @var string
	 * @ORM\Column(name="transaction_id", type="string", length=30)
	 */
	public $transactionId;

	/**
	 * @var integer
	 * @ORM\Column(name="user_id", type="bigint")
	 */
	public $userId;

	/**
	 * @var string
	 * @ORM\Column(name="pizus_id", type="string", length=100)
	 */
	public $pizusId;

	/**
	 * @var string
	 * @ORM\Column(name="gift_json", type="string", length=1000)
	 */
	public $giftJson;

	/**
	 * @var double
	 * @ORM\Column(name="unit_price", type="decimal", precision=20, scale=2)
	 */
	public $unitPrice;

	/**
	 * @var integer
	 * @ORM\Column(name="gift_count", type="smallint")
	 */
	public $giftCount;

	/**
	 * @var double
	 * @ORM\Column(name="price", type="decimal", precision=20, scale=2)
	 */
	public $price;

	/**
	 * @var string
	 * @ORM\Column(name="purchased_time", type="datetime")
	 */
	public $purchasedTime;
}