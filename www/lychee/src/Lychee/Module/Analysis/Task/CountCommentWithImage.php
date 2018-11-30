<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14/11/7
 * Time: 下午7:00
 */

namespace Lychee\Module\Analysis\Task;


use Lychee\Component\Task\Task;
use Lychee\Module\Analysis\AnalysisType;

/**
 * Class CountCommentWithImage
 * @package Lychee\Module\Analysis\Task
 */
class CountCommentWithImage implements Task
{
    use CounterTaskTrait;

    /**
     * @var string
     */
    private $analysisType = AnalysisType::IMAGE_COMMENT;

    /**
     * @return string
     */
    public function getName()
    {
        return 'count image comment';
    }

    /**
     *
     */
    public function run()
    {
        $entityManager = $this->container()->get('doctrine')->getManager();
        $latestResult = $this->getLastCountDate($entityManager, $this->analysisType);
        if (null !== $latestResult) {
            $iterator = $this->comment()->iterateCommentWithImage($latestResult->date);
            $this->dailyAnalysis(
                $entityManager,
                $iterator,
                $this->analysisType,
                $latestResult->totalCount - $latestResult->dailyCount
            );
        }
    }
}