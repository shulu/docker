<?php
/**
 * Created by PhpStorm.
 * User: ys160726
 * Date: 2016/12/6
 * Time: 下午4:16
 */

namespace Lychee\Module\Game\Entity;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity()
 * @ORM\Table(name="game_category")
 * Class GameCategory
 * @package Lychee\Module\Game\Entity
 */
class GameCategory
{

    /**
     * @var int;
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    public $id;


    /**
     * @var string;
     * @ORM\Column(name="icon", type="string", length=2083, nullable=true)
     */
    public $icon;


    /**
     * @var string;
     * @ORM\Column(name="name", type="string", length=20, nullable=true)
     */
    public $name;

}