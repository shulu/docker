<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 2017/4/18
 * Time: 下午2:33
 */

namespace Lychee\Module\ExtraMessage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EMGameRecord
 *
 * @ORM\Entity
 * @ORM\Table(name="em_game_record", schema="ciyo_extramessage")
 *
 * @package Lychee\Module\ExtraMessage\Entity
 */

class EMGameRecord {

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
	 * @ORM\Column(name="path", type="string", length=255)
	 */
	public $path;
}