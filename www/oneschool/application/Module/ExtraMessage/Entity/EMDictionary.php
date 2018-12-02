<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 2017/8/21
 * Time: 上午11:01
 */

namespace Lychee\Module\ExtraMessage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EMDictionary
 *
 * @ORM\Entity
 * @ORM\Table(name="em_dictionary", schema="ciyo_extramessage")
 *
 * @package Lychee\Module\ExtraMessage\Entity
 */

class EMDictionary {

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