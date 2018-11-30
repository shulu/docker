<?php
namespace Lychee\Module\Payment\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="payment_transaction_request", schema="ciyo_payment")
 */
class PaymentTransactionRequest {

    /**
     * @var int
     * @ORM\Column(name="transaction_id", type="bigint")
     * @ORM\Id
     */
    public $transactionId;

    /**
     * @var string
     * @ORM\Column(name="request_params", type="string", length=4000)
     */
    public $requestParams;

}