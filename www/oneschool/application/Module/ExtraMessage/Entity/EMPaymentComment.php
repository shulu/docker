<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 2017/8/21
 * Time: 下午2:24
 */

namespace Lychee\Module\ExtraMessage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EMPaymentComment
 *
 * @ORM\Entity
 * @ORM\Table(name="em_payment_comment", schema="ciyo_extramessage")
 *
 * @package Lychee\Module\ExtraMessage\Entity
 */

class EMPaymentComment {

	/**
	 * @var int
	 *
	 * @ORM\Column(name="product_purchase_id", type="bigint")
	 * @ORM\Id
	 */
	public $productPurchaseId;

	/**
	 * @var int
	 *
	 * @ORM\Column(name="user_id", type="bigint")
	 * @ORM\Id
	 */
	public $userId;

	/**
	 * @var int
	 *
	 * @ORM\Column(name="product_id", type="bigint")
	 */
	public $productId;

	/**
	 * @var int
	 *
	 * @ORM\Column(name="story_id", type="bigint")
	 */
	public $storyId;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="comment", type="string", length=255, nullable=true)
	 *
	 */
	public $comment;

	/**
	 * @var \DateTime
	 * @ORM\Column(name="comment_time", type="datetime")
	 */
	public $commentTime;
}