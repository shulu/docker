<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14/10/30
 * Time: 下午1:57
 */

namespace Lychee\Bundle\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class ManagerLog
 * @package Lychee\Bundle\AdminBundle\Entity
 *
 * @ORM\Entity()
 * @ORM\Table(name="admin_log")
 */
class ManagerLog {

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    public $id;

    /**
     * @var
     *
     * @ORM\Column(name="operator_id", type="integer")
     */
    public $operatorId;

    /**
     * @var
     *
     * @ORM\Column(name="operation_type", type="string", length=50)
     */
    public $operationType;

    /**
     * @var
     *
     * @ORM\Column(name="target_id", type="bigint")
     */
    public $targetId;

    /**
     * @var
     *
     * @ORM\Column(name="operation_time", type="datetime")
     */
    public $operationTime;

    /**
     * @var
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    public $description;
} 