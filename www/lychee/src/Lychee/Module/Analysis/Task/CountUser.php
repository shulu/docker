<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14/11/13
 * Time: 上午11:55
 */

namespace Lychee\Module\Analysis\Task;


use Lychee\Component\Task\Task;
use Lychee\Module\Analysis\AnalysisType;

/**
 * Class CountUser
 * @package Lychee\Module\Analysis\Task
 */
class CountUser implements Task
{
    use CounterTaskTrait;

    /**
     * @var string
     */
    private $analysisType = AnalysisType::USER;

    /**
     * @return string
     */
    public function getName()
    {
        return 'count user';
    }

    /**
     *
     */
    public function run()
    {
        $entityManager = $this->container()->get('doctrine')->getManager();
        $latestResult = $this->getLastCountDate($entityManager, $this->analysisType);
        if (null !== $latestResult) {
            $iterator = $this->account()->iterateAccountByCreateTime($latestResult->date);
            $this->dailyAnalysis(
                $entityManager,
                $iterator,
                $this->analysisType,
                $latestResult->totalCount - $latestResult->dailyCount
            );
        }
    }
}