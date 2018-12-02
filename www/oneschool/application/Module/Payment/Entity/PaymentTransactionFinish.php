<?php
namespace Lychee\Module\Payment\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="payment_transaction_finish", schema="ciyo_payment")
 */
class PaymentTransactionFinish {

    /**
     * @var int
     * @ORM\Column(name="transaction_id", type="bigint")
     * @ORM\Id
     */
    public $transactionId;

    /**
     * @var \DateTime
     * @ORM\Column(name="notify_receive_time", type="datetime")
     */
    public $notifyReceiveTime;

    /**
     * @var \DateTime
     * @ORM\Column(name="pay_time", type="datetime")
     */
    public $payTime;

    /**
     * @var string
     * @ORM\Column(name="notify_id", type="bigint")
     */
    public $notifyId;

}