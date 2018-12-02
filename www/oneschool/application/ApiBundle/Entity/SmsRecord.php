<?php
namespace Lychee\Bundle\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="sms_records")
 */
class SmsRecord {

    /**
     * @var int
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var \DateTime
     * @ORM\Column(name="`time`", type="datetime")
     */
    public $time;

    /**
     * @var string
     * @ORM\Column(name="ip", type="string", length=16, nullable=true)
     */
    public $ip;

    /**
     * @var string
     * @ORM\Column(name="area_code", type="string", length=10)
     */
    public $areaCode;

    /**
     * @var string
     * @ORM\Column(name="phone", type="string", length=20)
     */
    public $phone;

    /**
     * @var string
     * @ORM\Column(name="platform", type="string", length=20, nullable=true)
     */
    public $platform;

    /**
     * @var string
     * @ORM\Column(name="os_version", type="string", length=20, nullable=true)
     */
    public $osVersion;

    /**
     * @var string
     * @ORM\Column(name="app_version", type="string", length=20, nullable=true)
     */
    public $appVersion;

    /**
     * @var string
     * @ORM\Column(name="device_id", type="string", length=64, nullable=true)
     */
    public $deviceId;

}