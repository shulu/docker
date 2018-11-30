<?php
/**
 * Created by PhpStorm.
 * User: ys160726
 * Date: 2017/3/13
 * Time: 上午11:34
 */

namespace Lychee\Module\ExtraMessage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="plays", schema="ciyo_extramessage", indexes={
 *   @ORM\Index(name="next_idx", columns={"next"}),
 *   @ORM\Index(name="type_idx", columns={"type"})
 * }
 * )
 * Class Plays
 * @package Lychee\Module\ExtraMessage\Entity
 */
class Plays
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(name="subtitleline", type="string", length=255, nullable=true)
     */
    public $subtitleline;

    /**
     * @var int
     * @ORM\Column(name="type", type="integer")
     */
    public $type;

    /**
     * @var int
     * @ORM\Column(name="next", type="bigint", nullable=true)
     */
    public $next;

}