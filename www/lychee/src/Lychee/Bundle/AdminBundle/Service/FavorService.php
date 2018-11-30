<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 5/18/15
 * Time: 4:03 PM
 */

namespace Lychee\Bundle\AdminBundle\Service;


use Doctrine\Common\Persistence\ManagerRegistry;
use Lychee\Bundle\AdminBundle\Entity\Favor;
use Lychee\Component\Foundation\CursorableIterator\QueryCursorableIterator;

class FavorService {

    private $entityManager;

    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository
     */
    private $repository;

    public function __construct(ManagerRegistry $registry, $entityManagerName = null) {
        $this->entityManager = $registry->getManager($entityManagerName);
        $this->repository = $this->entityManager->getRepository(Favor::class);
    }

    public function hasPost($postId) {
        $favor = $this->repository->findOneBy([
            'postId' => $postId
        ]);
        if (null === $favor) {
            return false;
        }
        return true;
    }

    public function add($postId) {
        $favor = new Favor();
        $favor->setPostId($postId);
        $this->entityManager->persist($favor);
        $this->entityManager->flush();

        return $favor;
    }

    public function removeByPost($postId) {
        $favor = $this->repository->findOneBy([
            'postId' => $postId,
        ]);
        $this->entityManager->remove($favor);
        $this->entityManager->flush();

        return true;
    }

    public function iterator($cursor, $count = 20) {
        if (!$cursor) {
            $cursor = PHP_INT_MAX;
        }
        $query = $this->repository->createQueryBuilder('f')
            ->where('f.id < :cursor')
            ->orderBy('f.id', 'DESC')
            ->getQuery();

        $iterator = new QueryCursorableIterator($query, 'postId');
        $iterator->setCursor((int)$cursor)->setStep($count);

        return $iterator->current();
    }

    public function filterFavorPostIds($postIds) {
        $favors = $this->repository->findBy([
            'postId' => $postIds,
        ]);
        $favorPostIds = array_map(function ($item) {
            return $item->getPostId();
        }, $favors);

        return $favorPostIds;
    }

    public function fetchFavorIds($postIds) {
        $favors = $this->repository->findBy([
            'postId' => $postIds,
        ]);
        $result = [];
        foreach ($favors as $favor) {
            $result[$favor->getPostId()] = $favor->getId();
        }

        return $result;
    }

}