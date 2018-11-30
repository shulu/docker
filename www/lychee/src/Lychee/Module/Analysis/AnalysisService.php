<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 15-2-28
 * Time: 下午4:36
 */

namespace Lychee\Module\Analysis;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Lychee\Module\Analysis\Entity\AdminDailyAnalysis;

/**
 * Class AnalysisService
 * @package Lychee\Module\Analysis
 */
class AnalysisService {

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct($registry) {
        $this->entityManager = $registry->getManager();
    }

    /**
     * @param string $analysisType
     * @param \DateTime $date
     * @param int $amount
     * @return bool
     */
    public function setDailyAnalysis($analysisType, \DateTime $date, $amount = 0) {
        $dailyRow = $this->getDailyRow($analysisType, $date);
        $previousDate = new \DateTime(date('Y-m-d', strtotime('-1 day', $date->getTimestamp())));
        $previousRow = $this->getDailyRow($analysisType, $previousDate);

        if (null !== $dailyRow) {
            if (!$this->isLatestRow($dailyRow)) {
                return false;
            }
            // Update
            $dailyRow->dailyCount = $amount;
            if (null !== $previousRow) {
                $dailyRow->totalCount = $amount + $previousRow->totalCount;
            } else {
                $dailyRow->totalCount = $amount;
            }
        } else {
            // Insert
            $dailyCount = new AdminDailyAnalysis();
            $dailyCount->type = $analysisType;
            $dailyCount->date = $date;
            $dailyCount->dailyCount = $amount;

            if (null !== $previousRow) {
                $dailyCount->totalCount = $previousRow->totalCount + $amount;
            } else {
                $dailyCount->totalCount = $amount;
            }
            $this->entityManager->persist($dailyCount);
        }
        $this->entityManager->flush();

        return true;
    }

    /**
     * @param string $analysisType
     * @param \DateTime $date
     * @return null|AdminDailyAnalysis
     */
    private function getDailyRow($analysisType, \DateTime $date) {
        $repo = $this->entityManager->getRepository(AdminDailyAnalysis::class);

        return $repo->findOneBy([
            'type' => $analysisType,
            'date' => $date
        ]);
    }

    /**
     * @param AdminDailyAnalysis $row
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function isLatestRow(AdminDailyAnalysis $row) {
        $repository = $this->entityManager->getRepository(AdminDailyAnalysis::class);
        $query = $repository->createQueryBuilder('a')
            ->where('a.type = :type')
            ->setParameter('type', $row->type)
            ->orderBy('a.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery();
        $latestRow = $query->getOneOrNullResult();

        return $row->id === $latestRow->id;
    }
}