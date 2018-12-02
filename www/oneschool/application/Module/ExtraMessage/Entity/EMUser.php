<?php
/**
 * Created by PhpStorm.
 * User: john
 * Date: 2017/3/30
 * Time: 下午4:57
 */

namespace Lychee\Module\ExtraMessage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EMUser
 *
 * @ORM\Entity
 * @ORM\Table(name="em_user", schema="ciyo_extramessage", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="area_code_phone_udx", columns={"area_code", "phone"})
 * })
 *
 * @package Lychee\Module\ExtraMessage\Entity
 */
class EMUser {

	const GENDER_MALE = 1;
	const GENDER_FEMALE = 2;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="id", type="bigint")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	public $id;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="area_code", type="string", length=10, nullable=true)
	 */
	public $areaCode;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="phone", type="string", length=20, nullable=true)
	 */
	public $phone;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="create_time", type="datetime")
	 */
	public $createTime;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="nickname", type="string", length=36, nullable=true)
	 */
	public $nickname;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="signature", type="string", length=200, nullable=true)
	 */
	public $signature;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="avatar_url", type="string", length=2083, nullable=true)
	 */
	public $avatarUrl;

	/**
	 * @var int
	 *
	 * @ORM\Column(name="gender", type="smallint", nullable=true)
	 */
	public $gender;

	/**
	 * @var boolean
	 * @ORM\Column(name="frozen", type="boolean", nullable=false)
	 */
	public $frozen = false;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="location", type="string", length=255, nullable=true)
	 */
	public $location;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="birthday", type="date", nullable=true)
	 */
	public $birthday;

	/**
	 * @var int
	 *
	 * @ORM\Column(name="age", type="integer", nullable=true)
	 */
	public $age;

    /**
     * @var string
     *
     * @ORM\Column(name="grant_type", type="string", length=50, options={"default" : ""})
     */
	public $grantType = '';
}