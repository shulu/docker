<?php
namespace Lychee\Component\Database;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;

class DoctrineUtility {

    /**
     * @param EntityManagerInterface $em
     * @param string $entityName
     * @param string $timeColumnName
     * @param \DateTimeInterface $time
     * @return \DateTimeInterface|int
     */
    static private function getTimeValue($em, $entityName, $timeColumnName, $time) {
        $meta = $em->getClassMetadata($entityName);
        $timeType = $meta->getTypeOfField($meta->getFieldName($timeColumnName));
        return $timeType == 'datetime' ? $time : $time->getTimestamp();
    }

    /**
     * @param EntityManagerInterface $em
     * @param string $entityName
     * @param string $idColumnName
     * @param string $timeColumnName
     * @param \DateTimeInterface $time
     * @param int $lowerId
     *
     * @return int|null
     */
    static public function getMinIdWithTime($em, $entityName, $idColumnName, $timeColumnName, $time, $lowerId = 0) {
        $query = $em->createQuery('SELECT t.'.$idColumnName.' FROM '.$entityName.' t WHERE t.'.$idColumnName.' > :lowerId AND t.'.$timeColumnName.' >= :time ORDER BY t.'.$idColumnName.' ASC');
        $query->setMaxResults(1);
        $query->setParameters(array(
            'lowerId' => $lowerId,
            'time' => self::getTimeValue($em, $entityName, $timeColumnName, $time)
        ));
        try {
            $minId = $query->getSingleScalarResult();
            return $minId;
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param EntityManagerInterface $em
     * @param string $entityName
     * @param string $idColumnName
     * @param string $timeColumnName
     * @param \DateTimeInterface $time
     * @param int $upperId
     *
     * @return int|null
     */
    static public function getMaxIdWithTime($em, $entityName, $idColumnName, $timeColumnName, $time, $upperId = PHP_INT_MAX) {
        $query = $em->createQuery('SELECT t.'.$idColumnName.' FROM '.$entityName.' t WHERE t.'.$idColumnName.' < :upperId AND t.'.$timeColumnName.' <= :time ORDER BY t.'.$idColumnName.' DESC');
        $query->setMaxResults(1);
        $query->setParameters(array(
            'upperId' => $upperId,
            'time' => self::getTimeValue($em, $entityName, $timeColumnName, $time)
        ));
        try {
            $maxId = $query->getSingleScalarResult();
            return $maxId;
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param EntityManagerInterface $em
     * @param string $entityName
     * @param string $idColumnName
     * @param string $timeColumnName
     * @param string $id
     *
     * @return \DateTime|null
     */
    static public function getTimeWithId($em, $entityName, $idColumnName, $timeColumnName, $id) {
        $query = $em->createQuery('SELECT t.'.$timeColumnName.' FROM '.$entityName.' t WHERE t.'.$idColumnName.' = :id');
        $query->setParameters(array('id' => $id));
        try {
            $time = $query->getSingleResult()[$timeColumnName];
            if (!($time instanceof \DateTimeInterface)) {
                $time = new \DateTime('@'.$time);
            }
            return $time;
        } catch (NoResultException $e) {
            return null;
        }
    }

}