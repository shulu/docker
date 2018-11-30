<?php
/**
 * Created by PhpStorm.
 * User: ys160726
 * Date: 2016/12/6
 * Time: 下午2:46
 */

namespace Lychee\Module\Game\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="game_columns")
 * Class GameColumns
 * @package Lychee\Module\Game\Entity
 */
class GameColumns
{

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    public $id;

    /**
     * @var string
     * @ORM\Column(name="title", type="string", length=20)
     */
    public $title;
}