<?php
namespace Lychee\Module\CiyoActivity\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="activity_top_ciyo_by_week", indexes={
 * },options={"comment":"每周精选次元"})
 */
class ActivityTopCiyoByWeek {
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(name="id", type="bigint",options={"comment":"ID"}))
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string",length=255,options={"comment":"名字"}))
     */
    public $name;

    /**
     * @var int
     *
     * @ORM\Column(name="start_timestamp", type="bigint",options={"comment":"开始时间"}))
     */
    public $start_timestamp;

    /**
     * @var int
     *
     * @ORM\Column(name="end_timestamp", type="bigint",options={"comment":"结束时间"}))
     */
    public $end_timestamp;
}