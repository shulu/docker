<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14/11/14
 * Time: 下午6:25
 */

namespace Lychee\Module\Analysis\Task;


use Doctrine\ORM\EntityManager;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Module\Analysis\DailyCountTrait;
use Lychee\Module\Analysis\Entity\AdminDailyAnalysis;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class CounterTaskTrait
 * @package Lychee\Module\Analysis\Task
 */
trait CounterTaskTrait {

    use ContainerAwareTrait;
    use DailyCountTrait;

    /**
     * @return int
     */
    public function getDefaultInterval()
    {
        return 8 * 3600;
    }

    /**
     * @param EntityManager $entityManager
     * @param $analysisType
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getLastCountDate(EntityManager $entityManager, $analysisType)
    {
        $repo = $entityManager->getRepository(AdminDailyAnalysis::class);
        $query = $repo->createQueryBuilder('da')
            ->where('da.type = :type')
            ->setParameter('type', $analysisType)
            ->orderBy('da.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery();
        return $query->getOneOrNullResult();
    }

}