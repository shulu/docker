<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 2017/8/21
 * Time: 上午10:39
 */

namespace Lychee\Module\ExtraMessage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EMDiary
 *
 * @ORM\Entity
 * @ORM\Table(name="em_diary", schema="ciyo_extramessage")
 *
 * @package Lychee\Module\ExtraMessage\Entity
 */

class EMDiary {

	/**
	 * @var int
	 *
	 * @ORM\Column(name="user_id", type="bigint")
	 * @ORM\Id
	 */
	public $userId;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="record", type="text")
	 */
	public $record;
}