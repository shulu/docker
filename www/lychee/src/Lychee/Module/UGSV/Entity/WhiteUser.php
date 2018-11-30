<?php
namespace Lychee\Module\UGSV\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="ugsv_white_list")
 */
class WhiteUser {

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="bigint", options={"unsigned":true, "comment":"用户id"})
     * @ORM\Id
     */
    public $userId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_time", type="datetime", options={"comment":"创建时间"})
     */
    public $createTime;

}