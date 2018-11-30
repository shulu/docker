<?php
namespace Lychee\Module\Payment\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="payment_product_purchased", schema="ciyo_payment", indexes={
 *   @ORM\Index(name="payer_idx", columns={"payer"}),
 *   @ORM\Index(name="product_idx", columns={"product_id"})
 * }, uniqueConstraints={
 *   @ORM\UniqueConstraint(name="device_transaction_udx", columns={"payer", "transaction_id", "appstore_transaction_id", "promotion_code_id"})
 * })
 */
class PaymentProductPurchased {

    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     * @ORM\Column(name="payer", type="string", length=64)
     */
    public $payer;

    /**
     * @var int
     * @ORM\Column(name="transaction_id", type="bigint", options={"default":"0"})
     */
    public $transactionId = 0;

    /**
     * @var int
     * @ORM\Column(name="product_id", type="bigint")
     */
    public $productId;

	/**
	 * @var string
	 * @ORM\Column(name="appstore_transaction_id", type="string", length=100, options={"default":""})
	 */
	public $appStoreTransactionId = '';

	/**
	 * @var int
	 * @ORM\Column(name="promotion_code_id", type="bigint", options={"default": "0"})
	 */
	public $promotionCodeId;

	/**
	 * @var string
	 * @ORM\Column(name="total_fee", type="decimal", precision=20, scale=2, options={"default":"0"})
	 */
	public $totalFee;

	/**
	 * @var \DateTime
	 * @ORM\Column(name="purchase_time", type="datetime")
	 */
	public $purchaseTime;


	/**
	 * @var string
	 * @ORM\Column(name="pay_type", type="string", length=20, options={"default":""})
	 */
	public $payType = '';
}