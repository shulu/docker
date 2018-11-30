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
 * Class CountPost
 * @package Lychee\Module\Analysis\Task
 */
class CountPost implements Task
{
    use CounterTaskTrait;

    /**
     * @var string
     */
    private $analysisType = AnalysisType::POST;

    /**
     * @return string
     */
    public function getName()
    {
        return 'count post';
    }

    /**
     *
     */
    public function run()
    {
        $entityManager = $this->container()->get('doctrine')->getManager();
        $latestResult = $this->getLastCountDate($entityManager, $this->analysisType);
        if (null !== $latestResult) {
            $iterator = $this->post()->iteratePostByCreateTime($latestResult->date);
            $this->dailyAnalysis(
                $entityManager,
                $iterator,
                $this->analysisType,
                $latestResult->totalCount - $latestResult->dailyCount
            );
        }
    }
}