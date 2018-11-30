<?php
/**
 * Created by PhpStorm.
 * User: john
 * Date: 2017/3/30
 * Time: 下午2:18
 */

namespace Lychee\Module\ExtraMessage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="em_weibo_auth", schema="ciyo_extramessage")
 * Class Contacts
 * @package Lychee\Module\ExtraMessage\Entity
 */

class EMWeiboAuth
{
    /**
     * @var int
     *
     * @ORM\Column(name="weibo_uid", type="bigint")
     * @ORM\Id
     */
    public $weiboUid;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="bigint")
     */
    public $userId;
}