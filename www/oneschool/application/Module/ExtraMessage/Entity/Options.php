<?php
/**
 * Created by PhpStorm.
 * User: ys160726
 * Date: 2017/3/13
 * Time: 上午11:49
 */

namespace Lychee\Module\ExtraMessage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="options", schema="ciyo_extramessage"
 * )
 * Class Options
 * @package Lychee\Module\ExtraMessage\Entity
 */
class Options
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var int
     * @ORM\Column(name="option_id", type="bigint")
     */
    public $optionId;
    /**
     * @var string
     *
     * @ORM\Column(name="option_str", type="string", length=255, nullable=true)
     */
    public $optionStr;

    /**
     * @var int
     * @ORM\Column(name="next", type="bigint", nullable=true)
     */
    public $next;


}