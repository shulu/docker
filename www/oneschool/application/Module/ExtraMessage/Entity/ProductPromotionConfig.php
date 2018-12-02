<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 2017/9/19
 * Time: 下午2:06
 */

namespace Lychee\Module\ExtraMessage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductPromotionCode
 *
 * @ORM\Entity
 * @ORM\Table(name="product_promotion_config", schema="ciyo_extramessage", indexes={
 *   @ORM\Index(name="product_idx", columns={"product_id"}),
 *   @ORM\Index(name="story_idx", columns={"story_id"})
 * })
 *
 * @package Lychee\Module\ExtraMessage\Entity
 */

class ProductPromotionConfig {

	/**
	 * @var int
	 *
	 * @ORM\Column(name="product_id", type="bigint")
	 * @ORM\Id
	 */
	public $productId;

	/**
	 * @var int
	 *
	 * @ORM\Column(name="code_count", type="integer")
	 */
	public $codeCount;

	/**
	 * @var int
	 *
	 * @ORM\Column(name="story_id", type="bigint")
	 */
	public $storyId;
}