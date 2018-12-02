<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 2017/10/19
 * Time: 下午2:40
 */

namespace Lychee\Module\ExtraMessage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="em_dmzj_auth", schema="ciyo_extramessage")
 * Class Contacts
 * @package Lychee\Module\ExtraMessage\Entity
 */

class EMDmzjAuth
{
	/**
	 * @var int
	 *
	 * @ORM\Column(name="open_id", type="string", length=64)
	 * @ORM\Id
	 */
	public $openId;

	/**
	 * @var int
	 *
	 * @ORM\Column(name="user_id", type="bigint")
	 */
	public $userId;

}