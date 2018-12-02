<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14-9-26
 * Time: 下午8:28
 */

namespace Lychee\Bundle\AdminBundle\Service;


use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Lychee\Component\Foundation\ArrayUtility;

class OperationAccountsService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(Registry $registry)
    {
        $this->entityManager = $registry->getManager($registry->getDefaultManagerName());
    }

    public function fetchIds()
    {
        $query = $this->entityManager->createQuery('
            SELECT o.id
            FROM LycheeAdminBundle:OperationAccount o
        ');

        return ArrayUtility::mapByColumn($query->getResult(), 'id');
    }

    public function fetch($ids)
    {
        $query = $this->entityManager->createQuery('
            SELECT o
            FROM LycheeAdminBundle:OperationAccount o
            WHERE o.id IN(:accountIds)
        ')->setParameter('accountIds', $ids);

        return $query->getResult();
    }
} 