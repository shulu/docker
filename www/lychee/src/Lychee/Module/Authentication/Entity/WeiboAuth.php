<?php
namespace Lychee\Module\Authentication\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="auth_sina_weibo")
 */
class WeiboAuth {
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