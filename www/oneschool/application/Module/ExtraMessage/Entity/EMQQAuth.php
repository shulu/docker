<?php
/**
 * Created by PhpStorm.
 * User: john
 * Date: 2017/3/30
 * Time: 下午2:12
 */

namespace Lychee\Module\ExtraMessage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="em_qq_auth", schema="ciyo_extramessage")
 * Class Contacts
 * @package Lychee\Module\ExtraMessage\Entity
 */

class EMQQAuth
{
    /**
     * @var int
     *
     * @ORM\Column(name="open_id", type="string", length=64)
     * @ORM\Id
     */
    public $openId;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="bigint")
     */
    public $userId;

}