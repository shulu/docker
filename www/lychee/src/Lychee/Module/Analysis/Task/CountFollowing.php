<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14/11/7
 * Time: 下午7:00
 */

namespace Lychee\Module\Analysis\Task;


use Doctrine\ORM\EntityManager;
use Lychee\Component\Database\DoctrineUtility;
use Lychee\Component\GraphStorage\Doctrine\AbstractFollowing;
use Lychee\Component\Task\Task;
use Lychee\Module\Analysis\AnalysisType;
use Lychee\Module\Analysis\Entity\AdminDailyAnalysis;
use Lychee\Module\Like\Entity\CommentLike;
use Lychee\Module\Relation\Entity\UserFollowing;

/**
 * Class CountFollowing
 * @package Lychee\Module\Analysis\Task
 */
class CountFollowing implements Task
{
    use CounterTaskTrait;

    /**
     * @var string
     */
    private $analysisType = AnalysisType::FOLLOWING;

    /**
     * @return string
     */
    public function getName()
    {
        return 'count following';
    }

    /**
     *
     */
    public function run() {
	    /** @var EntityManager $entityManager */
	    $entityManager = $this->container()->get('doctrine')->getManager();
	    /** @var AdminDailyAnalysis|null $latestResult */
	    $latestResult = $this->getLastCountDate($entityManager, $this->analysisType);
	    if ($latestResult) {
		    $startDate = clone $latestResult->date;
		    $startDate->modify('+1 day');
	    } else {
		    $startDate = new \DateTime('yesterday');
	    }
	    $endDate = new \DateTime('today');
	    $midDate = clone $startDate;
	    $midDate->modify('+1 day');
	    $repo = $entityManager->getRepository(UserFollowing::class);
	    $previousTotalCount = $latestResult->totalCount;
	    while ($midDate <= $endDate) {
		    $query = $repo->createQueryBuilder('uf')
		                  ->select('COUNT(uf.id) following_count')
		                  ->where('uf.updateTime>=:startDate')
		                  ->andWhere('uf.updateTime<:midDate')
		                  ->andWhere('uf.state=:state')
		                  ->orderBy('uf.updateTime', 'DESC')
		                  ->setParameter(':startDate', $startDate)
		                  ->setParameter(':midDate', $midDate)
		                  ->setParameter(':state', AbstractFollowing::STATE_NORMAL)
		                  ->getQuery();
		    $result = $query->getOneOrNullResult();
		    $count = $result? $result['following_count'] : 0;
		    $analysis = new AdminDailyAnalysis();
		    $analysis->type = $this->analysisType;
		    $analysis->date = $startDate;
		    $analysis->dailyCount = $count;
		    $analysis->totalCount = $count + $previousTotalCount;
		    printf("[%s] Count: %s\tTotal: %s\n",$analysis->date->format('Y-m-d'), $analysis->dailyCount, $analysis->totalCount);
		    $entityManager->persist($analysis);
		    $entityManager->flush();
		    $previousTotalCount = $analysis->totalCount;
		    $startDate->modify('+1 day');
		    $midDate->modify('+1 day');
	    }
    }
}