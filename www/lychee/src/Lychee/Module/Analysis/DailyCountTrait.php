<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14/11/14
 * Time: 下午7:32
 */

namespace Lychee\Module\Analysis;


use Doctrine\ORM\EntityManager;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Foundation\CursorableIterator\AbstractCursorableIterator;
use Lychee\Module\Analysis\Entity\AdminDailyAnalysis;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DailyCountTrait
 * @package Lychee\Module\Analysis
 */
trait DailyCountTrait {

    use ModuleAwareTrait;

    /**
     * @param EntityManager $entityManager
     * @param AbstractCursorableIterator $iterator
     * @param $analysisType
     * @param int $initSum
     * @param OutputInterface $output
     * @param string $datetimeProperty
     */
    protected function dailyAnalysis(
        EntityManager $entityManager,
        AbstractCursorableIterator $iterator,
        $analysisType,
        $initSum = 0,
        OutputInterface $output = null,
        $datetimeProperty = 'createTime'
    )
    {
        gc_enable();
        $sum = $initSum;
        $dailyCount = [];

	    $loopEnd = 0;
	    while ($loopEnd !== 1) {
	    	$resultSet = $iterator->current();
		    foreach ($resultSet as $row) {
			    $sum++;
			    $createDate = $row->$datetimeProperty->format('Y-m-d');
			    if (isset($dailyCount[$createDate])) {
				    $dailyCount[$createDate][0]++;
				    $dailyCount[$createDate][1] = $sum;
			    } else {
				    $dailyCount[$createDate] = array(1, $sum);
			    }
		    }
		    $dates = array_keys($dailyCount);
		    list($unrecordedDates, $recordedResult) = $this->splitDates($entityManager, $analysisType, $dates);
		    $this->updateRecordedDates($entityManager, $dailyCount, $recordedResult);
		    $this->insertUnrecordedDates($entityManager, $analysisType, $dailyCount, $unrecordedDates);
		    $entityManager->clear();
		    gc_collect_cycles();
		    if (null !== $output) {
			    $cursor = $iterator->getCursor();
			    if ($cursor instanceof \DateTime) {
				    $cursor = $cursor->format('Y-m-d H:i:s');
			    }
			    $output->writeln(sprintf("Current Cursor: %s", $cursor));
		    }

		    if ($iterator->getNextCursor() == 0) {
		    	$loopEnd = 1;
			    continue;
		    }
		    $iterator->next();
	    }
    }

    /**
     * @param EntityManager $entityManager
     * @param $type
     * @param $dates
     * @return array
     */
    public function splitDates(EntityManager $entityManager, $type, $dates)
    {
        $dailyAnalysisRepo = $entityManager->getRepository(AdminDailyAnalysis::class);
        $query = $dailyAnalysisRepo->createQueryBuilder('da')
            ->where('da.date IN (:dates) AND da.type = :type')
            ->setParameter('dates', $dates)
            ->setParameter('type', $type)
            ->orderBy('da.date')
            ->getQuery();
        $recordedResult = $query->getResult();
        $recordedDates = [];
        if (null !== $recordedResult) {
            $recordedDates = array_map(function($item) {
                return $item->date->format('Y-m-d');
            }, $recordedResult);
        }
        $unrecordedDates = array_diff($dates, $recordedDates);

        return array($unrecordedDates, $recordedResult);
    }

    /**
     * @param \DateTime $dateTime
     * @return string
     */
    protected function dateFormat(\DateTime $dateTime)
    {
        return $dateTime->format('Y-m-d');
    }

    /**
     * @param EntityManager $entityManager
     * @param $dailyCount
     * @param $recordedResult
     */
    private function updateRecordedDates(EntityManager $entityManager, $dailyCount, $recordedResult)
    {
        foreach ($recordedResult as $row) {
            $date = $this->dateFormat($row->date);
            if ($row->dailyCount != $dailyCount[$date][0] || $row->totalCount != $dailyCount[$date][1]) {
                $row->dailyCount = $dailyCount[$date][0];
                $row->totalCount = $dailyCount[$date][1];
            }
        }
        $entityManager->flush();
    }

    /**
     * @param EntityManager $entityManager
     * @param $type
     * @param $dailyCount
     * @param $unrecordedDates
     */
    private function insertUnrecordedDates(EntityManager $entityManager, $type, $dailyCount, $unrecordedDates)
    {
        foreach ($unrecordedDates as $date) {
            $dailyCountEntity = new AdminDailyAnalysis();
            $dailyCountEntity->type = $type;
            $dailyCountEntity->date = new \DateTime($date);
            $dailyCountEntity->dailyCount = $dailyCount[$date][0];
            $dailyCountEntity->totalCount = $dailyCount[$date][1];
            $entityManager->persist($dailyCountEntity);
        }
        $entityManager->flush();
    }

    /**
     * @param EntityManager $entityManager
     * @param \DateTime $latestDate
     */
    public function contentContribution(EntityManager $entityManager, \DateTime $latestDate, OutputInterface $output = null)
    {
        gc_enable();
        $postCursor = $this->post()->getCursorAfterCreateTime($latestDate);
        $postIterator = $this->post()->iteratePost();
        $postIterator->setCursor($postCursor)->setStep(1000);

        $dailyAuthors = [];
        $output !== null && $output->writeln('Start to iterate posts');
        while ($postIterator->valid()) {
            $resultSet = $postIterator->current();
            foreach ($resultSet as $row) {
                $createDate = $this->dateFormat($row->createTime);
                if (isset($dailyAuthors[$createDate])) {
                    if (!in_array($row->authorId, $dailyAuthors[$createDate])) {
                        $dailyAuthors[$createDate][] = $row->authorId;
                    }
                } else {
                    $dailyAuthors[$createDate][] = $row->authorId;
                }
            }
            $postIterator->next();
            $entityManager->clear();
            gc_collect_cycles();
        }

        $output !== null && $output->writeln('Start to save');
        $dates = array_keys($dailyAuthors);
        list($unrecordedDates, $recordedResult) = $this->splitDates($entityManager, AnalysisType::CONTENT_CONTRIBUTION, $dates);
        foreach ($unrecordedDates as $date) {
            $output !== null && $output->writeln(sprintf("Insert Date: %s", $date));
            // Insert
            $dailyCountEntity = new AdminDailyAnalysis();
            $dailyCountEntity->type = AnalysisType::CONTENT_CONTRIBUTION;
            $dailyCountEntity->date = new \DateTime($date);
            $dailyCountEntity->dailyCount = count($dailyAuthors[$date]);
            $dailyCountEntity->totalCount = 0;
            $entityManager->persist($dailyCountEntity);
        }
        foreach ($recordedResult as $row) {
            // Update
            foreach ($dailyAuthors as $date=>$authorIds) {
                $latestCount = count($authorIds);
                if ($this->dateFormat($row->date) === $date && $row->dailyCount != $latestCount) {
                    $output !== null && $output->writeln(sprintf("Update Date: %s", $date));
                    $row->dailyCount = $latestCount;
                }
            }
        }
        $entityManager->flush();
    }
}