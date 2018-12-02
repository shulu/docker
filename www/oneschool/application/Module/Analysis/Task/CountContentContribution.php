<?php

namespace Lychee\Module\Analysis\Task;


use Lychee\Component\Task\Task;
use Lychee\Module\Analysis\AnalysisType;

/**
 * Class CountContentContribution
 * @package Lychee\Module\Analysis\Task
 */
class CountContentContribution implements Task
{
    use CounterTaskTrait;

    /**
     * @var string
     */
    private $analysisType = AnalysisType::CONTENT_CONTRIBUTION;

    /**
     * @return string
     */
    public function getName()
    {
        return 'count content contribution';
    }

    /**
     *
     */
    public function run()
    {
        $entityManager = $this->container()->get('doctrine')->getManager();
        $latestResult = $this->getLastCountDate($entityManager, $this->analysisType);
        if (null !== $latestResult) {
            $this->contentContribution($entityManager, $latestResult->date);
        }
    }
}