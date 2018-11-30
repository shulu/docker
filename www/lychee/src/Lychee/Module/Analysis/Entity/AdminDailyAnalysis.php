<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14/11/7
 * Time: 下午7:09
 */

namespace Lychee\Module\Analysis\Entity;


use Doctrine\ORM\Mapping as ORM;

/**
 * Class AdminDailyAnalysis
 * @package Lychee\Module\Analysis\Entity
 * @ORM\Entity()
 * @ORM\Table(
 *     name="admin_daily_analysis",
 *     indexes={@ORM\Index(name="daily_analysis_date_idx",columns={"date"})},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="type_date_idx",columns={"date","type"})},
 * )
 */
class AdminDailyAnalysis {

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
     * @ORM\Column(type="date")
     */
    public $date;

    /**
     * @var
     *
     * @ORM\Column(type="string", length=20)
     */
    public $type;

    /**
     * @var
     *
     * @ORM\Column(type="integer")
     */
    public $dailyCount;

    /**
     * @var
     *
     * @ORM\Column(type="integer")
     */
    public $totalCount;
} 