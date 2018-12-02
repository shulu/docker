<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 2017/8/22
 * Time: 下午1:45
 */

namespace Lychee\Module\Caitu\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CaituRecord
 *
 * @ORM\Entity
 * @ORM\Table(name="caitu_record", indexes={
 *   @ORM\Index(name="phone_idx", columns={"phone"})
 * }, uniqueConstraints={
 *   @ORM\UniqueConstraint(name="phone_udx", columns={"phone"})
 * })
 *
 * @package Lychee\Module\Caitu\Entity
 */

class CaituRecord {

	/**
	 * @var int
	 *
	 * @ORM\Column(name="id", type="bigint")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	public $id;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="phone", type="string", length=20)
	 */
	public $phone;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="ddate", type="datetime")
	 */
	public $ddate;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="tdate", type="datetime")
	 */
	public $tdate;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="extra", type="string", length=255, nullable=true)
	 */
	public $extra;

	/**
	 * @var int
	 *
	 * @ORM\Column(name="state", type="smallint", nullable=true)
	 */
	public $state;

	/**
	 * @var string
	 * @ORM\Column(name="fee", type="decimal", precision=20, scale=2)
	 */
	public $fee;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="type", type="string", length=20)
	 */
	public $type;
}