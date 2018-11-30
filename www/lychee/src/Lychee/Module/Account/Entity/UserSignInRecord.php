<?php
/**
 * Created by PhpStorm.
 * User: Jison
 * Date: 16/3/31
 * Time: 下午2:34
 */

namespace Lychee\Module\Account\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="user_sign_in")
 */
class UserSignInRecord {
    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="bigint")
     * @ORM\Id
     */
    public $userId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="`time`", type="datetime")
     */
    public $time;

    /**
     * @var string
     *
     * @ORM\Column(name="os", type="string", length=20, nullable=true)
     */
    public $os;

    /**
     * @var string
     *
     * @ORM\Column(name="os_version", type="string", length=20, nullable=true)
     */
    public $osVersion;

    /**
     * @var string
     *
     * @ORM\Column(name="device_id", type="string", length=128, nullable=true)
     */
    public $deviceId;

    /**
     * @var string
     *
     * @ORM\Column(name="device_type", type="string", length=20, nullable=true)
     */
    public $deviceType;

    /**
     * @var string
     *
     * @ORM\Column(name="client_version", type="string", length=20, nullable=true)
     */
    public $clientVersion;

}