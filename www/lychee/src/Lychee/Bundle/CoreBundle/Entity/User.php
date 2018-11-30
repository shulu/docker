<?php

namespace Lychee\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * User
 *
 * @ORM\Entity
 * @ORM\Table(name="user", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="email_udx", columns={"email"}),
 *   @ORM\UniqueConstraint(name="nickname_udx", columns={"nickname"}),
 *   @ORM\UniqueConstraint(name="area_code_phone_udx", columns={"area_code", "phone"})
 * })
 */
class User {

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
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    public $email;

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
     * @var string
     *
     * @ORM\Column(name="cover_url", type="string", length=2083, nullable=true)
     */
    public $coverUrl;

    /**
     * @var int
     *
     * @ORM\Column(name="gender", type="smallint", nullable=true)
     */
    public $gender;

    /**
     * @var boolean
     * @ORM\Column(name="frozen", type="boolean", nullable=false, options={"default": 0})
     */
    public $frozen = false;

    /**
     * @var int
     *
     * @ORM\Column(name="experience", type="integer", nullable=false, options={"default": 0})
     */
    public $experience = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="level", type="integer", options={"default": 1})
     */
    public $level = 1;

	/**
	 * @var int
	 *
	 * @ORM\Column(name="ciyo_coin", type="decimal", precision=20, scale=2, options={"default": 0})
	 */
	public $ciyoCoin = 0;
}
