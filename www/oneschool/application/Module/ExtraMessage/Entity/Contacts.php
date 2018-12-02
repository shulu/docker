<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 16/01/2017
 * Time: 7:54 PM
 */

namespace Lychee\Module\ExtraMessage\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity()
 * @ORM\Table(name="contacts", schema="ciyo_extramessage", indexes={
 *   @ORM\Index(name="device_idx", columns={"device_id"}),
 *   @ORM\Index(name="qq_idx", columns={"qq"}),
 *   @ORM\Index(name="email_idx", columns={"email"}),
 * })
 * Class Contacts
 * @package Lychee\Module\ExtraMessage\Entity
 */
class Contacts {

	/**
	 * @var int
	 * @ORM\Id
	 * @ORM\Column(name="id", type="bigint")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	public $id;

	/**
	 * @var string
	 * @ORM\Column(name="device_id", type="string", length=64)
	 */
	public $deviceId;

	/**
	 * @var string
	 * @ORM\Column(name="qq", type="string", length=20)
	 */
	public $qq;

	/**
	 * @var string
	 * @ORM\Column(name="email", type="string", length=100)
	 */
	public $email;

	/**
	 * @var \DateTime
	 * @ORM\Column(name="create_time", type="datetime")
	 */
	public $createTime;
}