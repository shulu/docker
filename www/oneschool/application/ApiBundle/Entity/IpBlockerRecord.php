<?php
namespace Lychee\Bundle\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="ip_blocker_records")
 */
class IpBlockerRecord {
    /**
     * @var string
     * @ORM\Column(name="ip", type="string", length=16)
     * @ORM\Id
     */
    public $ip;

    /**
     * @var string
     * @ORM\Column(name="action", type="string", length=20)
     * @ORM\Id
     */
    public $action;

    /**
     * @var int
     * @ORM\Column(name="last_time", type="integer")
     */
    public $lastTime;

    /**
     * @var int
     * @ORM\Column(name="day_count", type="integer")
     */
    public $dayCount;

    /**
     * @var int
     * @ORM\Column(name="hour_count", type="smallint")
     */
    public $hourCount;

    /**
     * @var int
     * @ORM\Column(name="version", type="bigint")
     */
    public $version;

}