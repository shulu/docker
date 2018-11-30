<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 8/22/16
 * Time: 6:47 PM
 */

namespace Lychee\Module\Promotion\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Campaign
 * @package Lychee\Module\Promotion\Entity
 * @ORM\Entity()
 * @ORM\Table(name="campaign")
 */
class Campaign {

    /**
     * @var integer
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(type="bigint")
     */
    public $id;

    /**
     * @var integer
     * @ORM\Column(type="string", name="image", length=255)
     */
    public $image;

    /**
     * @var
     * @ORM\Column(type="string", name="link", length=255)
     */
    public $link;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", name="start_time")
     */
    public $startTime;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", name="end_time")
     */
    public $endTime;

    /**
     * @var int
     * @ORM\Column(type="integer", name="views", options={"default":-1})
     */
    public $views = -1;

    /**
     * @var int
     * @ORM\Column(type="integer", name="unique_views", options={"default":-1})
     */
    public $uniqueViews = -1;
}