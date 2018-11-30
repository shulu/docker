<?php

namespace Lychee\Bundle\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OperationAccount
 *
 * @ORM\Table(name="admin_operation_account")
 * @ORM\Entity()
 */
class OperationAccount
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     */
    public $id;


    /**
     * @var array
     *
     * @ORM\ManyToMany(targetEntity="Manager", mappedBy="operationAccounts")
     */
    public $managers;
}
