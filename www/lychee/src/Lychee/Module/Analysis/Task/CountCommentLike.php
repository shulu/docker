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
use Lychee\Component\Task\Task;
use Lychee\Module\Analysis\AnalysisType;
use Lychee\Module\Analysis\Entity\AdminDailyAnalysis;
use Lychee\Module\Like\Entity\CommentLike;
use Lychee\Module\Like\LikeState;

/**
 * Class CountCommentLike
 * @package Lychee\Module\Analysis\Task
 */
class CountCommentLike implements Task
{
    use CounterTaskTrait;

    /**
     * @var string
     */
    private $analysisType = AnalysisType::COMMENT_LIKE;

    /**
     * @return string
     */
    public function getName()
    {
        return 'count comment like';
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
	    $repo = $entityManager->getRepository(CommentLike::class);
	    $previousTotalCount = $latestResult->totalCount;
	    while ($midDate <= $endDate) {
		    $query = $repo->createQueryBuilder('cl')
			    ->select('COUNT(cl.id) comment_like_count')
				->where('cl.updateTime>=:startDate')
				->andWhere('cl.updateTime<:midDate')
			    ->andWhere('cl.state=:state')
			    ->orderBy('cl.updateTime', 'DESC')
			    ->setParameter(':startDate', $startDate)
			    ->setParameter(':midDate', $midDate)
			    ->setParameter(':state', LikeState::NORMAL)
			    ->getQuery();
		    $result = $query->getOneOrNullResult();
		    $count = $result? $result['comment_like_count'] : 0;
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