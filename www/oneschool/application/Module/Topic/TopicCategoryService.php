<?php
namespace Lychee\Module\Topic;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Topic\Entity\TopicCategory;
use Lychee\Module\Topic\Entity\TopicCategoryRel;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManagerInterface;

class TopicCategoryService {

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @param RegistryInterface $registry
     * @param string $emName
     */
    public function __construct($registry, $emName) {
        $this->em = $registry->getManager($emName);
    }

    /**
     * @param string $categoryName
     * @throws Exception\CategoryDuplicatedException
     */
    public function addCategory($categoryName) {
        $category = new TopicCategory();
        $category->name = $categoryName;

        try {
            $this->em->persist($category);
            $this->em->flush($category);
        } catch (UniqueConstraintViolationException $e) {
            throw new Exception\CategoryDuplicatedException();
        }
    }

    /**
     * @param string $categoryName
     * @param int $topicId
     * @throws Exception\CategoryNotFoundException
     */
    public function categoryAddTopic($categoryName, $topicId) {
        /** @var TopicCategory $category */
        $category = $this->em->getRepository(TopicCategory::class)
            ->findOneBy(array('name' => $categoryName));
        if ($category == null) {
            throw new Exception\CategoryNotFoundException();
        } else {
            $sql = 'INSERT INTO topic_category_rel(category_id, topic_id)
              VALUE(?, ?) ON DUPLICATE KEY UPDATE topic_id = VALUES(topic_id)';
            $this->em->getConnection()->executeUpdate($sql, array($category->id, $topicId),
                array(\PDO::PARAM_INT, \PDO::PARAM_INT));
        }
    }

    public function categoryIdAddTopic($categoryId, $topicId) {
        $sql = 'INSERT INTO topic_category_rel(category_id, topic_id)
              VALUE(?, ?) ON DUPLICATE KEY UPDATE topic_id = VALUES(topic_id)';
        $this->em->getConnection()->executeUpdate($sql, array($categoryId, $topicId),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT));
    }

    /**
     * @param string $categoryName
     * @param int $topicId
     * @throws Exception\CategoryNotFoundException
     */
    public function categoryRemoveTopic($categoryName, $topicId) {
        $category = $this->em->getRepository(TopicCategory::class)
            ->findOneBy(array('name' => $categoryName));
        if ($category == null) {
            throw new Exception\CategoryNotFoundException();
        } else {
            $query = $this->em->createQuery(sprintf(
                'DELETE %s r WHERE r.categoryId = :categoryId AND r.topicId = :topicId',
                TopicCategoryRel::class
            ));
            $query->execute(array('categoryId' => $category->id, 'topicId' => $topicId));
        }
    }

    /**
     * @param string $name
     * @return int|null
     */
    public function categoryIdOfName($name) {
        /** @var TopicCategory $category */
        $category = $this->em->getRepository(TopicCategory::class)
            ->findOneBy(array('name' => $name));
        if ($category == null) {
            return null;
        } else {
            return $category->id;
        }
    }

    /**
     * @param string[] $names
     * @return int[]
     */
    public function categoryIdsOfNames($names) {
        /** @var TopicCategory[] $categories */
        $categories = $this->em->getRepository(TopicCategory::class)
            ->findBy(array('name' => $names));
        if (empty($categories)) {
            return array();
        } else {
            return ArrayUtility::columns($categories, 'id');
        }
    }

    /**
     * @param string $categoryName
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     * @return int[]
     * @throws Exception\CategoryNotFoundException
     */
    public function topicIdsInCategory($categoryName, $cursor, $count, &$nextCursor) {
        $category = $this->em->getRepository(TopicCategory::class)
            ->findOneBy(array('name' => $categoryName));
        if ($category == null) {
            throw new Exception\CategoryNotFoundException();
        } else {
            if ($count <= 0) {
                $nextCursor = $cursor;
                return array();
            }

            $query = $this->em->createQuery(sprintf(
                'SELECT r.topicId FROM %s r WHERE r.categoryId = :categoryId',
                TopicCategoryRel::class
            ));
            $query->setParameter('categoryId', $category->id);
            $query->setMaxResults($count);
            $query->setFirstResult($cursor);
            $result = $query->getScalarResult();
            if (count($result) < $count) {
                $nextCursor = 0;
            } else {
                $nextCursor = $cursor + $count;
            }
            return ArrayUtility::columns($result, 'topicId');
        }
    }

    /**
     * @param $categoryId
     * @return array
     */
    public function fetchTopicIdsInCategory($categoryId) {
        $query = $this->em->createQuery(sprintf(
            'SELECT r.topicId FROM %s r WHERE r.categoryId = :categoryId',
            TopicCategoryRel::class
        ));
        $query->setParameter('categoryId', $categoryId);
        $result = $query->getResult();
        
        return ArrayUtility::columns($result, 'topicId');
    }

    public function allTopicIdsInCategory($categoryName) {
        $category = $this->em->getRepository(TopicCategory::class)
            ->findOneBy(array('name' => $categoryName));
        if ($category == null) {
            throw new Exception\CategoryNotFoundException();
        } else {
            $query = $this->em->createQuery(sprintf(
                'SELECT r.topicId FROM %s r WHERE r.categoryId = :categoryId',
                TopicCategoryRel::class
            ));
            $query->setParameter('categoryId', $category->id);
            $result = $query->getScalarResult();
            return ArrayUtility::columns($result, 'topicId');
        }
    }

    public function categoriesByTopic($topicId) {
        $sql = 'SELECT c.name FROM topic_category as c LEFT JOIN topic_category_rel as r
          ON c.id = r.category_id WHERE r.topic_id = ? ORDER BY r.category_id DESC';
        $statement = $this->em->getConnection()->executeQuery($sql, array($topicId), array(\PDO::PARAM_INT));
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return ArrayUtility::columns($result, 'name');
    }

    public function categoriesByTopicIds($topicIds) {
        if (count($topicIds) == 0) {
            return array();
        }

        $sql = 'SELECT r.topic_id, c.name FROM topic_category as c LEFT JOIN topic_category_rel as r
          ON c.id = r.category_id WHERE r.topic_id IN ('.implode(',', $topicIds).') ORDER BY r.category_id DESC';
        $statement = $this->em->getConnection()->executeQuery($sql);
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return ArrayUtility::columnsGroupBy($result, 'name', 'topic_id');
    }

    /**
     * @param $topicId
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getPropertyByTopicId($topicId) {
        $sql = 'SELECT *
                FROM topic_category_rel r
                LEFT JOIN topic_category c ON r.category_id = c.id
                WHERE topic_id = :topicId AND category_id < 100';
        $conn = $this->em->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':topicId', $topicId);
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * @return mixed
     */
    public function getProperties() {
        $query = $this->em->getRepository(TopicCategory::class)->createQueryBuilder('t')
            ->where('t.id < 100')
            ->getQuery();
        $result = $query->getResult();

        return $result;
    }

    /**
     * @return mixed
     */
    public function getCategories() {
        $query = $this->em->getRepository(TopicCategory::class)->createQueryBuilder('t')
            ->where('t.id >= 100')
            ->getQuery();
        $result = $query->getResult();

        return $result;
    }

    /**
     * @return TopicCategory[]
     */
    public function getCurrentCategories() {
        $query = $this->em->createQuery('SELECT t FROM '.TopicCategory::class.' t WHERE t.id >= 300');
        $result = $query->getResult();
        return $result;
    }

    /**
     * @param $topicId
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getCategoriesByTopicId($topicId) {
        $sql = 'SELECT c.*
                FROM topic_category_rel r
                LEFT JOIN topic_category c ON r.category_id = c.id
                WHERE topic_id = :topicId AND category_id >= 100';
        $conn = $this->em->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':topicId', $topicId);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * @param $topicId
     * @param $categories
     * @throws \Doctrine\DBAL\DBALException
     */
    public function topicAddCategories($topicId, $categories) {
        $conn = $this->em->getConnection();
        $sql = 'DELETE FROM topic_category_rel WHERE topic_id=:topicId';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':topicId', $topicId);
        $stmt->execute();

        $sql = 'INSERT INTO topic_category_rel(category_id, topic_id)
                VALUE(:categoryId, :topicId)';
        foreach ($categories as $c) {
            $stmt = $this->em->getConnection()->prepare($sql);
            $stmt->bindValue(':categoryId', $c);
            $stmt->bindValue(':topicId', $topicId);
            $stmt->execute();
        }
    }

    /**
     * @param int $topicId
     * @throws \Doctrine\DBAL\DBALException
     */
    public function topicRemoveCategories($topicId) {
        $conn = $this->em->getConnection();
        $sql = 'DELETE FROM topic_category_rel WHERE topic_id=:topicId';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':topicId', $topicId);
        $stmt->execute();
    }

}