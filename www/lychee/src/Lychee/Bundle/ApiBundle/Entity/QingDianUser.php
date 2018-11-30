<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 2017/9/12
 * Time: 上午11:49
 */

namespace Lychee\Bundle\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="qingdian_user")
 */

class QingDianUser {

	/**
	 * @var int
	 * @ORM\Id
	 * @ORM\Column(name="user_id", type="bigint")
	 */
	public $userId;

	/**
	 * @var \DateTime
	 * @ORM\Column(name="request_time", type="datetime")
	 */
	public $time;
}