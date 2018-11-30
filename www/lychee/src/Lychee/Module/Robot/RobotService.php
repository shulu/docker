<?php
namespace Lychee\Module\Robot;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Lychee\Component\Foundation\ArrayUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RobotService {
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var ContainerInterface
     */
    private $serviceContainer;
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct($registry, $serviceContainer) {
        $this->entityManager = $registry->getManager();
        $this->serviceContainer = $serviceContainer;
    }


    /**
     * 导入机器人
     *
     * @param $robotIds
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function import($robotIds)
    {
        $step = 1000;
        $inserts = [];
        $maxCount = count($robotIds);
        $total = 0;
        $cursor = 0;
        foreach ($robotIds as $robotId) {
            $robotId = intval($robotId);
            $cursor ++;
            if (empty($robotId)) {
                continue;
            }
            $inserts[] = '('.$robotId.')';
            if (0==$cursor%$step
                || $cursor==$maxCount) {
                $sql = 'INSERT IGNORE INTO robot (id) VALUES '.implode(',', $inserts);
                $inserts = [];
                $r = $this->entityManager->getConnection()->executeUpdate($sql);
                $total += $r;
                usleep(200000);
            }
        }
        return $total;
    }


}