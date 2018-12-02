<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 8/17/15
 * Time: 4:44 PM
 */

namespace Lychee\Module\Recommendation;


use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Recommendation\Entity\Column;
use Lychee\Module\Recommendation\Entity\ColumnElement;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ColumnManagement {

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    public function __construct(RegistryInterface $doctrineRegistry, $entityManagerName) {
        $this->entityManager = $doctrineRegistry->getManager($entityManagerName);
    }

    public function createColumn(Column $column) {
        $this->entityManager->persist($column);
        $this->entityManager->flush();
    }

    public function addElement(ColumnElement $columnElement) {
        $columnElement->setOrder(0);
        $this->entityManager->persist($columnElement);
        $this->entityManager->flush();
        $conn = $this->entityManager->getConnection();
        $stmt = $conn->prepare('UPDATE column_element SET `order` = `order` + 1 WHERE column_id = :columnId');
        $stmt->bindValue(':columnId', $columnElement->getColumnId());
        $stmt->execute();
    }

    public function fetchColumns($count, $page = 1, $published = true, $deleted = false) {
        $qb = $this->entityManager->getRepository(Column::class)->createQueryBuilder('c');
        if ($published !== null) {
            $qb->where('c.published = :published')->setParameter('published', $published);
        }
        if ($deleted !== null) {
            $qb->andWhere('c.deleted = :deleted')->setParameter('deleted', $deleted);
        }
        $query = $qb->addOrderBy('c.order', 'ASC')
            ->addOrderBy('c.createTime', 'DESC')
            ->setFirstResult(($page - 1) * $count)
            ->setMaxResults($count)
            ->getQuery();
        $result = $query->getResult();

        return $result;
    }

    public function fetchElements($columnId, $count, $page = 1) {
        $result = $this->entityManager->getRepository(ColumnElement::class)
            ->findBy([
                'columnId' => $columnId,
            ], ['order' => 'ASC'], $count, ($page - 1) * $count);

        return $result;
    }

    public function fetchAllElements($columnId) {
        return $this->entityManager->getRepository(ColumnElement::class)
            ->findBy([
                'columnId' => $columnId,
            ], ['order' => 'ASC']);
    }

    public function fetchAllPublishedColumns() {
        return $this->entityManager->getRepository(Column::class)
            ->findBy([
                'published' => true,
                'deleted' => false,
            ], ['order' => 'ASC']);
    }

    /**
     * 按类型返回栏目列表
     * @param $type
     * @return array
     */
    public function fetchPublishedColumns($type) {
        return $this->entityManager->getRepository(Column::class)
            ->findBy([
                'type' => $type,
                'published' => true,
                'deleted' => false,
            ], ['order' => 'ASC']);
    }

    /**
     * @param $columnId
     * @return null|\Lychee\Module\Recommendation\Entity\Column
     */
    public function fetchColumnById($columnId) {
        return $this->entityManager->getRepository(Column::class)->find($columnId);
    }

    public function updateColumn($column) {
        $this->entityManager->flush($column);
    }

    public function removeColumn($columnId) {
        /**
         * @var \Lychee\Module\Recommendation\Entity\Column $column
         */
        $column = $this->entityManager->getRepository(Column::class)->find($columnId);
        if (null !== $column) {
            $column->setDeleted(true);
            $column->setPublished(false);
            $this->entityManager->flush();
        }
    }

    public function recoverColumn($columnId) {
        /**
         * @var \Lychee\Module\Recommendation\Entity\Column $column
         */
        $column = $this->entityManager->getRepository(Column::class)->find($columnId);
        if (null !== $column) {
            $column->setDeleted(false);
            $column->setPublished(false);
            $this->entityManager->flush();
        }
    }

    public function publishColumn($columnId) {
        /**
         * @var \Lychee\Module\Recommendation\Entity\Column $column
         */
        $column = $this->entityManager->getRepository(Column::class)->find($columnId);
        if (null !== $column) {
            $column->setPublished(true);
            $column->setOrder(0);
            $this->entityManager->flush();
            $conn = $this->entityManager->getConnection();
            $stmt = $conn->prepare(
                'UPDATE `column` SET `order` = `order` + 1 WHERE type="' . $column->getType()
                . '" AND published = 1 AND deleted = 0'
            );
            $stmt->execute();
        }
    }

    public function unpublishColumn($columnId) {
        /**
         * @var \Lychee\Module\Recommendation\Entity\Column $column
         */
        $column = $this->entityManager->getRepository(Column::class)->find($columnId);
        if (null !== $column) {
            $column->setPublished(false);
            $this->entityManager->flush();
        }
    }

    public function removeElement($columnId, $elementId) {
        $element = $this->entityManager->getRepository(ColumnElement::class)
            ->findOneBy([
                'columnId' => $columnId,
                'elementId' => $elementId,
            ]);
        if (null !== $element) {
            $this->entityManager->remove($element);
            $this->entityManager->flush();
        }
    }

    public function reorderElements($elements) {
        if (is_array($elements)) {
//            usort($elements, function($a, $b) {
//                if ($a->getOrder() > $b->getOrder()) {
//                    return 1;
//                } elseif ($a->getOrder() === $b->getOrder()) {
//                    if ($a->getCreateTime() > $b->getCreateTime()) {
//                        return 1;
//                    } else {
//                        return -1;
//                    }
//                } else {
//                    return -1;
//                }
//            });
//            $index = 1;
//            foreach ($elements as $element) {
//                $element->setOrder($index);
//                $index += 1;
//            }
            $index = 1;
            foreach ($elements as $element) {
                $element->setOrder($index);
                $index += 1;
            }
            $this->entityManager->flush();
        }
    }

    public function orderElements($columnId, $elementIds) {
        $elements = $this->fetchAllElements($columnId);
        $elements = ArrayUtility::mapByColumn($elements, 'elementId');
        $index = 1;
        foreach ($elementIds as $id) {
            $elements[$id]->setOrder($index);
            $index += 1;
        }
        $this->entityManager->flush();
    }

    public function reorderColumns() {
        $columns = $this->fetchAllPublishedColumns();
        $index = 1;
        foreach ($columns as $column) {
            $column->setOrder($index);
            $index += 1;
        }
        $this->entityManager->flush();
    }

    public function orderColumns($columnIds, $type = Column::TYPE_POST) {
        $columns = $this->fetchPublishedColumns($type);
        $columns = ArrayUtility::mapByColumn($columns, 'id');
        $index = 1;
        foreach ($columnIds as $id) {
            $columns[$id]->setOrder($index);
            $index += 1;
        }
        $this->entityManager->flush();
    }

    public function fetchOneColumn($id) {
        return $this->entityManager->getRepository(Column::class)->find($id);
    }

    public function getColumnCount($deleted, $published = null) {
        $qb = $this->entityManager->getRepository(Column::class)->createQueryBuilder('c');
        $qb->select('COUNT(c.id) column_count')
            ->where('c.deleted = :deleted')
            ->setParameter('deleted', $deleted);
        if (is_bool($published)) {
            $qb->andWhere('c.published = :published')->setParameter('published', $published);
        }
        $query = $qb->getQuery();
        $result = $query->getOneOrNullResult();

        return $result['column_count'];
    }

}