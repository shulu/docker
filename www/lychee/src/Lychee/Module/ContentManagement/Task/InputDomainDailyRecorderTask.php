<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 15-2-4
 * Time: 下午2:28
 */

namespace Lychee\Module\ContentManagement\Task;


use Lychee\Component\Task\Task;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Lychee\Module\ContentManagement\Entity\InputDomainDailyRecord;
use Lychee\Module\ContentManagement\Entity\InputDomainRecord;

/**
 * Class InputDomainDailyRecorderTask
 * @package Lychee\Module\ContentManagement\Task
 */
class InputDomainDailyRecorderTask implements Task {

    use ContainerAwareTrait;

    /**
     * @return string
     */
    public function getName() {
        return 'input domain daily record';
    }

    /**
     * @return int
     */
    public function getDefaultInterval() {
        return 12 * 3600;
    }

    /**
     *
     */
    public function run() {
        $entityManager = $this->container->get('doctrine')->getManager();
        $today = new \DateTime('midnight');
        $yesterday = new \DateTime('yesterday midnight');
        $dailyRecordRepo = $entityManager->getRepository(InputDomainDailyRecord::class);
        $query = $dailyRecordRepo->createQueryBuilder('dr')
            ->orderBy('dr.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery();
        $latestRecord = $query->getOneOrNullResult();
        if (null !== $latestRecord) {
            $latestRecordDate = $latestRecord->date;
            if ($latestRecordDate < $yesterday) {
                // Insert
                $this->addDailyRecord($dailyRecordRepo, $yesterday, $today);
            }
        } else {
            $this->addDailyRecord($dailyRecordRepo, $yesterday, $today);
        }
    }

    /**
     * @param $dailyRecordRepo
     * @param $yesterday
     * @param $today
     */
    private function addDailyRecord($dailyRecordRepo, $yesterday, $today) {
        $entityManager = $this->container->get('doctrine')->getManager();
        $domainRecordRepo = $entityManager->getRepository(InputDomainRecord::class);
        $query = $domainRecordRepo->createQueryBuilder('r')
            ->where('r.datetime >= :yesterday AND r.datetime < :today')
            ->setParameter('yesterday', $yesterday)
            ->setParameter('today', $today)
            ->getQuery();
        $result = $query->getResult();
        $records = [];
        if (null !== $result) {
            foreach ($result as $row) {
                if (isset($records[$row->domainId])) {
                    $records[$row->domainId] += 1;
                } else {
                    $records[$row->domainId] = 1;
                }
            }
        }
        // Save
        foreach ($records as $domainId => $count) {
            $dailyRecord = new InputDomainDailyRecord();
            $dailyRecord->date = $yesterday;
            $dailyRecord->domainId = $domainId;
            $dailyRecord->count = $count;
            $entityManager->persist($dailyRecord);
        }
        $entityManager->flush();
    }
}