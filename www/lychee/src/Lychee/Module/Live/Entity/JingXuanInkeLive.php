<?php
/**
 * Created by PhpStorm.
 * User: ys160726
 * Date: 2017/2/14
 * Time: 上午10:14
 */

namespace Lychee\Module\Live\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class JingXuanInkeLive
 * @package Lychee\Module\Live\Entity
 * @ORM\Entity()
 * * @ORM\Table(name="jingxuan_inke_live", schema="ciyo_live", indexes={
 *   @ORM\Index(name="start_time_idx", columns={"start_time"}),
 *   @ORM\Index(name="end_time_idx", columns={"end_time"})
 * })
 */
class JingXuanInkeLive
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(name="inke_uid", type="string", length=100)
     */
    public $inkeUid;

    /**
     * @var string
     * @ORM\Column(name="nikename", type="string", length=100, nullable=true)
     */
    public $nikename;

    /**
     * @var string
     * @ORM\Column(name="avatar", type="string", length=2083, nullable=true)
     */
    public $avatar;

    /**
     * @var string
     * @ORM\Column(name="cover", type="string", length=2083, nullable=true)
     */
    public $cover;

    /**
     * @var string
     * @ORM\Column(name="description", type="string", length=1000, nullable=true)
     */
    public $description;

    /**
     * @var \DateTime
     * @ORM\Column(name="start_time", type="datetime")
     */
    public $startTime;

    /**
     * @var \DateTime
     * @ORM\Column(name="end_time", type="datetime")
     */
    public $endTime;
}