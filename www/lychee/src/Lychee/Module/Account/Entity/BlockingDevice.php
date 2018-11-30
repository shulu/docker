<?php
namespace Lychee\Module\Account\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="blocking_device", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="platform_device_id_udx", columns={"platform", "device_id"})
 * })
 */
class BlockingDevice {

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    public $id;

    /**
     * @var int
     * @ORM\Column(name="user_id", type="bigint", nullable=true)
     */
    public $userId;

    /**
     * @var string
     * @ORM\Column(name="platform", type="string", length=20)
     */
    public $platform;

    /**
     * @var string
     * @ORM\Column(name="device_id", type="string", length=64)
     */
    public $deviceId;

    /**
     * @var \DateTime
     * @ORM\Column(name="create_time", type="datetime")
     */
    public $createTime;
}