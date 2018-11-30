<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 2017/4/20
 * Time: 下午2:10
 */

namespace Lychee\Module\ExtraMessage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EMGameRecord
 *
 * @ORM\Entity
 * @ORM\Table(name="em_promotion_code", schema="ciyo_extramessage", indexes={
 *   @ORM\Index(name="code_idx", columns={"code"}),
 *   @ORM\Index(name="user_idx", columns={"user_id"})
 * })
 *
 * @package Lychee\Module\ExtraMessage\Entity
 */

class EMPromotionCode {

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="id", type="bigint")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	public $id;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="user_id", type="bigint")
	 */
	public $userId;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="product_id", type="bigint")
	 */
	public $productId;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="transation_id", type="bigint", nullable=true)
	 */
	public $transationId;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="app_store_transation_id", type="bigint", nullable=true)
	 */
	public $appStoreTransationId;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="code", type="string", length=20, unique=true)
	 */
	public $code;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="create_time", type="datetime")
	 */
	public $createTime;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="receiver_id", type="bigint", nullable=true)
	 */
	public $receiverId;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="receive_time", type="datetime", nullable=true)
	 */
	public $receiveTime;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="code_expire_time", type="datetime", nullable=true)
	 */
	public $codeExpireTime;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="vendor", type="string", length=20, options={"default":""})
	 */
	public $vendor = '';

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="vendor_record_id", type="bigint", options={"default":0})
	 */
	public $vendorRecordId = 0;

	/**
	 * @var boolean
	 *
	 * @ORM\Column(name="pre_gen", type="smallint", options={"default":0})
	 */
	public $preGen = false;

}