<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 15/03/2017
 * Time: 2:52 PM
 */

namespace Lychee\Module\Live\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class LiveRecord
 * @package Lychee\Module\Live\Entity
 * @ORM\Entity()
 * @ORM\Table(name="live_record", schema="ciyo_live")
 */
class LiveRecord {

	/**
	 * @var integer
	 * @ORM\Id
	 * @ORM\GeneratedValue("AUTO")
	 * @ORM\Column(name="id", type="bigint")
	 */
	public $id;

	/**
	 * @var integer
	 * @ORM\Column(name="user_id", type="bigint")
	 */
	public $userId;

	/**
	 * @var \DateTime
	 * @ORM\Column(name="start_time", type="datetime")
	 */
	public $startTime;

	/**
	 * @var integer
	 * @ORM\Column(name="duration", type="integer")
	 */
	public $duration;
}