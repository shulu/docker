<?php
namespace Lychee\Module\Authentication\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="auth_wechat")
 */
class WechatAuth {
    /**
     * @var string
     *
     * @ORM\Column(name="open_id", type="string", length=32)
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