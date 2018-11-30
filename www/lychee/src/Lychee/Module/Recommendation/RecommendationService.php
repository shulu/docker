<?php
namespace Lychee\Module\Recommendation;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Elastica\Exception\ResponseException;
use Lsw\MemcacheBundle\Cache\MemcacheInterface;
use Lychee\Bundle\CoreBundle\Entity\User;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Foundation\CursorableIterator\QueryCursorableIterator;
use Lychee\Module\Recommendation\Entity\EditorChoiceTopic;
use Lychee\Module\Recommendation\Entity\EditorChoiceTopicCategory;
use Lychee\Module\Recommendation\Entity\RecommendableTopic;
use Lychee\Module\Recommendation\Entity\RecommendationGroup;
use Lychee\Module\Recommendation\Entity\RecommendationItem;
use Lychee\Module\Recommendation\Entity\StickyRecommendationItem;
use Lychee\Module\Recommendation\Entity\TyroRecommendationTopicCategory;
use Lychee\Module\Recommendation\Entity\TyroRecommendationTopics;
use Lychee\Module\Recommendation\Exception\GroupTimeInvalidException;
use Lychee\Module\Recommendation\Exception\GroupTypeInvalidException;
use Lychee\Module\Recommendation\Exception\ItemPositionDuplicateException;
use Lychee\Module\Recommendation\RecommendationType;
use Lychee\Module\Recommendation\Entity\RecommendationTopicTest;
use Lychee\Module\Recommendation\UserRankingType;
use Lychee\Component\Foundation\ImageUtility;

class RecommendationService {
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var MemcacheInterface
     */
    private $memcache;

    /**
     * @var \Predis\Client|\Redis
     */
    private $redis;

    private $serviceContainer;

    /**
     * @param Registry $doctrine
     * @param string $entityManagerName
     * @param MemcacheInterface $memcache
     * @param \Predis\Client|\Redis $redis
     */
    public function __construct($doctrine, $entityManagerName, $memcache, $redis, $serviceContainer=null) {
        $this->entityManager = $doctrine->getManager($entityManagerName);
        $this->memcache = $memcache;
        $this->redis = $redis;
        $this->serviceContainer = $serviceContainer;
    }

    /**
     * @param int $zhai
     * @param int $meng
     * @param int $ran
     * @param int $fu
     * @param int $jian
     * @param int $ao
     *
     * @return int[]
     */
    public function getTopicIdsByTestScore($zhai, $meng, $ran, $fu, $jian, $ao) {
        $query = $this->entityManager->createQuery('
            SELECT DISTINCT t.topicId FROM '.RecommendationTopicTest::class.' t
            WHERE
            (t.property = \''.RecommendationTopicTest::PROPERTY_ZHAI.'\' AND t.score <= :zhai ) OR
            (t.property = \''.RecommendationTopicTest::PROPERTY_MENG.'\' AND t.score <= :meng ) OR
            (t.property = \''.RecommendationTopicTest::PROPERTY_RAN.'\' AND t.score <= :ran ) OR
            (t.property = \''.RecommendationTopicTest::PROPERTY_FU.'\' AND t.score <= :fu ) OR
            (t.property = \''.RecommendationTopicTest::PROPERTY_JIAN.'\' AND t.score <= :jian ) OR
            (t.property = \''.RecommendationTopicTest::PROPERTY_AO.'\' AND t.score <= :ao )
            ORDER BY t.score DESC
        ');
        $query->setParameters(array(
            'zhai' => $zhai, 'meng' => $meng, 'ran' => $ran,
            'fu' => $fu, 'jian' => $jian, 'ao' => $ao
        ));
        $result = $query->getArrayResult();
        return ArrayUtility::columns($result, 'topicId');
    }

    /**
     * @param string $type RecommendationType
     *
     * @return IdList
     */
    public function getHotestIdList($type) {
        switch ($type) {
            case RecommendationType::COMMENT:
                $key = 'hot_comments';
                break;
            case RecommendationType::POST:
                $key = 'hot_posts';
                break;
            case RecommendationType::TOPIC:
                $key = 'hot_topics';
                break;
            case RecommendationType::USER:
                $key = 'hot_users';
                break;
            case RecommendationType::VIDEO_POST:
                $key = 'hot_video_posts';
                break;
            default:
                throw new \InvalidArgumentException('Unknown type');
        }

        return new IdList($this->redis, $key);
    }

    /**
     * @param string $type
     * @return RankingList
     */
    public function getUserRankingIdList($type) {
        switch ($type) {
            case UserRankingType::FOLLOWED:
                $key = 'user_ranking_followed';
                break;
            case UserRankingType::COMMENT:
                $key = 'user_ranking_comment';
                break;
            case UserRankingType::POST:
                $key = 'user_ranking_post';
                break;
            case UserRankingType::IMAGE_COMMENT:
                $key = 'user_ranking_image_comment';
                break;
            default:
                throw new \InvalidArgumentException('Unknown type');
        }

        return new RankingList($this->redis, $key);
    }

    /**
     * @param string $type RecommendationType
     * @throws \InvalidArgumentException
     */
    private function checkRecommendationType($type) {
        if (!in_array($type, array(
            RecommendationType::POST,
            RecommendationType::COMMENT,
            RecommendationType::TOPIC,
            RecommendationType::USER,
            RecommendationType::SPECIAL_SUBJECT,
            RecommendationType::APP,
        ))
        ) {
            throw new \InvalidArgumentException('unknown type.');
        }
    }

    /**
     * @param string $type RecommendationType
     * @param int $id
     * @param string|null $image
     * @param string|null $reason
     */
    public function addRecommendedItem($type, $id, $image, $reason) {
        $this->checkRecommendationType($type);

        $item = new RecommendationItem();
        $item->setType($type);
        $item->setTargetId($id);
        $item->setImage($image);
        $item->setReason($reason);

        $this->entityManager->persist($item);
        $this->entityManager->flush($item);
    }

    /**
     * @param int|RecommendationItem $idOrItem
     *
     * @throws \InvalidArgumentException
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function removeRecommendedItem($idOrItem) {
        if ($idOrItem instanceof RecommendationItem) {
            $item = $idOrItem;
        } else {
            $id = intval($idOrItem);
            $item = $this->entityManager->find(RecommendationItem::class, $id);
            if ($item == null) {
                throw new \InvalidArgumentException('invalid id.');
            }
        }

        $this->entityManager->remove($item);
        $this->entityManager->flush($item);
    }

    /**
     * @param string $type
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     * @param \DateTime|null $date
     *
     * @return RecommendationItem[]
     * @return array
     */
    public function listRecommendedItems($type, $cursor, $count, &$nextCursor, \DateTime $date = null) {
        $this->checkRecommendationType($type);

        if ($count <= 0) {
            $nextCursor = $cursor;
            return array();
        }

	    $qb = $this->entityManager->getRepository(RecommendationItem::class)->createQueryBuilder('ri');
        if ($type === RecommendationType::TOPIC) {
        	/*
        	 * 推荐次元有顺序, 因此不以ID作cursor, 而用position
        	 */
        	if ($cursor != 0) {
        		$cursor += 1;
	        }
        	$qb->where('ri.type = :type')
		        ->andWhere('ri.position >= :cursor')
		        ->setParameter('type', $type)
		        ->setParameter('cursor', $cursor);
        } else {
	        if ($cursor == 0) {
		        $cursor = PHP_INT_MAX;
	        }
	        $qb->where('ri.id < :cursor')
	           ->andWhere('ri.type = :type')
	           ->setParameter('cursor', $cursor)
	           ->setParameter('type', $type);
        }
        if (null !== $date) {
		    $date = $date->modify('midnight');
		    $nextDate = clone $date;
		    $nextDate->modify('tomorrow');
		    $qb->andWhere('ri.createTime < :tomorrow')
		       ->andWhere('ri.createTime >= :today')
		       ->setParameter('tomorrow', $nextDate)
		       ->setParameter('today', $date);
	    }
	    $qb->orderBy('ri.position', 'ASC');
	    $qb->addOrderBy('ri.sticky', 'DESC');
	    $qb->addOrderBy('ri.id', 'DESC');

        $query = $qb->getQuery();
        $query->setMaxResults($count);
        $result = $query->getResult();

        if (count($result) < $count) {
            $nextCursor = 0;
        } else {
	        if ($type === RecommendationType::TOPIC) {
	        	$nextCursor = $result[count($result) - 1]->getPosition();
	        } else {
		        $nextCursor = $result[count($result) - 1]->getId();
	        }
        }

        return $result;
    }

	/**
	 * @return mixed
	 */
    public function fetchRecommendationPostsIn24() {
    	$now = new \DateTime();
	    $oneDayAgo = clone $now;
	    $oneDayAgo->modify('-1 day');
    	$query = $this->entityManager->getRepository(RecommendationItem::class)->createQueryBuilder('ri')
		    ->where('ri.type=:type')
		    ->andWhere('ri.createTime>=:startTime')
		    ->andWhere('ri.createTime<:endTime')
		    ->setParameter('type', RecommendationType::POST)
		    ->setParameter('startTime', $oneDayAgo)
		    ->setParameter('endTime', $now)
		    ->orderBy('ri.id', 'DESC')
		    ->getQuery();

	    return $query->getResult();
    }

    /**
     * @param $id
     * @return null|object
     */
    public function fetchItemById($id) {
        return $this->entityManager->getRepository(RecommendationItem::class)
            ->find($id);
    }

    /**
     * @param string $type
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     * @param array $excluded
     *
     * @return array
     */
    public function fetchHottestIds($type, $cursor, $count, &$nextCursor, $excluded = array()) {
        if ($count > 0) {
            $iterator = $this->getHotestIdList($type)
                ->getIterator($excluded);
            $iterator->setCursor($cursor);
            $iterator->setStep($count);
            $nextCursor = $iterator->getNextCursor();
            return $iterator->current();
        } else {
            $nextCursor = $cursor;
            return array();
        }
    }

    /**
     * @param $cursor
     * @param $count
     * @param null $nextCursor
     * @return array
     */
    public function fetchAppsByCursor($cursor, $count, &$nextCursor = null) {
        return $this->recommendationItemIterator(RecommendationType::APP, $cursor, $count, $nextCursor);
    }

    /**
     * @param $cursor
     * @param $count
     * @param null $nextCursor
     * @return array
     */
    public function fetchSpecialSubjectsByCursor($cursor, $count, &$nextCursor = null) {
        return $this->recommendationItemIterator(RecommendationType::SPECIAL_SUBJECT, $cursor, $count, $nextCursor);
    }

    /**
     * @param $type
     * @param $cursor
     * @param $count
     * @param null $nextCursor
     * @return array
     */
    private function recommendationItemIterator($type, $cursor, $count, &$nextCursor = null) {
        if (0 == $count) {
            return [];
        }
        if (0 == $cursor) {
            $cursor = PHP_INT_MAX;
        }
        $query = $this->entityManager->createQuery('
            SELECT ri
            FROM Lychee\Module\Recommendation\Entity\RecommendationItem ri
            WHERE ri.id < :cursor AND ri.type = :recommendationType
            ORDER BY ri.id DESC
        ');
        $query->setMaxResults($count)->setParameters([
            'recommendationType' => $type,
            'cursor' => $cursor
        ]);
        $result = $query->getArrayResult();
        $items = ArrayUtility::columns($result, 'id');
        $targetIds = array_map(function ($item) {
            return $item['targetId'];
        }, $result);
        if (count($items) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $items[count($items) - 1];
        }

        return $targetIds;
    }

    public function fetchItemByTarget($targetId, $recommendationType) {
        $item = $this->entityManager->getRepository(RecommendationItem::class)
            ->findBy([
                'targetId' => $targetId,
                'type' => $recommendationType,
            ]);

        return $item;
    }
    
    public function listTopicIdsInCategoryByHotOrder($categoryId, $cursor, $count, &$nextCursor = null) {
        if ($count <= 0) {
            $nextCursor = $cursor;
            return array();
        }

        $sql = 'SELECT topic_id FROM topic_category_score WHERE category_id = ?'
            .' ORDER BY score DESC, `order` ASC, topic_id DESC LIMIT ?, ?';
        $stat = $this->entityManager->getConnection()->executeQuery($sql, array($categoryId, $cursor, $count),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        $topicIds = ArrayUtility::columns($rows, 'topic_id');
        if (count($topicIds) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $cursor + $count;
        }
        
        return $topicIds;
    }

    /**
     * @param int|int[] $topicIds
     */
    public function addRecommendableTopics($topicIds) {
        if (!is_array($topicIds)) {
            $topicIds = array($topicIds);
        }

        $blockSize = 100;
        for ($i = 0; $i < count($topicIds); $i += $blockSize) {
            $block = array_slice($topicIds, $i, $blockSize);
            $block = array_map(function($t){return intval($t);}, $block);
            $block = array_filter($block, function($t){return $t > 0;});
            if (count($block) > 0) {
                $sql = 'INSERT INTO recommendable_topic(topic_id) VALUES('
                    .implode('),(', $block)
                    .') ON DUPLICATE KEY UPDATE topic_id = topic_id';
                $this->entityManager->getConnection()->executeUpdate($sql);

                $event = [];
                $event['topicIds'] = $block;
                $this->serviceContainer->get('lychee.dynamic_dispatcher_async')
                    ->dispatch('recommendable_topic.create', $event);
            }
        }
    }

    /**
     * @param int|int[] $topicIds
     * @throws \Doctrine\DBAL\DBALException
     */
    public function removeRecommendableTopics($topicIds) {
        if (!is_array($topicIds)) {
            $topicIds = array($topicIds);
        }

        $blockSize = 100;
        for ($i = 0; $i < count($topicIds); $i += $blockSize) {
            $block = array_slice($topicIds, $i, $blockSize);
            $block = array_map(function($t){return intval($t);}, $block);
            $block = array_filter($block, function($t){return $t > 0;});
            if (count($block) > 0) {
                $sql = 'DELETE FROM recommendable_topic WHERE topic_id IN ('
                    .implode(',', $block).')';
                $this->entityManager->getConnection()->executeUpdate($sql);

                $event = [];
                $event['topicIds'] = $block;
                $event['time'] = time();
                $this->serviceContainer->get('lychee.dynamic_dispatcher_async')
                    ->dispatch('recommendable_topic.delete', $event);
            }
        }
    }

    public function clearRecommendableTopics() {
    	$sql = 'DELETE FROM recommendable_topic';
	    $this->entityManager->getConnection()->executeUpdate($sql);
    }

    /**
     * 获取编辑推荐次元
     * @return array
     */
    public function fetchTopicsByEditorChoice() {
        $categoryRepo = $this->entityManager->getRepository(EditorChoiceTopicCategory::class);
        $categories = $categoryRepo->findBy([], [
            'position' => 'ASC'
        ]);
        $topicsRepo = $this->entityManager->getRepository(EditorChoiceTopic::class);
        $topics = $topicsRepo->findBy([], [
            'position' => 'ASC'
        ]);
        $result = [];
        /**
         * @var $c EditorChoiceTopicCategory
         */
        foreach ($categories as $c) {
            /**
             * @var $t EditorChoiceTopic
             */
            foreach ($topics as $t) {
                if ($c->categoryId === $t->categoryId) {
                    $result[$c->categoryId][] = $t->topicId;
                }
            }
        }

        return $result;
//        array(
//            301 => array(25362, 25076, 33787, 25384, 25923, 25159),
//            306 => array(29661, 31168, 25097),
//            305 => array(25150, 25109, 27925),
//            308 => array(25168, 27579, 33894),
//            304 => array(26082, 30653, 25183),
//            303 => array(25116, 25093, 26033)
//        );
    }

    /**
     * 添加编辑推荐次元
     * @param $categoryId
     * @param $topicId
     * @throws \Doctrine\DBAL\DBALException
     */
    public function addEditorChoiceTopic($categoryId, $topicId) {
        $categoryRepo = $this->entityManager->getRepository(EditorChoiceTopicCategory::class);
        $category = $categoryRepo->findOneBy(['categoryId' => $categoryId]);
        /**
         * @var $maxOrderCategory EditorChoiceTopicCategory
         */
        $maxOrderCategory = $categoryRepo->findOneBy([], [
            'position' => 'DESC'
        ]);
        $maxOrder = 0;
        if ($maxOrderCategory) {
            $maxOrder = $maxOrderCategory->position;
        }
        if (!$category) {
            $category = new EditorChoiceTopicCategory();
            $category->categoryId = $categoryId;
            $category->position = $maxOrder + 1;
            $this->entityManager->persist($category);
            $this->entityManager->flush();
        }
        $conn = $this->entityManager->getConnection();
        $stmt = $conn->prepare("UPDATE editor_choice_topic SET position=position+1 WHERE category_id=:categoryId");
        $stmt->bindParam(':categoryId', $categoryId);
        $stmt->execute();
        $editorChoiceTopic = new EditorChoiceTopic();
        $editorChoiceTopic->categoryId = $categoryId;
        $editorChoiceTopic->topicId = $topicId;
        $editorChoiceTopic->position = 1;
        $this->entityManager->persist($editorChoiceTopic);
        $this->entityManager->flush();
    }

    /**
     * 删除编辑推荐次元
     * @param $categoryId
     * @param $topicId
     */
    public function removeEditorChoiceTopic($categoryId, $topicId) {
        $topicRepo = $this->entityManager->getRepository(EditorChoiceTopic::class);
        /**
         * @var $topic null|EditorChoiceTopic
         */
        $topic = $topicRepo->findOneBy([
            'categoryId' => $categoryId,
            'topicId' => $topicId,
        ]);
        if ($topic) {
            $this->entityManager->remove($topic);
            $this->entityManager->flush();
            $topics = $topicRepo->findBy([
                'categoryId' => $categoryId,
            ], [
                'position' => 'ASC'
            ]);
            if ($topics) {
                $order = 1;
                /**
                 * @var $t EditorChoiceTopic
                 */
                foreach ($topics as $t) {
                    $t->position = $order;
                    $order += 1;
                }
                $this->entityManager->flush();
            } else {
                $categoryRepo = $this->entityManager->getRepository(EditorChoiceTopicCategory::class);
                $category = $categoryRepo->findOneBy(['categoryId' => $categoryId]);
                if ($category) {
                    $this->entityManager->remove($category);
                    $this->entityManager->flush();
                    $categories = $categoryRepo->findBy([], ['position' => 'ASC']);
                    if ($categories) {
                        $order = 1;
                        /**
                         * @var $c EditorChoiceTopicCategory
                         */
                        foreach ($categories as $c) {
                            $c->position = $order;
                            $order += 1;
                        }
                        $this->entityManager->flush();
                    }
                }
            }
        }
    }

    public function stickItem($itemId) {
        /** @var RecommendationItem $item */
        $item = $this->fetchItemById($itemId);
        if ($item) {
            $this->unstickItemByType($item->getType());
            $item->setSticky(1);
            $this->entityManager->flush();
        }
    }

    public function unstickItem($itemId) {
        /** @var RecommendationItem $item */
        $item = $this->entityManager->getRepository(RecommendationItem::class)
            ->find($itemId);
        if ($item) {
            $item->setSticky(0);
            $this->entityManager->flush();
        }
    }

    public function unstickItemByType($type) {
        $items = $this->entityManager->getRepository(RecommendationItem::class)
            ->findBy([
                'type' => $type,
                'sticky' => 1,
            ]);
        if ($items) {
            foreach ($items as $item) {
                $this->entityManager->remove($item);
            }
            $this->entityManager->flush();
        }
    }

    public function filterItemTargetIds($type, $targetIds) {
        $items = $this->entityManager->getRepository(RecommendationItem::class)
            ->findBy([
                'type' => $type,
                'targetId' => $targetIds,
            ]);
        if ($items) {
            return array_map(function($item) {
                /** @var RecommendationItem $item */
                return $item->getTargetId();
            }, $items);
        }
        return [];
    }

	/**
	 * @return array
	 */
    public function filterRecommendableTopicIds($topicIds) {
        $topics = $this->entityManager->getRepository(RecommendableTopic::class)
            ->findBy([
                'topicId' => $topicIds
            ]);
        
        return ArrayUtility::columns($topics, 'topicId');
    }

    public function fetchRecommendableTopicIds() {
        $topics = $this->entityManager->getRepository(RecommendableTopic::class)
            ->findAll();

        return ArrayUtility::columns($topics, 'topicId');
    }
    
    public function formatFields($item) {
        if (empty($item)) {
            return null;
        }
        $item->setImage(ImageUtility::formatUrl($item->getImage()));
        return $item;
    }

	/**
	 * @return array
	 */
    public function listAllRecommendationTopics() {
    	$em = $this->entityManager;
	    $items = $em->getRepository(RecommendationItem::class)
		    ->findBy([
		    	'type' => RecommendationType::TOPIC
		    ], [
		    	'position' => 'ASC'
		    ]);
        foreach ($items as $key => $item) {
            $item = $this->formatFields($item);
            $items[$key] = $item;
        }
	    return $items;
    }

    public function listAllRecommendationTopicIds() {
    	$query = $this->entityManager->getRepository(RecommendationItem::class)
		    ->createQueryBuilder('r')
		    ->select('r.targetId')
		    ->where('r.type=:type')
		    ->orderBy('r.position', 'ASC')
		    ->setParameter('type', RecommendationType::TOPIC)
		    ->getQuery();
	    $result = $query->getResult();

	    return array_map(function($item) { return (int)$item['targetId']; }, $result);
    }

    public function getVipRanking() {
	    $result = $this->memcache->get('count_following');
	    if(!$result) {
		    return [];
	    }

	    return array_keys($result);
    }

    public function listRecommendationLiveIds(){

	    $now = new \DateTime('now');
	    $startTime = $now->modify('-3 hours');

	    //$stmt->bindValue(':start_time', strtotime (date ("Y-m-d H:i:s")), \PDO::PARAM_INT);


	    $sql = 'SELECT post_id FROM live_post WHERE finish = 0 AND start_time > ? ORDER BY id DESC LIMIT 0, 10';
	    $stat = $this->entityManager->getConnection()->executeQuery($sql, array($startTime->format('Y-m-d H:i:s')),
		    array(\PDO::PARAM_STR));

	    $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
	    $postIds = ArrayUtility::columns($rows, 'post_id');

	    return $postIds;
    }

    /**
     * 创建精选次元后事件，消费消息队列异步执行
     *
     * @param $eventBody
     */
    public function asyncAfterCreateTopic($eventBody) {
        $topicIds = $eventBody['topicIds'];
        $topics = $this->serviceContainer->get('lychee.module.topic')->fetch($topicIds);
        foreach ($topics as $topic) {
            if (empty($topic)) {
                continue;
            }
            if ($topic->deleted) {
                continue;
            }
            try {
                $this->serviceContainer->get('lychee.module.search.superiorTopicIndexer')->add($topic);
            } catch (ResponseException $e){}
        }
        return true;
    }

    /**
     * 删除精选次元后事件，消费消息队列异步执行
     *
     * @param $eventBody
     */
    public function asyncAfterDeleteTopic($eventBody) {
        $topicIds = $eventBody['topicIds'];
        $topics = $this->serviceContainer->get('lychee.module.topic')->fetch($topicIds);
        foreach ($topics as $topic) {
            if (empty($topic)) {
                continue;
            }
            try {
                $this->serviceContainer->get('lychee.module.search.superiorTopicIndexer')->remove($topic);
            } catch (ResponseException $e){}
        }
        return true;
    }
}