<?php
namespace Lychee\Module\Account\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="user_device")
 */
class UserDevice {

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="bigint")
     * @ORM\Id
     */
    public $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="platform", type="string", length=20)
     */
    public $platform;

    /**
     * @var string
     *
     * @ORM\Column(name="device_id", type="string", length=64)
     */
    public $deviceId;

}