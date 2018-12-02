<?php
/**
 * Created by PhpStorm.
 * User: ys160726
 * Date: 2016/12/6
 * Time: 下午3:02
 */

namespace Lychee\Module\Game\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="game_columns_recommendation",indexes={@ORM\Index(name="columns_idx", columns={"column_id"})})
 * Class GameRecommendation
 * @package Lychee\Module\Game\Entity
 */
class GameColumnsRecommendation
{

    /**
     * @var int;
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    public $id;
    
    /**
     * @var int;
     * @ORM\Column(name="column_id", type="integer")
     */
    public $columnId;


    /**
     * @var int
     * @ORM\Column(name="game_id", type="bigint")
     */
    public $gameId;


    /**
     * @var int
     * @ORM\Column(name="position", type="integer")
     */
    public $position;

}