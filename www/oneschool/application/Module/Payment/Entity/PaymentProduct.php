<?php
namespace Lychee\Module\Payment\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="payment_products", schema="ciyo_payment", indexes={
 *   @ORM\Index(name="appstore_idx", columns={"app_store_id"}),
 *     @ORM\Index(name="appid_idx", columns={"app_id"})
 * })
 */
class PaymentProduct {

	const CIYO_APP_ID = 10001;
	const EXTRAMESSAGE_APP_ID = 10002;

    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint")
     *
     *
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     * @ORM\Column(name="name", type="string", length=200)
     */
    public $name;

    /**
     * @var string
     * @ORM\Column(name="desc", type="string", length=400)
     */
    public $desc;

    /**
     * @var string
     * @ORM\Column(name="price", type="decimal", precision=20, scale=2)
     */
    public $price;

	/**
	 * @var integer
	 * @ORM\Column(name="ciyo_coin", type="integer", options={"default": 0})
	 */
	public $ciyoCoin = 0;

	/**
	 * @var string
	 * @ORM\Column(name="app_store_id", type="string", length=100, options={"default": ""})
	 */
	public $appStoreId;

	/**
	 * @var string
	 * @ORM\Column(name="app_id", type="string", length=20, options={"default":"10001"})
	 */
	public $appId;

	/**
	 * @var int
	 * @ORM\Column(name="status", type="integer", options={"default":1})
	 */
	public $status;
}