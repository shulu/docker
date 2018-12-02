<?php
namespace Lychee\Module\Recommendation\Task;

use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Database\DoctrineUtility;
use Lychee\Component\Task\Task;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

abstract class SettleTask implements Task {
    use ContainerAwareTrait;
    use ModuleAwareTrait;

    /**
     * @return EntityManager
     */
    protected function em() {
        return $this->container->get('doctrine')->getManager();
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger() {
        return $this->container->get('monolog.logger.task');
    }

    /**
     * @param string $class
     * @param string $idName
     * @param string $timeName
     * @param \DateTime $fromTime
     * @param \DateTime $toTime
     *
     * @return array [$minId, $maxId]
     */
    protected function getIdRangeInPeriod($class, $idName, $timeName, $fromTime, $toTime) {
        $maxId = DoctrineUtility::getMaxIdWithTime($this->em(), $class, $idName, $timeName, $toTime);
        if ($maxId === null) {
            return array(0, 0);
        }
        $minId = DoctrineUtility::getMaxIdWithTime($this->em(), $class, $idName, $timeName, $fromTime, $maxId);
        if ($minId === null) {
            return array(0, $maxId);
        }

        return array($minId, $maxId);
    }

    /**
     * @return \DateTime
     */
    protected function getSettleUpperTime() {
        $upperTime = new \DateTime();
        $upperTime->sub(new \DateInterval('PT1S'));
        return $upperTime;
    }

    /**
     * @param string $class
     * @param string $idName
     * @param string $timeName
     *
     * @return array [$minId, $maxId]
     */
    protected function getSettleIdRange($class, $idName, $timeName) {
        $upperTime = $this->getSettleUpperTime();
        $maxId = DoctrineUtility::getMaxIdWithTime($this->em(), $class, $idName, $timeName, $upperTime);
        if ($maxId === null) {
            return array(0, 0);
        }

        $upperTime = DoctrineUtility::getTimeWithId($this->em(), $class, $idName, $timeName, $maxId);
        if ($upperTime === null) {
            return array(0, 0);
        }

        $lowerTime = clone $upperTime;
        $lowerTime->sub($this->getSettleInterval());

        $minId = DoctrineUtility::getMaxIdWithTime($this->em(), $class, $idName, $timeName, $lowerTime, $maxId);
        $lowerTime = DoctrineUtility::getTimeWithId($this->em(), $class, $idName, $timeName, $minId);

        $upperTimeString = $upperTime->format('Y-m-d H:i:s');
        $lowerTimeString = $lowerTime->format('Y-m-d H:i:s');

        $this->getLogger()->info("$class [{$minId}({$lowerTimeString}) - {$maxId}({$upperTimeString})]");

        return array($minId, $maxId);
    }

    /**
     * @return \DateInterval
     */
    abstract public function getSettleInterval();
}