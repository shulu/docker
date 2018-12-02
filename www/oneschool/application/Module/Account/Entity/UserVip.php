<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 19/10/2016
 * Time: 5:02 PM
 */

namespace Lychee\Module\Account\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="user_vip", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="user_udx", columns={"user_id"})
 * })
 */
class UserVip {

	/**
	 * @var int
	 *
	 * @ORM\Column(name="id", type="integer")
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
	 * @var string
	 *
	 * @ORM\Column(name="certification_text", type="string", length=40, nullable=true)
	 */
	public $certificationText;

}