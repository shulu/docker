<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 2017/12/27
 * Time: 下午12:24
 */

namespace Lychee\Module\ExtraMessage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EMGameRecord
 *
 * @ORM\Entity
 * @ORM\Table(name="em_promotion_code_vendor_record", schema="ciyo_extramessage")
 *
 * @package Lychee\Module\ExtraMessage\Entity
 */
class EMPromotionCodeVendorRecord {

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="id", type="bigint")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	public $id;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="vendor", type="string", length=20)
	 */
	public $vendor;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="product_id", type="bigint")
	 */
	public $productId;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="count", type="integer")
	 */
	public $count;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="code_expire_time", type="datetime", nullable=true)
	 */
	public $codeExpireTime;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="create_time", type="datetime")
	 */
	public $createTime;
}