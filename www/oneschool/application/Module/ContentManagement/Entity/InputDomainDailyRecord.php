<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 15-2-3
 * Time: 下午3:36
 */

namespace Lychee\Module\ContentManagement\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class InputDomainDailyRecord
 * @package Lychee\Module\ContentManagement\Entity
 * @ORM\Entity()
 * @ORM\Table(name="input_domain_daily_record")
 */
class InputDomainDailyRecord {
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
     * @ORM\Column(name="domain_id", type="integer")
     */
    public $domainId;

    /**
     * @var
     *
     * @ORM\Column(type="integer", options={"default": 0})
     */
    public $count = 0;
}