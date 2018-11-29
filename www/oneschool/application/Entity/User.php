<?php

namespace app\entity;

/**
 * User
 *
 * @Entity
 * @Table(name="user", uniqueConstraints={
 *   @UniqueConstraint(name="email_udx", columns={"email"}),
 *   @UniqueConstraint(name="nickname_udx", columns={"nickname"}),
 *   @UniqueConstraint(name="area_code_phone_udx", columns={"area_code", "phone"})
 * })
 */
class User {

    const GENDER_MALE = 1;
    const GENDER_FEMALE = 2;

    /**
     * @var integer
     *
     * @Column(name="id", type="bigint")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     *
     * @Column(name="email", type="string", length=255, nullable=true)
     */
    public $email;

    /**
     * @var string
     *
     * @Column(name="area_code", type="string", length=10, nullable=true)
     */
    public $areaCode;

    /**
     * @var string
     *
     * @Column(name="phone", type="string", length=20, nullable=true)
     */
    public $phone;

    /**
     * @var \DateTime
     *
     * @Column(name="create_time", type="datetime")
     */
    public $createTime;

    /**
     * @var string
     *
     * @Column(name="nickname", type="string", length=36, nullable=true)
     */
    public $nickname;

    /**
     * @var string
     *
     * @Column(name="signature", type="string", length=200, nullable=true)
     */
    public $signature;

    /**
     * @var string
     *
     * @Column(name="avatar_url", type="string", length=2083, nullable=true)
     */
    public $avatarUrl;

    /**
     * @var string
     *
     * @Column(name="cover_url", type="string", length=2083, nullable=true)
     */
    public $coverUrl;

    /**
     * @var int
     *
     * @Column(name="gender", type="smallint", nullable=true)
     */
    public $gender;

    /**
     * @var boolean
     * @Column(name="frozen", type="boolean", nullable=false, options={"default": 0})
     */
    public $frozen = false;

    /**
     * @var int
     *
     * @Column(name="experience", type="integer", nullable=false, options={"default": 0})
     */
    public $experience = 0;

    /**
     * @var int
     *
     * @Column(name="level", type="integer", options={"default": 1})
     */
    public $level = 1;

	/**
	 * @var int
	 *
	 * @Column(name="ciyo_coin", type="decimal", precision=20, scale=2, options={"default": 0})
	 */
	public $ciyoCoin = 0;
	
	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}
	/**
	 * @param int $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}
	/**
	 * @return string
	 */
	public function getUsername()
	{
		return $this->nickname;
	}
	/**
	 * @param string $username
	 */
	public function setUsername($username)
	{
		$this->nickname = $username;
	}
}
