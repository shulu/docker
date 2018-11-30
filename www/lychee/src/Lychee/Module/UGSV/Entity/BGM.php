<?php
namespace Lychee\Module\UGSV\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="ugsv_bgm", indexes={
 *   @ORM\Index(name="weight", columns={"weight"}),
 *   @ORM\Index(name="use_count", columns={"use_count"})
 * })
 */
class BGM {

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, options={"comment":"歌曲名称"})
     */
    public $name;

    /**
     * @var string
     *
     * @ORM\Column(name="singer_name", type="string", length=100, options={"comment":"歌手名称"})
     */
    public $singerName;

    /**
     * @var int
     *
     * @ORM\Column(name="size", type="bigint", options={"unsigned":true, "comment":"文件大小xx字节"})
     */
    public $size;

    /**
     * @var int
     *
     * @ORM\Column(name="duration", type="integer", options={"unsigned":true, "comment":"音频时长xx秒"})
     */
    public $duration;

    /**
     * @var string
     *
     * @ORM\Column(name="src", type="string", length=2083, options={"comment":"音频文件完整地址"})
     */
    public $src;

    /**
     * @var string
     *
     * @ORM\Column(name="cover", type="string", length=2083, options={"comment":"封面图完整地址"})
     */
    public $cover;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_time", type="datetime", options={"comment":"创建时间"})
     */
    public $createTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_time", type="datetime", options={"comment":"更新时间"})
     */
    public $updateTime;

    /**
     * @var int
     *
     * @ORM\Column(name="weight", type="integer", options={"unsigned":true, "default":"0", "comment":"排序权重，值越大，热门排序越靠前"})
     */
    public $weight = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="use_count", type="bigint", options={"unsigned":true, "comment":"使用次数"})
     */
    public $useCount = 0;

}