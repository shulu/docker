<?php
namespace Lychee\Module\Authentication\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="phone_code")
 */
class PhoneCode {
    /**
     * @var int
     * @ORM\Column(name="area_code", type="string", length=10)
     * @ORM\Id
     */
    public $areaCode;

    /**
     * @var int
     * @ORM\Column(name="phone", type="string", length=20)
     * @ORM\Id
     */
    public $phone;

    /**
     * @var int
     * @ORM\Column(name="create_time", type="integer")
     */
    public $createTime;

    /**
     * @var string
     * @ORM\Column(name="code", type="string", length=6)
     */
    public $code;
}