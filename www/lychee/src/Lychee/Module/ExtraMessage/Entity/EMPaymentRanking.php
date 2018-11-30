<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 2017/9/14
 * Time: 下午12:01
 */

namespace Lychee\Module\ExtraMessage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EMPaymentRanking
 *
 * @ORM\Entity
 * @ORM\Table(name="em_payment_ranking", schema="ciyo_extramessage",uniqueConstraints={
 *   @ORM\UniqueConstraint(name="user_product_udx", columns={"user_id", "story_id"})
 * })
 *
 * @package Lychee\Module\ExtraMessage\Entity
 */

class EMPaymentRanking {

	/**
	 * @var int
	 *
	 * @ORM\Column(name="id", type="bigint")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	public $id;

	/**
	 * @var int
	 *
	 * @ORM\Column(name="user_id", type="bigint")
	 */
	public $userId;

	/**
	 * @var int
	 *
	 * @ORM\Column(name="story_id", type="bigint")
	 */
	public $storyId;

	/**
	 * @var double
	 * @ORM\Column(name="total_fee", type="decimal", precision=20, scale=2, options={"default":"0"})
	 */
	public $totalFee;

}