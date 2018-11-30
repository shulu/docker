<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 18/11/2016
 * Time: 1:01 PM
 */

namespace Lychee\Module\Live\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class UserLive
 * @package Lychee\Module\Live\Entity
 * @ORM\Entity()
 * @ORM\Table(name="live_pizus_user", schema="ciyo_live", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="pizus_udx", columns={"pizus_id"})
 * })
 */
class LivePizusUser {

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="user_id", type="bigint")
	 * @ORM\Id
	 */
	public $userId;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="pizus_id", type="string", length=100)
	 */
	public $pizusId;
}