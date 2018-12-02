<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 15-3-10
 * Time: 下午6:39
 */

namespace Lychee\Module\ContentManagement\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Sticker
 * @package Lychee\Module\ContentManagement\Entity
 * @ORM\Entity()
 * @ORM\Table(name="sticker")
 */
class Sticker {

    /**
     * @var
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @var
     *
     * @ORM\Column(type="string", length=20)
     */
    public $name;

    /**
     * @var
     *
     * @ORM\Column(name="thumbnail_url", type="string", nullable=true)
     */
    public $thumbnailUrl;

    /**
     * @var
     *
     * @ORM\Column(name="is_new", type="smallint")
     */
    public $isNew;

    /**
     * @var
     *
     * @ORM\Column(name="url", type="string", nullable=true)
     */
    public $url;

    /**
     * @var
     *
     * @ORM\Column(name="last_modified_time", type="datetime", nullable=true)
     */
    public $lastModifiedTime;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     */
    public $deleted = 0;
}