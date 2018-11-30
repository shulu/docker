<?php
/**
 * Created by PhpStorm.
 * User: ys160726
 * Date: 2017/1/5
 * Time: 下午5:08
 */

namespace Lychee\Module\Live\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class LiveInkeInfo
 * @package Lychee\Module\Live\Entity
 * @ORM\Entity()
 * @ORM\Table(name="live_inke_live_info", schema="ciyo_live")
 */
class LiveInkeInfo
{

    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(name="inke_uid", type="string", length=100)
     */
    public $inkeUid;

    /**
     * @var \DateTime
     * @ORM\Column(name="create_time", type="datetime")
     */
    public $createTime;

    /**
     * @var \DateTime
     * @ORM\Column(name="update_time", type="datetime", nullable=true)
     */
    public $updateTime;

    /**
     * @var boolean
     * @ORM\Column(name="top", type="boolean", options={"default":0})
     */
    public $top;
}