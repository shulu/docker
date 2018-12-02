<?php
namespace Lychee\Module\Topic;

use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Elastica\Exception\ResponseException;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Foundation\IteratorTrait;
use Lychee\Module\Topic\Entity\TopicCertified;
use Lychee\Module\Topic\Entity\TopicCoreMember;
use Lychee\Module\Topic\Entity\TopicCreatingApplication;
use Lychee\Module\Topic\Entity\TopicGroup;
use Lychee\Module\Topic\Entity\UserTopicCreating;
use Lychee\Module\Topic\Entity\UserTopicManaging;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Lychee\Component\KVStorage\DoctrineStorage;
use Lychee\Module\Topic\Entity\Topic;
use Lychee\Module\Topic\Entity\TopicCreatingQuota;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Doctrine\DBAL\LockMode;
use Lychee\Module\Topic\Following\TopicFollowingService;
use Lychee\Module\Analysis\Entity\TopicsViews;
use Lychee\Module\Topic\TopicCategoryService;
use Lychee\Component\Foundation\ImageUtility;

class TopicService {

    use IteratorTrait;

    /**
     * @var EntityManager
     */
    private $entityManager;

    private $eventDispatcher;
    /**
     * @var DoctrineStorage
     */
    private $entityStorage;

    private $followingService;
    
    private $categoryService;

    private $serviceContainer;

    /**
     * @param ManagerRegistry $doctrine
     * @param EventDispatcherInterface $eventDispatcher
     * @param TopicFollowingService $followingService
     * @param TopicCategoryService $categoryService
     */
    public function __construct($doctrine, $eventDispatcher, $followingService, $categoryService, $serviceContainer=null) {
        $this->entityManager = $doctrine->getManager($doctrine->getDefaultManagerName());
        $this->entityStorage = new DoctrineStorage($this->entityManager, Topic::class);
        $this->eventDispatcher = $eventDispatcher;
        $this->followingService = $followingService;
        $this->categoryService = $categoryService;
        $this->serviceContainer = $serviceContainer;
    }

    /**
     * @param TopicParameter $parameter
     *
     * @return Topic
     * @throws Exception\TopicAlreadyExistException
     * @throws Exception\RunOutOfCreatingQuotaException
     * @throws \Exception
     */
    public function create($parameter) {
        if ($parameter->title == null) {
            throw new \LogicException('can not create topic with out a title.');
        }
        try {
            $this->entityManager->beginTransaction();

            $this->addTopicTitle($parameter->title);

            if ($parameter->creatorId != null) {
                $ok = $this->increaseUserCreatingQuota($parameter->creatorId, -1);
                if (!$ok) {
                    throw new Exception\RunOutOfCreatingQuotaException();
                }
            }

            $topic = new Topic();
            $topic->createTime = new \DateTime();
            $topic->creatorId = $parameter->creatorId;
            $topic->managerId = $parameter->creatorId;
            $topic->title = $parameter->title;
            $topic->summary = $parameter->summary;
            $topic->description = $parameter->description;
            $topic->indexImageUrl = $parameter->indexImageUrl;
            $topic->coverImageUrl = $parameter->coverImageUrl;
            $topic->private = $parameter->private;
            $topic->applyToFollow = $parameter->applyToFollow;
            $topic->color = $parameter->color;

            $this->entityStorage->set(null, $topic);

            $userTopicManaging = new UserTopicManaging();
            $userTopicManaging->userId = $parameter->creatorId;
            $userTopicManaging->topicId = $topic->id;
            $this->entityManager->persist($userTopicManaging);
            $this->entityManager->flush($userTopicManaging);

            if (!empty($parameter->categoryIds)) {
                foreach ($parameter->categoryIds as $categoryId) {
                    $this->categoryService->categoryIdAddTopic($categoryId, $topic->id);
                }
            }

            $this->entityManager->commit();
            $this->eventDispatcher->dispatch(TopicEvent::CREATE, new TopicEvent($topic->id));

        } catch (UniqueConstraintViolationException $e) {
            $this->entityManager->rollback();
            throw new Exception\TopicAlreadyExistException();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        $this->followingService->follow($parameter->creatorId, $topic->id);
        $topic->followerCount += 1;

        return $topic;
    }

    /**
     * @param Topic $topic
     */
    public function update($topic) {
        $this->entityStorage->set($topic->id, $topic);
        $this->eventDispatcher->dispatch(TopicEvent::UPDATE, new TopicEvent($topic->id));

        $event = [];
        $event['topicId'] = $topic->id;
        $this->serviceContainer->get('lychee.dynamic_dispatcher_async')
            ->dispatch('topic.update', $event);
    }

    /**
     * @param int $topicId
     * @param int $managerId
     *
     * @throws \Exception
     */
    public function updateManager($topicId, $managerId) {
        try {
            $this->entityManager->beginTransaction();
            $topic = $this->fetchOne($topicId);
            if ($topic->managerId == $managerId) {
                return;
            }

            $this->entityManager->lock($topic, LockMode::PESSIMISTIC_WRITE);
            if ($topic->managerId) {
                $sql = 'DELETE FROM user_topic_managing WHERE user_id = ? AND topic_id = ?';
                $this->entityManager->getConnection()->executeUpdate($sql, array($topic->managerId, $topicId),
                    array(\PDO::PARAM_INT, \PDO::PARAM_INT));
            }
            $topic->managerId = $managerId;
            $this->entityStorage->set($topicId, $topic);

            $insertSql = 'INSERT INTO user_topic_managing(user_id, topic_id) VALUE (?, ?) ON DUPLICATE KEY UPDATE topic_id = topic_id';
            $this->entityManager->getConnection()->executeUpdate(
                $insertSql, array($managerId, $topicId),
                array(\PDO::PARAM_INT, \PDO::PARAM_INT));

            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    public function formatTopicFields($topic) {
        if (empty($topic)) {
            return null;
        }
        if (isset($topic->indexImageUrl)) {
            $topic->indexImageUrl = ImageUtility::formatUrl($topic->indexImageUrl);
        }
        if (isset($topic->coverImageUrl)) {
            $topic->coverImageUrl = ImageUtility::formatUrl($topic->coverImageUrl);
        }
        return $topic;
    }

    /**
     * @param array $ids
     *
     * @return array
     */
    public function fetch($ids) {
        $list = $this->entityStorage->getMulti($ids);
        foreach ($list as $item) {
            $this->formatTopicFields($item);
        }
        return $list;
    }

    /**
     * @param int $id
     *
     * @return Topic|null
     */
    public function fetchOne($id) {
        $topic =  $this->entityStorage->get($id);
        $this->formatTopicFields($topic);
        return $topic;
    }

    /**
     * @param string $title
     * @return Topic|null
     */
    public function fetchOneByTitle($title) {
        return $this->entityManager->getRepository(Topic::class)
            ->findOneBy(array('title' => $title));
    }

    /**
     * @param int $id
     *
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function exist($id) {
        /** @var Topic $topic */
        $topic = $this->entityManager->find(Topic::class, $id);
        if ($topic == null) {
            return false;
        } else if ($topic->deleted) {
            return false;
        } else {
            return true;
        }
    }

    public function maskAsDeleted($id) {
        $this->entityManager->transactional(function() use ($id) {
            $result = $this->entityManager->createQuery('
                SELECT t.creatorId, t.managerId FROM '.Topic::class.' t WHERE t.id = :id
            ')->setParameters(array('id' => $id))->getSingleResult();

            if ($result) {
                $managerId = $result['managerId'];
            } else {
                $managerId = null;
            }

            $this->entityManager->createQuery('
                UPDATE '.Topic::class.' t SET t.deleted = true WHERE t.id = :id
            ')->execute(array('id' => $id));

            if ($managerId) {
                $this->entityManager->createQuery('
                    DELETE FROM '.UserTopicManaging::class.' c WHERE c.userId = :userId AND c.topicId = :topicId
                ')->execute(array('userId'=>$managerId, 'topicId'=>$id));
            }
            $this->categoryService->topicRemoveCategories($id);
        });

        $this->eventDispatcher->dispatch(TopicEvent::DELETE, new TopicEvent($id));
    }

    public function fetchAll($cursor, $count, &$nextCursor = null) {
        if ($count === 0) {
            return array();
        }
        if ($cursor === 0) {
            $cursor = PHP_INT_MAX;
        }

        $query = $this->entityManager->createQuery('
            SELECT t
            FROM '.Topic::class.' t
            WHERE t.id < :maxTopicId AND t.deleted = false AND t.hidden = false
            ORDER BY t.id DESC
        ')->setMaxResults($count);
        $topics = $query->execute(array('maxTopicId' => $cursor));

        if (count($topics) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $topics[count($topics) - 1]->id;
        }

        return ArrayUtility::mapByColumn($topics, 'id');
    }

    public function fetchByKeyword($keyword, $cursor, $count, &$nextCursor = null) {
        if ($count === 0) {
            return array();
        }
        if ($cursor === 0) {
            $cursor = PHP_INT_MAX;
        }

        $query = $this->entityManager->createQuery('
            SELECT t
            FROM '.Topic::class.' t
            WHERE t.id < :maxId AND t.title LIKE :keyword
            ORDER BY t.id DESC
        ')->setMaxResults($count);
        $topics = $query->execute(array('maxId' => $cursor, 'keyword' => '%'. $keyword . '%'));

        if (count($topics) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $topics[count($topics) - 1]->id;
        }

        return ArrayUtility::mapByColumn($topics, 'id');
    }

    public function fetchByKeywordOrderByPostCount($keyword, $cursor, $count, &$nextCursor = null) {
        if ($count === 0) {
            return array();
        }
        if ($cursor === 0) {
            $cursor = PHP_INT_MAX;
        }

        $query = $this->entityManager->createQuery('
            SELECT t
            FROM '.Topic::class.' t
            WHERE t.id < :maxId AND t.title LIKE :keyword
            ORDER BY t.postCount DESC
        ')->setMaxResults($count);
        $topics = $query->execute(array('maxId' => $cursor, 'keyword' => '%'. $keyword . '%'));

        if (count($topics) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $topics[count($topics) - 1]->id;
        }

        return ArrayUtility::mapByColumn($topics, 'id');
    }

    /**
     * @param int $topicId
     * @param int $delta
     */
    public function increasePostCounter($topicId, $delta) {
        $topic = $this->fetchOne($topicId);
        $query = $this->entityManager->createQuery('
            UPDATE '.Topic::class.' t
            SET t.postCount = t.postCount + :delta
            WHERE t.id = :topicId
        ')->setParameters(array('topicId' => $topicId, 'delta' => $delta));
        $query->execute();

        $this->entityManager->detach($topic);
    }

    /**
     * @param $topicIds
     * @return array
     */
    public function fetchAmountOfPosts($topicIds) {
        $topics = $this->fetch($topicIds);
        $ret = [];
        foreach ($topics as $topic) {
            $ret[$topic->id] = $topic->postCount;
        }

        return $ret;
    }

    /**
     * @param int $userId
     *
     * @return int
     */
    public function getUserCreatingQuota($userId) {
        $tableName = $this->entityManager->getClassMetadata(TopicCreatingQuota::class)->getTableName();
        $sql = sprintf('SELECT quota FROM %s WHERE user_id = ?', $tableName);
        $statement = $this->entityManager->getConnection()->executeQuery($sql, array($userId), array(\PDO::PARAM_INT));
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        if (count($result) == 0) {
            return 0;
        } else {
            return intval($result[0]['quota']);
        }
    }

    public function fetchIdsByManager($userId, $cursor, $count, &$nextCursor = null) {
        if ($count === 0) {
            return array();
        }

        $query = $this->entityManager->createQuery('
            SELECT t.topicId
            FROM '.UserTopicManaging::class.' t
            WHERE t.userId = :managerId
            ORDER BY t.topicId DESC
        ')->setMaxResults($count)->setFirstResult($cursor);
        $query->setParameters(array('managerId' => $userId));
        $topicIds = ArrayUtility::columns($query->getScalarResult(), 'topicId');

        if (count($topicIds) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $cursor + $count;
        }

        return $topicIds;
    }

    public function increaseUserCreatingQuota($userId, $delta) {
        $tableName = $this->entityManager->getClassMetadata(TopicCreatingQuota::class)->getTableName();

        if ($delta == 0) {
            return false;
        } else if ($delta > 0) {
            $sql = sprintf('INSERT INTO %s(user_id, quota) VALUES (?, ?) ON DUPLICATE KEY UPDATE quota = quota + ?', $tableName);
            $affectedRow = $this->entityManager->getConnection()->executeUpdate($sql, array($userId, $delta, $delta));
        } else {
            $delta = -intval($delta);
            $sql = sprintf('UPDATE %s SET quota = quota - ? WHERE user_id = ? AND quota >= ?', $tableName);
            $affectedRow = $this->entityManager->getConnection()->executeUpdate($sql, array($delta, $userId, $delta));
        }
        return $affectedRow > 0;
    }

    /**
     * @param string $title
     * @throws UniqueConstraintViolationException
     */
    private function addTopicTitle($title) {
        $sql = 'INSERT INTO topic_titles(title) VALUES(?)';
        $this->entityManager->getConnection()->executeUpdate($sql, array($title), array(\PDO::PARAM_STR));
    }

    private function removeTopicTitle($title) {
        $sql = 'DELETE FROM topic_titles WHERE title = ?';
        $this->entityManager->getConnection()->executeUpdate($sql, array($title), array(\PDO::PARAM_STR));
    }

    /**
     * @param string $title
     * @return bool
     */
    private function existTopicTitle($title) {
        $sql = 'SELECT 1 FROM topic_titles WHERE title = ?';
        $stat = $this->entityManager->getConnection()->executeQuery($sql, array($title), array(\PDO::PARAM_STR));
        $row = $stat->fetch();
        return $row !== false;
    }

    /**
     * @param string $order
     * @return \Lychee\Component\Foundation\CursorableIterator\QueryCursorableIterator
     */
    public function iterateTopic($order = 'ASC')
    {
        return $this->iterateEntity($this->entityManager, Topic::class, 'id', $order);
    }

    /**
     * @param \DateTime $createTime
     * @return \Lychee\Component\Foundation\CursorableIterator\QueryCursorableIterator
     */
    public function iterateTopicByCreateTime(\DateTime $createTime)
    {
        return $this->iterateEntityByCreateTime($this->entityManager, Topic::class, 'id', 'createTime', $createTime);
    }

    /**
     * @param $topicId
     * @param \DateTime $startTime
     * @param \DateTime $endTime
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function countPost($topicId, \DateTime $startTime, \DateTime $endTime) {
        $conn = $this->entityManager->getConnection();
        $sql = 'SELECT COUNT(t.post_id) post_count
        FROM topic_post t
        LEFT JOIN post p ON p.id=t.post_id
        WHERE t.topic_id=:topicId AND p.create_time>=:startTime AND p.create_time<:endTime AND p.deleted=0';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':topicId', $topicId);
        $stmt->bindValue(':startTime', $startTime->format('Y-m-d H:i:s'));
        $stmt->bindValue(':endTime', $endTime->format('Y-m-d H:i:s'));
        $stmt->execute();
        $result = $stmt->fetch();

        return $result['post_count'];
    }

    /**
     * @param $topicId
     * @param \DateTime $startTime
     * @param \DateTime $endTime
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function countComment($topicId, \DateTime $startTime, \DateTime $endTime) {
        $conn = $this->entityManager->getConnection();
        $sql = 'SELECT COUNT(pc.comment_id) comment_count
        FROM topic_post t
        LEFT JOIN post_comment pc ON pc.post_id=t.post_id
        LEFT JOIN comment c ON c.id=pc.comment_id
        WHERE t.topic_id=:topicId AND c.create_time>=:startTime AND c.create_time<:endTime AND c.deleted=0';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':topicId', $topicId);
        $stmt->bindValue(':startTime', $startTime->format('Y-m-d H:i:s'));
        $stmt->bindValue(':endTime', $endTime->format('Y-m-d H:i:s'));
        $stmt->execute();
        $result = $stmt->fetch();

        return $result['comment_count'];
    }

    /**
     * @param $topicId
     * @param \DateTime $startTime
     * @param \DateTime $endTime
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function countFollower($topicId, \DateTime $startTime, \DateTime $endTime) {
        $conn = $this->entityManager->getConnection();
        $sql = 'SELECT COUNT(user_id) user_count
        FROM topic_user_following
        WHERE topic_id=:topicId AND create_time>=:startTime AND create_time<:endTime AND state=1';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':topicId', $topicId);
        $stmt->bindValue(':startTime', $startTime->format('Y-m-d H:i:s'));
        $stmt->bindValue(':endTime', $endTime->format('Y-m-d H:i:s'));
        $stmt->execute();
        $result = $stmt->fetch();

        return $result['user_count'];
    }

    public function hide($topicId) {
        $sql = 'UPDATE topic SET hidden = 1 WHERE id = ?';
        $affectedRow = $this->entityManager->getConnection()->executeUpdate($sql, array($topicId),
            array(\PDO::PARAM_INT));
        if ($affectedRow > 0) {
            $this->categoryService->topicRemoveCategories($topicId);
            $this->eventDispatcher->dispatch(TopicEvent::HIDE, new TopicEvent($topicId));
        }
    }

    public function unhide($topicId) {
        $sql = 'UPDATE topic SET hidden = 0 WHERE id = ?';
        $affectedRow = $this->entityManager->getConnection()->executeUpdate($sql, array($topicId),
            array(\PDO::PARAM_INT));
        if ($affectedRow) {
            $this->eventDispatcher->dispatch(TopicEvent::UNHIDE, new TopicEvent($topicId));
        }
    }

    public function listHideTopic($page, $limit = 20) {
        $offset = ($page - 1) * $limit;
        return $this->entityManager->getRepository(Topic::class)->findBy([
            'hidden' => 1,
            'deleted' => 0
        ], [
            'id' => 'DESC'
        ], $limit, $offset);
    }

    public function hideTopicsCount() {
        $qb = $this->entityManager->getRepository(Topic::class)->createQueryBuilder('t')
            ->select('COUNT(t)')
            ->where('t.hidden=1 AND t.deleted=0')
            ->getQuery();
        return $qb->getSingleScalarResult();
    }

    public function fetchNewestTopicIds($count = 3) {
        $qb = $this->entityManager->getRepository(Topic::class)->createQueryBuilder('t')
            ->where('t.deleted=0')
            ->orderBy('t.id', 'DESC')
            ->setMaxResults($count)
            ->getQuery();
        return $qb->getResult();
    }

    /**
     * @param int $topicId
     */
    public function maskAsCertified($topicId) {
        $topic = $this->fetchOne($topicId);
        $topic->certified = true;

        try {
            $this->entityManager->beginTransaction();
            $this->update($topic);
            $inertSql = 'INSERT INTO topic_certified(topic_id) VALUES(?)';
            $this->entityManager->getConnection()->executeUpdate($inertSql, array($topicId), array(\PDO::PARAM_INT));
            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    public function removeCertified($topicId) {
        $topic = $this->fetchOne($topicId);
        if ($topic) {
            $topic->certified = false;
            try {
                $this->entityManager->beginTransaction();
                $this->update($topic);
                $deleteSql = 'DELETE FROM topic_certified WHERE topic_id=?';
                $this->entityManager->getConnection()->executeUpdate($deleteSql, array($topicId), array(\PDO::PARAM_INT));
                $this->entityManager->commit();
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                throw $e;
            }
        }
    }

    /**
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     * @return int[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function listCertifiedTopics($cursor, $count, &$nextCursor) {
        if ($count == 0) {
            $nextCursor = $cursor;
            return array();
        }
        
        if ($cursor == 0) {
            $cursor = PHP_INT_MAX;
        }
        $sql = 'SELECT id, topic_id FROM topic_certified WHERE id < ? ORDER BY id DESC LIMIT ?';
        $stat = $this->entityManager->getConnection()->executeQuery($sql,
            array($cursor, $count), array(\PDO::PARAM_INT, \PDO::PARAM_INT));
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        $topicIds = ArrayUtility::columns($rows, 'topic_id');
        if (count($rows) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $rows[count($rows) - 1]['id'];
        }

        return $topicIds;
    }

    public function fetchCertifiedTopics($page = 1, $count = 20) {
        $offset = ($page - 1) * $count;
        $query = $this->entityManager->createQuery('
            SELECT tc
            FROM ' . TopicCertified::class . ' tc
            ORDER BY tc.id DESC
        ')->setMaxResults($count)
            ->setFirstResult($offset);
        $topicIds = ArrayUtility::columns($query->getResult(), 'topicId');

        return [$topicIds, $this->certifiedTopicsCount()];
    }

    public function certifiedTopicsCount() {
        $query = $this->entityManager->createQuery('
            SELECT COUNT(tc.id) topic_count
            FROM ' . TopicCertified::class . ' tc
        ');
        $count = $query->getSingleResult();

        return $count['topic_count'];
    }

    public function allTopicCount() {
        $dql = 'SELECT COUNT(t.id) topic_count FROM ' . Topic::class . ' t';
        $query = $this->entityManager->createQuery($dql);
        $count = $query->getSingleResult();

        return $count['topic_count'];
    }

    /**
     * @param TopicParameter $parameter
     * @throws Exception\TopicAlreadyExistException
     * @throws Exception\RunOutOfCreatingQuotaException
     * @throws \Exception
     */
    public function submitCreatingApplication($parameter) {
        if ($parameter->creatorId == null || $parameter->title == null) {
            throw new \LogicException('can not create topic creating application with out a creator or title.');
        }

        $application = new TopicCreatingApplication();
        $application->title = $parameter->title;
        $application->creatorId = $parameter->creatorId;
        $application->applyTime = new \DateTime();
        $application->applyToFollow = $parameter->applyToFollow;
        $application->color = $parameter->color;
        $application->coverImageUrl = $parameter->coverImageUrl;
        $application->indexImageUrl = $parameter->indexImageUrl;
        $application->description = $parameter->description;
        $application->summary = $parameter->summary;
        $application->private = $parameter->private;
        $application->categoryId = empty($parameter->categoryIds) ? null : $parameter->categoryIds[0];

        try {
            $this->entityManager->beginTransaction();
            $this->addTopicTitle($parameter->title);

            $ok = $this->increaseUserCreatingQuota($parameter->creatorId, -1);
            if (!$ok) {
                throw new Exception\RunOutOfCreatingQuotaException();
            }

            $this->entityManager->persist($application);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (UniqueConstraintViolationException $e) {
            $this->entityManager->rollback();
            throw new Exception\TopicAlreadyExistException();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    /**
     * @param int $applicationId
     * @return TopicCreatingApplication|null
     */
    public function getCreatingApplication($applicationId) {
        return $this->entityManager->find(TopicCreatingApplication::class,
            $applicationId);
    }

    /**
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     * @return TopicCreatingApplication[]
     */
    public function listCreatingApplication($cursor, $count, &$nextCursor) {
        if ($count === 0) {
            $nextCursor = $cursor;
            return array();
        }

        $query = $this->entityManager->createQuery('SELECT t FROM '.TopicCreatingApplication::class.' t WHERE t.id > :minId ORDER BY t.id ASC')->setMaxResults($count);
        $applications = $query->execute(array('minId' => $cursor));

        if (count($applications) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $applications[count($applications) - 1]->id;
        }

        foreach ($applications as $item) {
            $this->formatTopicFields($item);
        }

        return ArrayUtility::mapByColumn($applications, 'id');
    }

    /**
     * @param int $applicationId
     * @return Topic|null
     * @throws Exception\TopicAlreadyExistException
     * @throws \Exception
     */
    public function confirmCreatingApplication($applicationId) {
        try {
            $this->entityManager->beginTransaction();

            $application = $this->entityManager->find(TopicCreatingApplication::class,
                $applicationId, LockMode::PESSIMISTIC_READ);
            if ($application == null) {
                $this->entityManager->rollback();
                return null;
            }

            $topic = new Topic();
            $topic->createTime = new \DateTime();
            $topic->creatorId = $application->creatorId;
            $topic->managerId = $application->creatorId;
            $topic->title = $application->title;
            $topic->summary = $application->summary;
            $topic->description = $application->description;
            $topic->indexImageUrl = $application->indexImageUrl;
            $topic->coverImageUrl = $application->coverImageUrl;
            $topic->private = $application->private;
            $topic->applyToFollow = $application->applyToFollow;
            $topic->color = $application->color;

            $this->entityStorage->set(null, $topic);

            $userTopicManaging = new UserTopicManaging();
            $userTopicManaging->userId = $application->creatorId;
            $userTopicManaging->topicId = $topic->id;
            $this->entityManager->persist($userTopicManaging);
            $this->entityManager->flush($userTopicManaging);

            if (!empty($application->categoryId)) {
                $this->categoryService->categoryIdAddTopic($application->categoryId, $topic->id);
            }

            $this->entityManager->remove($application);
            $this->entityManager->flush();

            $this->eventDispatcher->dispatch(TopicEvent::CREATE, new TopicEvent($topic->id));

            $this->entityManager->commit();
        } catch (UniqueConstraintViolationException $e) {
            $this->entityManager->rollback();
            throw new Exception\TopicAlreadyExistException();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        $this->followingService->follow($application->creatorId, $topic->id);
        return $topic;
    }

    public function rejectCreatingApplication($applicationId) {
        try {
            $this->entityManager->beginTransaction();

            $application = $this->entityManager->find(TopicCreatingApplication::class,
                $applicationId, LockMode::PESSIMISTIC_READ);
            if ($application == null) {
                $this->entityManager->rollback();
                return;
            }

            $this->removeTopicTitle($application->title);
            $this->increaseUserCreatingQuota($application->creatorId, 1);
            $this->entityManager->remove($application);
            $this->entityManager->flush();

            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

	/**
	 * @param $topicId
	 * @param $memberId
	 *
	 * @return bool
	 */
    public function isCoreMember($topicId, $memberId) {
    	$member = $this->entityManager->getRepository(TopicCoreMember::class)
		    ->findOneBy([
		    	'topicId' => $topicId,
		    	'userId' => $memberId
		    ]);

	    return $member && true;
    }


    /**
     * 获取次元分组
     *
     * @param int $cursor
     * @param int $count
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getGroupRelations($cursor=0, $count=10, &$nextCursor=null)
    {
        $nextCursor = 0;
        $configs = [];
        //次元分类id，次元分类名，拥有的次元id集合
        $configs[] = [1, '热门', [25076,54703,25109,54728]];
        $configs[] = [2, '宅向', [54723,25354,36094,27115,25168,25384,25159,25211,28874,25115]];
        $configs[] = [3, '自拍', [35360,25150,25220,35024,41951,31252]];
        $configs[] = [4, '游戏', [50194,35601,48064,48019,26082,25183,54639]];
        $configs[] = [5, '大触', [25362,26454,29759,31168,33787,54237,25386,25430]];
        $configs[] = [6, '美图', [27925,29661,25511,25497,28711,25473,46853,32129,34016,29579,34753,31167]];
        $configs[] = [7, '三次元', [32872,35409,25109,25181,25158,31747,31825,32636,30965,32352,30727]];
        $configs[] = [8, '影音', [25935,54699,34316]];

        $list = [];

        foreach ($configs as $item) {
            list($groupId, $groupName, $topicIds) = $item;
            $list[] = [
                'groupId' => $groupId,
                'groupName' => $groupName,
                'topicIds' => $topicIds
            ];
        }

        return $list;

        // 具备后台后开放下方逻辑

        $groups = $this->entityManager->getRepository(TopicGroup::class)
            ->createQueryBuilder('repo')
            ->orderBy("repo.weight", 'DESC')
            ->addOrderBy("repo.id", 'ASC')
            ->setFirstResult($cursor)
            ->setMaxResults($count+1)
            ->getQuery()
            ->getResult();

        $nextCursor = 0;
        if (isset($groups[$count])) {
            $nextCursor = $cursor+$count;
            unset($groups[$count]);
        }

        $groupIds = [];
        $list = [];
        foreach ($groups as $group) {
            $groupIds[] = $group->id;
            $item = [];
            $item['groupId'] = $group->id;
            $item['groupName'] = $group->name;
            $item['topicIds'] = [];
            $list[$group->id] = $item;
        }

        if (empty($groupIds)) {
            return [];
        }

        $sql = "select topic_id,group_id from topic_group_rel where group_id in (".implode(',', $groupIds).")";
        $rels = $this->entityManager->getConnection()
            ->executeQuery($sql)
            ->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rels as $item) {
            $list[$item['group_id']]['topicIds'][] = $item['topic_id'];
        }

        return $list;
    }


    /**
     * 新增次元分组
     *
     * @param $name
     * @return TopicGroup
     */
    public function addGroup($name, $weight=0)
    {
        $group = new TopicGroup();
        $group->name = $name;
        $group->weight = $weight;
        $this->entityManager->persist($group);
        $this->entityManager->flush();
        return $group;
    }

    public function getTopicGroupDoctrineStorage()
    {
        static $entityStorage = null;
        if ($entityStorage) {
            return $entityStorage;
        }
        $entityStorage = new DoctrineStorage($this->entityManager, TopicGroup::class);
        return $entityStorage;
    }

    /**
     * 更新次元分组
     *
     * @param $name
     * @return bool
     */
    public function updateGroup($id, $name, $weight=null)
    {
        $group = $this->getTopicGroupDoctrineStorage()->get($id);
        if (empty($group)) {
            return false;
        }
        if (!is_null($weight)) {
            $group->weight = $weight;
        }
        $group->name = $name;
        $this->entityManager->persist($group);
        $this->entityManager->flush();
        return true;
    }

    /**
     * 新增次元分组关联
     *
     * @param $topicId
     * @param $groupId
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function topicAddGroup($topicId, $groupId)
    {
        $sql = 'INSERT INTO topic_group_rel(topic_id, group_id, update_time)
          VALUE(?, ?, ?) ON DUPLICATE KEY UPDATE update_time = ?';
        $time = time();

        $this->entityManager->getConnection()->executeUpdate($sql,
            array($topicId, $groupId, $time, $time),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT)
        );

        return true;
    }

    /**
     * 取消次元分组关联
     *
     * @param $topicId
     * @param $groupId
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function topicRemoveGroup($topicId, $groupId) {
        $sql = 'DELETE FROM topic_group_rel WHERE topic_id = ? AND group_id = ?';
        $this->entityManager->getConnection()->executeUpdate($sql,
            array($topicId, $groupId), array(\PDO::PARAM_INT, \PDO::PARAM_INT));
        return true;
    }


    /**
     * 更新次元后事件，消费消息队列异步执行
     *
     * @param $eventBody
     */
    public function asyncAfterUpdate($eventBody) {
        $topicId = $eventBody['topicId'];
        $topic = $this->fetchOne($topicId);
        $this->entityManager->clear(Topic::class);
        try {
            if ($topic) {
                $this->serviceContainer->get('lychee.module.search.superiorTopicIndexer')->update($topic);
            }
        } catch (ResponseException $e) {}

        return true;
    }


    public function isReplaceUrl($id)
    {
        $r = UrlReplaceWhiteList::getList();
        return !in_array($id, $r);
    }

}