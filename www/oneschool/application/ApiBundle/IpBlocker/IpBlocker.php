<?php
namespace Lychee\Bundle\ApiBundle\IpBlocker;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Lychee\Bundle\ApiBundle\Entity\IpBlockerRecord;
use Symfony\Bridge\Doctrine\RegistryInterface;

class IpBlocker {

    const MAX_REQUEST_PER_HOUR = 20;
    const MAX_REQUEST_PER_DAY = 40;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * IpBlocker constructor.
     * @param RegistryInterface $registry
     */
    public function __construct($registry) {
        $this->em = $registry->getManager();
    }

    /**
     * @param string $ip
     * @param string $action
     * @param int $time
     * @param int $maxRequestPerDay
     * @param int $maxRequestPerHour
     * @return bool
     * @throws \Exception
     */
    public function checkAndUpdate($ip, $action, $time = null, $maxRequestPerDay = self::MAX_REQUEST_PER_DAY, $maxRequestPerHour = self::MAX_REQUEST_PER_HOUR) {
        $now = $time === null ? time() : $time;
        try {
            $record = $this->lockRow($ip, $action, $now);
            $lastTime = intval($record['last_time']);
            $dayCount = intval($record['day_count']);
            $hourCount = intval($record['hour_count']);
            $version = intval($record['version']);
            if ($now <= $lastTime) {
                if ($dayCount < $maxRequestPerDay && $hourCount < $maxRequestPerHour) {
                    $this->updateRow($ip, $action, $now, $dayCount + 1, $hourCount + 1, $version + 1);
                    return true;
                } else {
                    $this->em->getConnection()->rollBack();
                    return false;
                }
            } else {
                if ($this->canUpdate($now, $lastTime, $dayCount, $hourCount, $maxRequestPerDay, $maxRequestPerHour)) {
                    $this->updateRow($ip, $action, $now, $dayCount + 1, $hourCount + 1, $version + 1);
                    return true;
                } else {
                    $this->em->getConnection()->rollBack();
                    return false;
                }
            }
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }

    }

    private function canUpdate($now, $last, &$dayCount, &$hourCount, $maxRequestPerDay, $maxRequestPerHour) {
        $nowInfo = getdate($now);
        $nowYear = $nowInfo['year'];
        $nowDayOfYear = $nowInfo['yday'];
        $nowHourOfDay = $nowInfo['hours'];

        $lastInfo = getdate($last);
        $lastYear = $lastInfo['year'];
        $lastDayOfYear = $lastInfo['yday'];
        $lastHourOfDay = $lastInfo['hours'];

        if ($nowYear == $lastYear) {
            if ($nowDayOfYear == $lastDayOfYear) {
                if ($dayCount < $maxRequestPerDay) {
                    if ($nowHourOfDay == $lastHourOfDay) {
                        return $hourCount < $maxRequestPerHour;
                    } else {
                        $hourCount = 0;
                        return true;
                    }
                } else {
                    return false;
                }
            }
        }
        $dayCount = 0;
        $hourCount = 0;
        return true;
    }
    
    private function lockRow($ip, $action, $time) {
        $this->em->getConnection()->beginTransaction();
        $lockSql ='SELECT last_time, day_count, hour_count, version FROM ip_blocker_records WHERE ip = ? AND action = ? FOR UPDATE';
        $stat = $this->em->getConnection()->executeQuery($lockSql, array($ip, $action),
            array(\PDO::PARAM_STR, \PDO::PARAM_STR));
        $row = $stat->fetch(\PDO::FETCH_ASSOC);
        if ($row == false) {
            $this->em->getConnection()->rollBack();
            $this->em->getConnection()->beginTransaction();
            try {
                $insertSql = 'INSERT INTO ip_blocker_records(ip, action, last_time, day_count, hour_count, version)'
                    .' VALUES(?, ?, ?, ?, ?, ?)';
                $this->em->getConnection()->executeUpdate($insertSql, array($ip, $action, $time, 0, 0, 0),
                    array(\PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
                $row = array('last_time' => $time, 'day_count' => 0, 'hour_count' => 0, 'version' => 0);
            } catch (UniqueConstraintViolationException $e) {
                $this->em->getConnection()->rollBack();
                $this->em->getConnection()->beginTransaction();
                $stat = $this->em->getConnection()->executeQuery($lockSql, array($ip, $action),
                    array(\PDO::PARAM_STR, \PDO::PARAM_STR));
                $row = $stat->fetch(\PDO::FETCH_ASSOC);
                if ($row == false) {
                    throw new \LogicException("can not reach here.");
                }
            }
        }
        return $row;
    }

    private function updateRow($ip, $action, $time, $dayCount, $hourCount, $version) {
        $sql = 'UPDATE ip_blocker_records SET last_time = ?, day_count = ?, hour_count = ?, version = ? WHERE ip = ? AND action = ?';
        $this->em->getConnection()->executeUpdate($sql, array($time, $dayCount, $hourCount, $version, $ip, $action),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR));
        $this->em->getConnection()->commit();
    }

}