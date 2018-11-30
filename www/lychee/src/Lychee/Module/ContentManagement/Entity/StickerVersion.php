<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 3/11/15
 * Time: 3:49 PM
 */

namespace Lychee\Module\ContentManagement\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class StickerVersion
 * @package Lychee\Module\ContentManagement\Entity
 * @ORM\Entity()
 * @ORM\Table(name="sticker_version")
 */
class StickerVersion {

    /**
     * @var
     *
     * @ORM\Id
     * @ORM\Column(name="version", type="integer")
     */
    public $version;
}