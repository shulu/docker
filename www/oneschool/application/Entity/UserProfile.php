<?php
namespace app\entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="user_profile", options={"charset"="utf8mb4", "collate"="utf8mb4_unicode_ci"})
 */
class UserProfile {

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="user_id", type="bigint")
     */
    public $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="cover_url", type="string", length=2083, nullable=true)
     */
    public $coverUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="signature", type="string", length=200, nullable=true)
     */
    public $signature;

    /**
     * @var string
     *
     * @ORM\Column(name="honmei", type="string", length=200, nullable=true)
     */
    public $honmei;

    /**
     * @var string
     *
     * @ORM\Column(name="attributes", type="string", length=1000, nullable=true)
     */
    public $attributes;

    /**
     * @var string
     *
     * @ORM\Column(name="skills", type="string", length=200, nullable=true)
     */
    public $skills;

    /**
     * @var string
     *
     * @ORM\Column(name="constellation", type="string", length=20, nullable=true)
     */
    public $constellation;

    /**
     * @var string
     *
     * @ORM\Column(name="blood_type", type="string", length=10, nullable=true)
     */
    public $bloodType;

    /**
     * @var int
     *
     * @ORM\Column(name="age", type="integer", nullable=true)
     */
    public $age;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="birthday", type="date", nullable=true)
     */
    public $birthday;

    /**
     * @var string
     *
     * @ORM\Column(name="location", type="string", length=200, nullable=true)
     */
    public $location;

    /**
     * @var string
     *
     * @ORM\Column(name="school", type="string", length=100, nullable=true)
     */
    public $school;

    /**
     * @var string
     *
     * @ORM\Column(name="community", type="string", length=100, nullable=true)
     */
    public $community;

    /**
     * @var string
     *
     * @ORM\Column(name="fancy", type="string", length=200, nullable=true)
     */
    public $fancy;
} 