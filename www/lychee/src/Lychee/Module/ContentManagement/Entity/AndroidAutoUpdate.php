<?php
/**
 * Created by PhpStorm.
 * User: ys160726
 * Date: 2016/12/26
 * Time: 下午2:40
 */

namespace Lychee\Module\ContentManagement\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class AndroidAutoUpdate
 * @package Lychee\Module\ContentManagement\Entity
 * @ORM\Entity()
 * @ORM\Table(name="android_auto_update",indexes={@ORM\Index(name="app_idx", columns={"app_id"})})
 */
class AndroidAutoUpdate
{
    const APP_CIYUANSHE = 1;
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    public $id;

    /**
     * @var int
     * @ORM\Column(name="app_id", type="integer")
     */
    public $appId;

    /**
     * @var string
     * @ORM\Column(name="version", type="string", length=100)
     */
    public $version;

    /**
     * @var \DateTime
     * @ORM\Column(name="upload_date", type="datetime")
     */
    public $uploadDate;

    /**
     * @var string
     * @ORM\Column(name="size", type="string", length=10, nullable=true)
     */
    public $size;

    /**
     * @var string
     * @ORM\Column(name="log", type="string", length=500)
     */
    public $log;

    /**
     * @var string
     * @ORM\Column(name="link", type="string", length=255, nullable=true)
     */
    public $link;

    /**
     * @var boolean
     * @ORM\Column(name="auto_update", type="boolean")
     */
    public $autoUpdate;

    /**
     * @var int
     * @ORM\Column(name="version_code", type="integer")
     */
    public $versionCode;
}