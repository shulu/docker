<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14/10/30
 * Time: 下午12:19
 */

namespace Lychee\Bundle\AdminBundle\Service\ManagerLog;


use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Lychee\Bundle\AdminBundle\Entity\ManagerLog;

/**
 * Class ManagerLogService
 * @package Lychee\Bundle\AdminBundle\Service\ManagerLog
 */
class ManagerLogService {

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param $operatorId
     * @param $operationType
     * @param $targetId
     * @param array $description
     */
    public function log($operatorId, $operationType, $targetId, $description = [])
    {
        $managerLog = new ManagerLog();
        $managerLog->operatorId = $operatorId;
        $managerLog->operationType = $operationType;
        $managerLog->targetId = $targetId;
        $managerLog->operationTime = new \DateTime();
        if (is_array($description) && !empty($description)) {
            $managerLog->description = json_encode($description);
        }
        $this->entityManager->persist($managerLog);
        $this->entityManager->flush();
    }

    /**
     * @param $operationType
     * @param $targetIds
     * @return array
     */
    public function fetchLogsByTypeAndTargetIds($operationType, $targetIds)
    {
        $repo = $this->entityManager->getRepository('LycheeAdminBundle:ManagerLog');
        $qb = $repo->createQueryBuilder('ml')
            ->select('ml')
            ->where('ml.operationType = :operationType AND ml.targetId IN (:targetIds)')
            ->setParameter('operationType', $operationType)
            ->setParameter('targetIds', $targetIds)
            ->getQuery();

        return $qb->getResult();
    }
}