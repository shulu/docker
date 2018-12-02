<?php
namespace Lychee\Module\Payment\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="payment_transaction", schema="ciyo_payment")
 */
class PaymentTransaction {

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
	 * @var integer
	 * @ORM\Column(name="payer_type", type="smallint", options={"default": 1})
	 */
	public $payerType;

    /**
     * @var string
     * @ORM\Column(name="client_ip", type="string", length=200)
     */
    public $clientIp;

    /**
     * @var int
     * @ORM\Column(name="product_id", type="bigint")
     */
    public $productId;

    /**
     * @var string
     * @ORM\Column(name="total_fee", type="decimal", precision=20, scale=2)
     */
    public $totalFee;

    /**
     * @var \DateTime
     * @ORM\Column(name="start_time", type="datetime")
     */
    public $startTime;

    /**
     * @var \DateTime
     * @ORM\Column(name="end_time", type="datetime")
     */
    public $endTime;

    /**
     * @var string
     * @ORM\Column(name="pay_type", type="string", length=20)
     */
    public $payType;

}