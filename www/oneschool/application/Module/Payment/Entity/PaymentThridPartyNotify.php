<?php
namespace Lychee\Module\Payment\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="payment_thridparty_notify", schema="ciyo_payment")
 */
class PaymentThridPartyNotify {

    /**
     * @var int
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     * @ORM\Column(name="type", type="string", length=20)
     */
    public $type;

    /**
     * @var \DateTime
     * @ORM\Column(name="receive_time", type="datetime")
     */
    public $receiveTime;

    /**
     * @var string
     * @ORM\Column(name="body", type="string", length=4000)
     */
    public $body;

}