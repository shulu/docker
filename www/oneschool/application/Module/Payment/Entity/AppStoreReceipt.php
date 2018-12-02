<?php
namespace Lychee\Module\Payment\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="app_store_receipt", schema="ciyo_payment", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="transaction_udx", columns={"transaction_id"})
 * })
 */
class AppStoreReceipt {

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
     * @ORM\Column(name="transaction_id", type="string", length=100)
     */
    public $transactionId;

    /**
     * @var int
     * @ORM\Column(name="product_id", type="string", length=200)
     */
    public $productId;

	/**
	 * @var int
	 * @ORM\Column(name="new_product_id", type="string", length=200, options={"default":""})
	 */
    public $newProductId;

    /**
     * @var \DateTime
     * @ORM\Column(name="time", type="datetime")
     */
    public $time;

    /**
     * @var int
     * @ORM\Column(name="receipt", type="text")
     */
    public $receipt;

    /**
     * @var int
     * @ORM\Column(name="env", type="string", length=20)
     */
    public $env;

	/**
	 * @var bool
	 * @ORM\Column(name="valid", type="boolean", options={"default":false})
	 */
	public $valid = false;

    /**
     * @var string
     * @ORM\Column(name="total_fee", type="decimal", precision=20, scale=2, options={"default":"0"})
     */
    public $totalFee;
}