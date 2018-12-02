<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 3/20/15
 * Time: 4:05 PM
 */

namespace Lychee\Module\Recommendation;

use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Recommendation\Entity\RecommendationItem;
use Lychee\Module\Recommendation\Entity\SpecialSubjectRelation;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Lychee\Module\Recommendation\Entity\SpecialSubject;

/**
 * Class SpecialSubjectManagement
 * @package Lychee\Module\Recommendation
 */
class SpecialSubjectManagement {

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @param RegistryInterface $doctrineRegistry
     * @param $entityManagerName
     */
    public function __construct(RegistryInterface $doctrineRegistry, $entityManagerName) {
        $this->entityManager = $doctrineRegistry->getManager($entityManagerName);
    }

    /**
     * @param $banner
     * @param $description
     * @return SpecialSubject
     */
    public function add($name, $banner, $description) {
        $specialSubject = new SpecialSubject();
        $specialSubject->setName($name);
        $specialSubject->setBanner($banner);
        $specialSubject->setDescription($description);
        $this->entityManager->persist($specialSubject);
        $this->entityManager->flush();

        return $specialSubject;
    }

    /**
     * @param $ids
     * @return array
     */
    public function fetch($ids) {
        $specialSubjectRepo = $this->entityManager->getRepository(SpecialSubject::class);
        $query = $specialSubjectRepo->createQueryBuilder('s')
            ->where('s.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery();
        $result = $query->getResult();
        $sortResult = [];
        if (null !== $result) {
            $result = ArrayUtility::mapByColumn($result, 'id');
            foreach ($ids as $id) {
                if (isset($result[$id])) {
                    $sortResult[$id] = $result[$id];
                }
            }
        }

        return $sortResult;
    }

    /**
     * @param $id
     * @return null|\Lychee\Module\Recommendation\Entity\SpecialSubject
     */
    public function fetchOne($id) {
        return $this->entityManager->getRepository(SpecialSubject::class)->find($id);
    }

    /**
     * @param SpecialSubject $specialSubject
     * @param null $postIds
     * @param null $topicIds
     * @param null $userIds
     */
    public function addAssociated(SpecialSubject $specialSubject, $postIds = null, $topicIds = null, $userIds = null) {
        $em = $this->entityManager;
        if (is_array($postIds)) {
            foreach ($postIds as $id) {
                $relation = new SpecialSubjectRelation();
                $relation->setType(SpecialSubjectRelation::TYPE_POST);
                $relation->setSpecialSubject($specialSubject);
                $relation->setAssociatedId($id);
                $em->persist($relation);
            }
        }
        if (is_array($topicIds)) {
            foreach ($topicIds as $id) {
                $relation = new SpecialSubjectRelation();
                $relation->setType(SpecialSubjectRelation::TYPE_TOPIC);
                $relation->setSpecialSubject($specialSubject);
                $relation->setAssociatedId($id);
                $em->persist($relation);
            }
        }
        if (is_array($userIds)) {
            foreach ($userIds as $id) {
                $relation = new SpecialSubjectRelation();
                $relation->setType(SpecialSubjectRelation::TYPE_USER);
                $relation->setSpecialSubject($specialSubject);
                $relation->setAssociatedId($id);
                $em->persist($relation);
            }
        }
        $em->flush();
    }

    /**
     * @param $id
     */
    public function delete($id) {
        $specialSubjectRepo = $this->entityManager->getRepository(SpecialSubject::class);
        $specialSubject = $specialSubjectRepo->find($id);
        if (null !== $specialSubject) {
            $relationRepo = $this->entityManager->getRepository(SpecialSubjectRelation::class);
            $relations = $relationRepo->findBy([
                'specialSubject' => $specialSubject
            ]);
            foreach ($relations as $row) {
                $this->entityManager->remove($row);
            }
            $this->entityManager->remove($specialSubject);
            $this->entityManager->flush();
        }
    }

    /**
     * @param $page
     * @param int $count
     * @return array
     */
    public function fetchByPage($page, $count = 15) {
        $items = $this->entityManager->getRepository(RecommendationItem::class)
            ->findBy([
                'type' => RecommendationType::SPECIAL_SUBJECT
            ], [
                'id' => 'DESC',
            ], $count, ($page - 1) * $count);
        $specialSubjectIds = array_map(function ($item) {
            return $item->getTargetId();
        }, $items);
        $specialSubjects = $this->fetch($specialSubjectIds);
        $resultSet = array_reduce($items, function($result, $item) use ($specialSubjects) {
            is_array($result) || $result = [];
            foreach ($specialSubjects as $ss) {
                if ($ss->getId() == $item->getTargetId()) {
                    $result[] = $ss;
                    break;
                }
            }

            return $result;
        });

        return $resultSet;
    }

    /**
     * @param $cursor
     * @param $count
     * @param null $nextCursor
     * @return array
     */
    public function fetchByCursor($cursor, $count, &$nextCursor = null) {
        if ($count === 0) {
            return array();
        }
        if ($cursor === 0) {
            $cursor = PHP_INT_MAX;
        }
//        $recommendationItem = $this->entityManager->getRepository(RecommendationItem::class)->findOneBy(array(
//            'targetId' => $cursor,
//        ));
//        if ($recommendationItem) {
//            $cursor = $recommendationItem->getId();
//        } else {
//            $cursor = PHP_INT_MAX;
//        }

        $query = $this->entityManager->createQuery('
            SELECT ri
            FROM Lychee\Module\Recommendation\Entity\RecommendationItem ri
            WHERE ri.id < :cursor AND ri.type = :recommendationType
            ORDER BY ri.id DESC
        ')->setMaxResults($count);
        $query->setParameters(array('recommendationType' => RecommendationType::SPECIAL_SUBJECT, 'cursor' => $cursor));
        $result = $query->getArrayResult();
        $items = ArrayUtility::columns($result, 'targetId');

        if (count($items) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $result[count($result) - 1]['id'];
        }

        return $items;
    }

    public function fetchSpecialSubjectByCursor($cursor, $count, &$nextCursor = null) {
        if (0 == $count) {
            return [];
        }
        if (0 == $cursor) {
            $cursor = PHP_INT_MAX;
        }
        $query = $this->entityManager->createQuery('
            SELECT ss
            FROM Lychee\Module\Recommendation\Entity\SpecialSubject ss
            WHERE ss.id < :cursor
            ORDER BY ss.id DESC
        ')->setMaxResults($count);
        $query->setParameter('cursor', $cursor);
        $result = $query->getArrayResult();
        $specialSubjects = ArrayUtility::mapByColumn($result, 'id');
        if (count($specialSubjects) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $result[count($result) - 1]['id'];
        }

        return $specialSubjects;
    }

    /**
     * @param $id
     * @return array
     */
    public function fetchRelation($id) {
        return $this->entityManager->getRepository(SpecialSubjectRelation::class)
            ->findBy([
                'specialSubjectId' => $id
            ]);
    }

    /**
     * @param $id
     * @return bool|SpecialSubject|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function fetchPrevious($id) {
        $specialSubject = $this->entityManager->getRepository(SpecialSubject::class)->findOneBy([
            'id' => $id
        ]);
        if (null === $specialSubject) {
            return false;
        }
        $itemRepo = $this->entityManager->getRepository(RecommendationItem::class);
        $item = $itemRepo->findOneBy([
            'targetId' => $specialSubject->getId(),
        ]);
        if (null === $item) {
            return false;
        }

        $query = $itemRepo
            ->createQueryBuilder('i')
            ->where('i.id > :id AND i.type = :type')
            ->setParameter('id', $item->getId())
            ->setParameter('type', RecommendationType::SPECIAL_SUBJECT)
            ->setMaxResults(1)
            ->getQuery();

        $previousItem = $query->getOneOrNullResult();
        if (null === $previousItem) {
            return false;
        }
        $previousSpecialSubjectId = $previousItem->getTargetId();

        return $this->fetchOne($previousSpecialSubjectId);
    }

    /**
     * @param $id
     * @return bool|SpecialSubject|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function fetchNext($id) {
        $specialSubject = $this->entityManager->getRepository(SpecialSubject::class)->findOneBy([
            'id' => $id
        ]);
        if (null === $specialSubject) {
            return false;
        }
        $itemRepo = $this->entityManager->getRepository(RecommendationItem::class);
        $item = $itemRepo->findOneBy([
            'targetId' => $specialSubject->getId(),
        ]);
        if (null === $item) {
            return false;
        }

        $query = $itemRepo
            ->createQueryBuilder('i')
            ->where('i.id < :id AND i.type = :type')
            ->orderBy('i.id', 'DESC')
            ->setParameter('id', $item->getId())
            ->setParameter('type', RecommendationType::SPECIAL_SUBJECT)
            ->setMaxResults(1)
            ->getQuery();

        $previousItem = $query->getOneOrNullResult();
        if (null === $previousItem) {
            return false;
        }
        $previousSpecialSubjectId = $previousItem->getTargetId();

        return $this->fetchOne($previousSpecialSubjectId);
    }

}