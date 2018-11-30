<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14/11/14
 * Time: 下午5:48
 */

namespace Lychee\Component\Foundation;


use Doctrine\ORM\EntityManager;
use Lychee\Component\Database\DoctrineUtility;
use Lychee\Component\Foundation\CursorableIterator\QueryCursorableIterator;

/**
 * Class IteratorTrait
 * @package Lychee\Module
 */
trait IteratorTrait {

    /**
     * 按创建时间顺序迭代实体数据
     * @param EntityManager $entityManager
     * @param $repository
     * @param string $fieldName
     * @param string $order
     * @return QueryCursorableIterator
     */
    protected function iterateEntity(EntityManager $entityManager, $repository, $fieldName = 'id', $order = 'ASC')
    {
        $repo = $entityManager->getRepository($repository);
        $qb = $repo->createQueryBuilder('repo');
        if ('ASC' === $order) {
            $qb->where("repo.$fieldName > :cursor");
        } else {
            $qb->where("repo.$fieldName < :cursor");
            $order = 'DESC';
        }
        $query = $qb->orderBy("repo.$fieldName", $order)->getQuery();

        return new QueryCursorableIterator($query, $fieldName);
    }

    protected function iterateEntityByKeyword(EntityManager $entityManager, $repository, $keyword, $keywordColumnName, $fieldName = 'id', $order = 'ASC')
    {
        $repo = $entityManager->getRepository($repository);
        $qb = $repo->createQueryBuilder('repo');
        $qb->where("repo.$keywordColumnName LIKE :keyword")
            ->setParameter('keyword', '%'.$keyword.'%');
        if ('ASC' === $order) {
            $qb->andWhere("repo.$fieldName > :cursor");
        } else {
            $qb->andWhere("repo.$fieldName < :cursor");
            $order = 'DESC';
        }
        $query = $qb->orderBy("repo.$fieldName", $order)->getQuery();

        return new QueryCursorableIterator($query, $fieldName);
    }

	/**
	 * @param EntityManager $entityManager
	 * @param $entityName
	 * @param $idColumnName
	 * @param $timeColumnName
	 * @param \DateTime $createTime
	 *
	 * @return QueryCursorableIterator
	 */
    protected function iterateEntityByCreateTime(
    	EntityManager $entityManager,
	    $entityName,
	    $idColumnName,
	    $timeColumnName,
	    \DateTime $createTime
    ) {
    	$date = clone $createTime;
    	$date->modify('-1 second');
    	$maxId = DoctrineUtility::getMaxIdWithTime($entityManager, $entityName, $idColumnName, $timeColumnName, $date);
        $repo = $entityManager->getRepository($entityName);
        $query = $repo->createQueryBuilder('repo')
            ->where("repo.$idColumnName > :cursor")
            ->orderBy("repo.$idColumnName")
            ->getQuery();

        $iterator = new QueryCursorableIterator($query, $idColumnName);
	    $iterator->setCursor($maxId);
	    $iterator->setStep(2000);

	    return $iterator;
    }
}