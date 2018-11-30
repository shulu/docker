<?php
namespace Lychee\Module\Post;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\Query;
use Elastica\Exception\ResponseException;
use Lychee\Bundle\ApiBundle\Error\ErrorsException;
use Lychee\Bundle\ApiBundle\Error\LiveError;
use Lychee\Bundle\ApiBundle\Error\PostError;
use Lychee\Bundle\CoreBundle\Entity\PostCounting;
use Lychee\Bundle\CoreBundle\Entity\TopicChatPost;
use Lychee\Bundle\CoreBundle\Entity\TopicPost;
use Lychee\Bundle\CoreBundle\Entity\UserPost;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Foundation\CursorableIterator\QueryCursorableIterator;
use Lychee\Component\Foundation\CursorableIterator\CursorableIterator;
use Lychee\Component\Foundation\IteratorTrait;
use Lychee\Component\KVStorage\DoctrineStorage;
use Lychee\Component\KVStorage\MemcacheStorage;
use Lychee\Module\Post\Exception\PostHasDeletedException;
use Lychee\Module\Post\Exception\PostNotFoundException;
use Lychee\Module\Search\PostIndexer;
use Lychee\Module\Topic\Entity\Topic;
use Lychee\Module\Topic\Entity\TopicUserFollowing;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Lsw\MemcacheBundle\Cache\MemcacheInterface;
use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Component\KVStorage\CachedDoctrineStorage;
use Lychee\Component\IdGenerator\IdGenerator;
use Lychee\Module\Topic\TopicService;
use Lychee\Module\Post\Entity\TopicStickyPost;
use Lychee\Module\Account\AccountService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Lychee\Module\Post\StickyService;
use Lychee\Module\Schedule\ScheduleService;
use Lychee\Component\Foundation\ImageUtility;

class PostService {

    use IteratorTrait;

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var CachedDoctrineStorage
     */
    private $storage;

    /**
     * @var DoctrineStorage
     */
    private $countingStorage;

    /**
     * @var IdGenerator
     */
    private $idGenerator;

    /**
     * @var TopicService
     */
    private $topicService;

    /**
     * @var AccountService
     */
    private $accountService;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var StickyService
     */
    private $stickyService;

    private $scheduleService;

    /**
     * @var ContainerInterface
     */
    private $serviceContainer;

    /**
     * @param ManagerRegistry $registry
     * @param MemcacheInterface $memcache
     * @param IdGenerator $idGenerator
     * @param TopicService $topicService
     * @param AccountService $accountService
     * @param EventDispatcherInterface $eventDispatcher
     * @param StickyService $stickyService
     * @param ScheduleService $scheduleService
     */
    public function __construct(
        $registry,
        $memcache,
        $idGenerator,
        $topicService,
        $accountService,
        $eventDispatcher,
        $stickyService,
        $scheduleService,
        $serviceContainer
    ) {
        $this->doctrine = $registry;
        $this->entityManager = $registry->getManager($registry->getDefaultManagerName());

        $cacheStorage = new MemcacheStorage($memcache, 'post:', 86400);
        $this->storage = new CachedDoctrineStorage($this->entityManager, 'LycheeCoreBundle:Post', $cacheStorage);

        $this->countingStorage = new DoctrineStorage(
            $this->entityManager, 'LycheeCoreBundle:PostCounting'
        );

        $this->idGenerator = $idGenerator;

        $this->topicService = $topicService;
        $this->accountService = $accountService;
        $this->eventDispatcher = $eventDispatcher;
        $this->stickyService = $stickyService;
        $this->scheduleService = $scheduleService;
        $this->serviceContainer = $serviceContainer;
    }

    private function validateContent($content) {
        return preg_match('/(yun|pan).baidu.com/', $content);
    }

    /**
     * @param PostParameter $parameters
     * @return Post
     * @throws \Exception
     */
    public function create($parameters) {
        $post = $this->buildPostFromParameters($parameters);

        try {
            $this->entityManager->beginTransaction();
            $this->storage->set(null, $post);

            $counting = new PostCounting();
            $counting->postId = $post->id;
            $this->countingStorage->set($post->id, $counting);

            $this->userAddPost($post->authorId, $post);
            if ($post->topicId > 0) {
                $this->topicAddPost($post->topicId, $post);
            }

            $cityId = $parameters->getCityId();
            if(isset($cityId)){
	            $this->cityAddPost($cityId, $post, $parameters->getAuthorId());
            }

            $type = $parameters->getType();
            $isVip = $parameters->getIsVip();
            if($type == POST::TYPE_LIVE && $isVip == 1){
            	$this->addLivePost($post);
            }
            if($type == POST::TYPE_SHORT_VIDEO){
                $this->addShortVideoPost($post, $parameters->getSVId(), $parameters->getBgmId());
            }

            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
        $this->eventDispatcher->dispatch(PostEvent::CREATE, new PostEvent($post->id));

        // 新业务接该事件
        $event = [];
        $event['postId'] = $post->id;
        $event['type'] = $post->type;
        $this->dispatchEvent('post.create', $event);

        return $post;
    }

    private function dispatchEvent($eventName, $event)
    {
        $this->serviceContainer->get('lychee.dynamic_dispatcher_async')->dispatch($eventName, $event);
    }

    /**
     * @param PostParameter $parameters
     * @return Post
     */
    private function buildPostFromParameters($parameters) {
        $post = new Post();
        $postId = $parameters->getPostId();
        $post->id = $postId == null ? $this->idGenerator->generate() : $postId;
        $post->createTime = new \DateTime();
        $post->content = $parameters->getContent();
        $post->authorId = $parameters->getAuthorId();
        $post->topicId = $parameters->getTopicId();
        $post->imageUrl = $parameters->getImageUrl();
        $post->videoUrl = $parameters->getVideoUrl();
        $post->audioUrl = $parameters->getAudioUrl();
        $post->siteUrl = $parameters->getSiteUrl();
        $post->longitude = $parameters->getLongitude();
        $post->latitude = $parameters->getLatitude();
        $post->address = $parameters->getAddress();
        $post->annotation = $parameters->getAnnotation();
        $post->type = $parameters->getType();
        $post->imGroupId = $parameters->getImGroupId();
        $post->scheduleId = $parameters->getScheduleId();
        $post->votingId = $parameters->getVotingId();

        return $post;
    }

    public function increaseCommentedCounter($postId, $delta) {
        $this->increaseCounter($postId, 'commentedCount', $delta);
    }

    public function increaseLikedCounter($postId, $delta) {
        $this->increaseCounter($postId, 'likedCount', $delta);
    }

    private function increaseCounter($postId, $counterField, $delta) {
        $counting = $this->fetchOneCounting($postId);
        $query = $this->entityManager->createQuery('
            UPDATE LycheeCoreBundle:PostCounting pc
            SET pc.'.$counterField.' = pc.'.$counterField.' + :delta
            WHERE pc.postId = :postId
        ')->setParameters(array('postId' => $postId, 'delta' => $delta));
        $query->execute();
        $counting->$counterField += $delta;
        $this->entityManager->detach($counting);
    }

    /**
     * @param int $id
     *
     * @return PostCounting|null
     */
    public function fetchOneCounting($id) {
        return $this->countingStorage->get($id);
    }

    /**
     * @param array $ids
     *
     * @return array
     */
    public function fetchCountings($ids) {
        return $this->countingStorage->getMulti($ids);
    }


    public function formatPostFields($post) {
        if (empty($post)) {
            return null;
        }
        if (isset($post->imageUrl)) {
            $post->imageUrl = ImageUtility::formatUrl($post->imageUrl);
        }
        if (isset($post->annotation)) {
            $post->annotation = ImageUtility::formatUrl($post->annotation);
        }

        if (is_array($post)
        &&isset($post['imageUrl'])) {
            $post['imageUrl'] = ImageUtility::formatUrl($post['imageUrl']);
        }

        if (is_array($post)
            &&isset($post['annotation'])) {
            $post['annotation'] = ImageUtility::formatUrl($post['annotation']);
        }

        return $post;
    }

    /**
     * @param int $id
     *
     * @return Post|null
     */
    public function fetchOne($id) {
        $return = $this->storage->get($id);
        $this->formatPostFields($return);
        return $return;
    }

    /**
     * @param int[] $ids
     *
     * @return Post[]
     */
    public function fetch($ids) {
        $list =  $this->storage->getMulti($ids);
        foreach ($list as $item) {
            $this->formatPostFields($item);
        }
        return $list;
    }

    public function fetchIdsByAuthorId($authorId, $cursor, $count, &$nextCursor = null) {
        return $this->fetchIdsByAuthorIds(array($authorId), $cursor, $count, $nextCursor);
    }

    public function fetchIdsByAuthorIds($authorIds, $cursor, $count, &$nextCursor = null) {
        if ($count === 0 || count($authorIds) == 0) {
            $nextCursor = $cursor;
            return array();
        }
        if ($cursor === 0) {
            $cursor = PHP_INT_MAX;
        }

        $query = $this->entityManager->createQuery('
            SELECT up.postId
            FROM LycheeCoreBundle:UserPost up
            WHERE up.userId IN (:userIds)
            AND up.postId < :cursor
            ORDER BY up.postId DESC
        ')->setMaxResults($count);
        $query->setParameters(array('userIds' => $authorIds, 'cursor' => $cursor));
        $result = $query->getArrayResult();
        $postIds = ArrayUtility::columns($result, 'postId');

        if (count($postIds) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $postIds[count($postIds) - 1];
        }

        return $postIds;
    }

    public function fetchPassIdsByAuthorIds($authorIds, $cursor, $count, &$nextCursor = null) {
        if ($count === 0 || count($authorIds) == 0) {
            $nextCursor = $cursor;
            return array();
        }
        if ($cursor === 0) {
            $cursor = PHP_INT_MAX;
        }

        $query = $this->entityManager->createQuery('
            SELECT up.postId
            FROM LycheeCoreBundle:UserPost up
            LEFT JOIN \Lychee\Module\Post\Entity\PostAudit pa WITH up.postId=pa.postId
            WHERE up.userId IN (:userIds)
            AND ( pa.status IS NULL OR pa.status = 1 )
            AND up.postId < :cursor
            ORDER BY up.postId DESC
        ')->setMaxResults($count);
        $query->setParameters(array('userIds' => $authorIds, 'cursor' => $cursor));
        $result = $query->getArrayResult();
        $postIds = ArrayUtility::columns($result, 'postId');

        if (count($postIds) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $postIds[count($postIds) - 1];
        }

        return $postIds;
    }


    public function fetchIdsByAuthorIdsInPublicTopics($authorIds, $cursor, $count, &$nextCursor = null) {
        if ($count === 0 || count($authorIds) == 0) {
            $nextCursor = $cursor;
            return array();
        }
        if ($cursor === 0) {
            $cursor = PHP_INT_MAX;
        }
        $userTopics = $this->entityManager->getRepository(TopicUserFollowing::class)->createQueryBuilder('tuf')
            ->select('tuf.topicId')
            ->where('tuf.userId IN (:userIds)')
            ->setParameter('userIds',$authorIds)
            ->getQuery()
            ->getResult();
        $privateTopics = $this->entityManager->getRepository(Topic::class)->createQueryBuilder('t')
            ->select('t.id')
            ->where('t.id IN (:userTopics) AND t.private=1')
            ->setParameter('userTopics', $userTopics)
            ->getQuery()
            ->getResult();
        if (!$privateTopics) {
            $privateTopics = [''];
        }
        $query = $this->entityManager->createQuery('
            SELECT up.postId
            FROM LycheeCoreBundle:UserPost up
            WHERE up.userId IN (:userIds)
            AND up.topicId NOT IN (:privateTopics)
            AND up.postId < :cursor
            ORDER BY up.postId DESC
        ')->setMaxResults($count);
        $query->setParameters(array('userIds' => $authorIds,'privateTopics' => $privateTopics , 'cursor' => $cursor));
        $result = $query->getArrayResult();
        $postIds = ArrayUtility::columns($result, 'postId');

        if (count($postIds) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $postIds[count($postIds) - 1];
        }

        return $postIds;
    }
    public function fetchIdsByAuthorIdInPublicTopic($authorId, $cursor, $count, &$nextCursor = null) {
        if ($count == 0) {
            $nextCursor = $cursor;
            return array();
        }
        if ($cursor == 0) {
            $cursor = PHP_INT_MAX;
        }

        $sql = 'SELECT u.post_id FROM user_post u LEFT JOIN topic t ON u.topic_id = t.id'
            .' WHERE u.user_id = ? AND u.post_id < ? AND t.private = 0 ORDER BY u.post_id DESC LIMIT ?';
        $stat = $this->entityManager->getConnection()->executeQuery($sql, array($authorId, $cursor, $count),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        $postIds = ArrayUtility::columns($rows, 'post_id');

        if (count($postIds) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $postIds[count($postIds) - 1];
        }

        return $postIds;
    }

    /**
     * @param int $userId
     * @param Post $post
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function userAddPost($userId, $post) {
        $conn = $this->entityManager->getConnection();
        $sql = 'INSERT INTO user_post(user_id, post_id, topic_id) VALUES(?, ?, ?)'
            . ' ON DUPLICATE KEY UPDATE topic_id = topic_id';
        $affectedRows = $conn->executeUpdate($sql, array($userId, $post->id, $post->topicId),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));

        if ($affectedRows == 1) {
            $this->accountService->increasePostCounter($userId, 1);
        }
    }

	/**
	 * @param string $city
	 *
	 * @return int
	 */
    public function getCityId($city){

	    $sql = 'SELECT id FROM city WHERE name = ?';
	    $stat = $this->entityManager->getConnection()->executeQuery($sql, array($city),
		    array(\PDO::PARAM_STR));
	    $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
	    $cityIds = ArrayUtility::columns($rows, 'id');

	    if(count($cityIds) <= 0){
			return -1;
	    }

	    return $cityIds[0];
    }

	/**
	 * @param int $cityId
	 * @param Post $post
	 * @param int $authorId
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 */
    private function cityAddPost($cityId, $post, $authorId){

	    $conn = $this->entityManager->getConnection();
	    $sql = 'INSERT INTO city_post(city_id, post_id, author_id) VALUES(?, ?, ?)';
	    $affectedRows = $conn->executeUpdate($sql, array($cityId, $post->id, $authorId),
		    array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));

	    return $affectedRows;
    }

	/**
	 * @param Post $post
	 *
	 * @return int
	 */
	private function addLivePost($post){

		$conn = $this->entityManager->getConnection();
		$sql = 'INSERT INTO live_post (author_id, post_id) VALUES(?, ?)';
		$affectedRows = $conn->executeUpdate($sql, array($post->authorId, $post->id),
			array(\PDO::PARAM_INT, \PDO::PARAM_INT));

		return $affectedRows;
	}

	public function finishLivePost($authorId){

		$conn = $this->entityManager->getConnection();
		$sql = 'UPDATE live_post SET finish = 1 WHERE author_id = ?';
		$affectedRows = $conn->executeUpdate($sql, array($authorId),
			array(\PDO::PARAM_INT));

		return $affectedRows;
	}

	/**
     * @param int $userId
     * @param Post $post
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function userRemovePost($userId, $post) {
        $conn = $this->entityManager->getConnection();
        $sql = 'DELETE FROM user_post WHERE user_id = ? AND post_id = ?';
        $affectedRows = $conn->executeUpdate($sql, array($userId, $post->id),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT));

        if ($affectedRows == 1) {
            $this->accountService->increasePostCounter($userId, -1);
        }
    }

    public function fetchIdsByTopicId($topicId, $cursor, $count, &$nextCursor = null) {
        return $this->fetchIdsByTopicIds(array($topicId), $cursor, $count, $nextCursor);
    }

    public function fetchIdsByTopicIdForClient($topicId, $cursor, $count, &$nextCursor = null, $client=null) {

        if (strtolower($client)=='ios') {
            return $this->fetchPassIdsByTopicId($topicId, $cursor, $count, $nextCursor);
        }

        return $this->fetchIdsByTopicId($topicId, $cursor, $count, $nextCursor);
    }


	public function fetchVIPLivePosts(){

		$sql = 'SELECT id FROM post AS p
 				INNER JOIN user_vip AS uv ON p.author_id = uv.user_id
				WHERE p.type = 4 AND p. ORDER BY p.id DESC LIMIT 0, 10';
		$stat = $this->entityManager->getConnection()->executeQuery($sql, array($cityId, $cursor, $count),
			array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
	}

    public function fetchIdsByCityId($cityId, $cursor, $count, &$nextCursor = null, $sex = null, $minAge = null, $maxAge = null){

	    if ($count === 0) {
		    $nextCursor = $cursor;
		    return array();
	    }
	    if ($cursor === 0) {
		    $cursor = PHP_INT_MAX;
	    }

	    if($sex == null && $minAge == null){

		    $sql = 'SELECT post_id FROM city_post WHERE city_id = ? AND post_id < ? ORDER BY id DESC LIMIT 0, ?';
		    $stat = $this->entityManager->getConnection()->executeQuery($sql, array($cityId, $cursor, $count),
			    array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));

	    } else if($sex != null && $minAge == null){

		    $sql = 'SELECT cp.post_id FROM city_post AS cp INNER JOIN `user` AS u 
					ON cp.author_id = u.id
					WHERE u.gender = ? AND cp.city_id = ? AND cp.post_id < ?   
					ORDER BY cp.id DESC LIMIT 0, ?';
		    $stat = $this->entityManager->getConnection()->executeQuery($sql, array($sex, $cityId, $cursor, $count),
			    array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));

	    } else if($sex == null && $minAge != null){

		    $sql = 'SELECT cp.post_id FROM city_post AS cp INNER JOIN `user_profile` AS up 
					ON cp.author_id = up.user_id
					WHERE up.age >= ? AND up.age <= ? AND cp.city_id = ? AND cp.post_id < ?   
					ORDER BY cp.id DESC LIMIT 0, ?';
		    $stat = $this->entityManager->getConnection()->executeQuery($sql, array($minAge, $maxAge, $cityId, $cursor, $count),
			    array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));

	    } else if($sex != null && $minAge != null){

		    $sql = 'SELECT cp.post_id FROM city_post AS cp 
					INNER JOIN `user_profile` AS up 
					ON cp.author_id = up.user_id
					INNER JOIN `user` AS u 
					ON u.id = cp.author_id
					WHERE u.gender = ? AND up.age >= ? AND up.age <= ? AND cp.city_id = ? AND cp.post_id < ?   
					ORDER BY cp.id DESC LIMIT 0, ?';
		    $stat = $this->entityManager->getConnection()->executeQuery($sql, array($sex, $minAge, $maxAge, $cityId, $cursor, $count),
			    array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));

	    }

	    $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
	    $postIds = ArrayUtility::columns($rows, 'post_id');
	    if (count($postIds) < $count) {
		    $nextCursor = 0;
	    } else {
		    $nextCursor = $postIds[count($postIds) - 1];
	    }

	    return $postIds;
    }






    public function fetchIdsByTopicIds($topicIds, $cursor, $count, &$nextCursor = null) {
        if ($count === 0) {
            $nextCursor = $cursor;
            return array();
        }
        if ($cursor === 0) {
            $cursor = PHP_INT_MAX;
        }

        $query = $this->entityManager->createQuery('
            SELECT tp.postId
            FROM LycheeCoreBundle:TopicPost tp
            WHERE tp.topicId IN (:topicIds)
            AND tp.postId < :cursor
            ORDER BY tp.postId DESC
        ')->setMaxResults($count);
        $result = $query->execute(array('topicIds' => $topicIds, 'cursor' => $cursor));
        $postIds = ArrayUtility::columns($result, 'postId');
        if (count($postIds) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $postIds[count($postIds) - 1];
        }

        return $postIds;
    }


    public function fetchPassIdsByTopicId($topicId, $cursor, $count, &$nextCursor = null) {
        return $this->fetchPassIdsByTopicIds(array($topicId), $cursor, $count, $nextCursor);
    }

    /**
     * 根据次元id，获取审核通过的帖子id
     * @param array  次元id数组
     * @param int    结果必须>该帖子id
     * @param int    限制查询的记录数
     * @param int    最后一条帖子id
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function fetchPassIdsByTopicIds($topicIds, $cursor, $count, &$nextCursor = null) {
        if ($count === 0) {
            $nextCursor = $cursor;
            return array();
        }
        if ($cursor === 0) {
            $cursor = PHP_INT_MAX;
        }

        $query = $this->entityManager->createQuery('
            SELECT tp.postId
            FROM LycheeCoreBundle:TopicPost tp
            LEFT JOIN \Lychee\Module\Post\Entity\PostAudit pa WITH tp.postId=pa.postId
            WHERE tp.topicId IN (:topicIds)
            AND ( pa.status IS NULL OR pa.status = :auditStatus )
            AND tp.postId < :cursor
            ORDER BY tp.postId DESC
        ')->setMaxResults($count);
        $result = $query->execute(array(
            'topicIds' => $topicIds,
            'cursor' => $cursor,
            'auditStatus'=>\Lychee\Module\Post\Entity\PostAudit::PASS_STATUS));
        $postIds = ArrayUtility::columns($result, 'postId');
        if (count($postIds) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $postIds[count($postIds) - 1];
        }

        return $postIds;
    }

    /**
     * 筛选出审核通过的帖子id
     * @param array    提供筛选的帖子id
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function filterPassIds($postIds) {
        if (empty($postIds)) {
            return [];
        }

        $ret = [];
        $i = 1;
        $chunk = [];
        $statusMapping = [];
        $count = count($postIds);

        foreach ($postIds as $postId) {
            $chunk[] = $postId;
            if (0==$i%500
            ||$i>=$count) {

                $query = $this->entityManager->createQuery('
                    SELECT pa.postId, pa.status
                    FROM \Lychee\Module\Post\Entity\PostAudit pa
            WHERE pa.postId in (:postIds) 
        ');
                $query->setParameters(array(
                    'postIds' => $postIds
                ));
                $result = $query->getArrayResult();
                foreach ($result as $item) {
                    $statusMapping[$item['postId']] = $item['status'];
                }
                $chunk = [];
            }

            $i++;
        }
        foreach ($postIds as $postId) {
            if (isset($statusMapping[$postId])
                && $statusMapping[$postId]!=\Lychee\Module\Post\Entity\PostAudit::PASS_STATUS) {
                continue;
            }
            $ret[] = $postId;
        }
        return $ret;
    }

    public function fetchIdsByAuthorIdAndTopicId($userId, $topicId, $cursor, $count, &$nextCursor = null) {
        if ($count === 0) {
            $nextCursor = $cursor;
            return array();
        }
        if ($cursor === 0) {
            $cursor = PHP_INT_MAX;
        }

        $query = $this->entityManager->createQuery('
            SELECT up.postId
            FROM LycheeCoreBundle:UserPost up
            WHERE up.userId = :userId AND up.topicId = :topicId
            AND up.postId < :cursor
            ORDER BY up.postId DESC
        ')->setMaxResults($count);
        $query->setParameters(array('userId' => $userId, 'topicId' => $topicId, 'cursor' => $cursor));
        $result = $query->getArrayResult();
        $postIds = ArrayUtility::columns($result, 'postId');

        if (count($postIds) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $postIds[count($postIds) - 1];
        }

        return $postIds;
    }

    public function fetchLatestIdsGroupByTopicId($topicIds, $count) {
        $queries = array();
        foreach ($topicIds as $topicId) {
            $queries[] = sprintf('(SELECT topic_id, post_id FROM topic_post
                WHERE topic_id = ? ORDER BY post_id DESC LIMIT %d)', $count);
        }
        $query = implode('UNION', $queries);
        $statement = $this->entityManager->getConnection()->executeQuery($query, $topicIds);
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return ArrayUtility::columnsGroupBy($result, 'post_id', 'topic_id');
    }

    public function fetchIdsWithImageByTopicId($topicId, $cursor, $count, &$nextCursor = null) {
        if ($count === 0) {
            $nextCursor = $cursor;
            return array();
        }
        if ($cursor === 0) {
            $cursor = PHP_INT_MAX;
        }

        $query = $this->entityManager->createQuery('
            SELECT tp.postId
            FROM LycheeCoreBundle:TopicPost tp
            LEFT JOIN LycheeCoreBundle:Post p WITH tp.postId = p.id
            WHERE tp.topicId = :topicId
            AND tp.postId < :cursor
            AND p.imageUrl IS NOT NULL
            ORDER BY tp.postId DESC
        ')->setMaxResults($count);
        $result = $query->execute(array('topicId' => $topicId, 'cursor' => $cursor));
        $postIds = ArrayUtility::columns($result, 'postId');
        if (count($postIds) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $postIds[count($postIds) - 1];
        }

        return $postIds;
    }

    public function fetchIdsWithChatByTopicId($topicId, $cursor, $count, &$nextCursor = null) {
        if ($count === 0) {
            $nextCursor = $cursor;
            return array();
        }
        if ($cursor === 0) {
            $cursor = PHP_INT_MAX;
        }

        $query = $this->entityManager->createQuery('
            SELECT tcp.postId
            FROM LycheeCoreBundle:TopicChatPost tcp
            WHERE tcp.topicId = :topicId
            AND tcp.postId < :cursor
            ORDER BY tcp.postId DESC
        ')->setMaxResults($count);
        $result = $query->execute(array('topicId' => $topicId, 'cursor' => $cursor));
        $postIds = ArrayUtility::columns($result, 'postId');
        if (count($postIds) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $postIds[count($postIds) - 1];
        }

        return $postIds;
    }

    /**
     * @param int $topicId
     * @param Post $post
     */
    private function topicAddPost($topicId, $post) {
        $conn = $this->entityManager->getConnection();
        $topicAddPostSql = 'INSERT INTO topic_post(topic_id, post_id) VALUES(?, ?)'
            . ' ON DUPLICATE KEY UPDATE post_id = post_id';
        $affectedRows = $conn->executeUpdate($topicAddPostSql, array($topicId, $post->id),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT));

        if ($post->imGroupId) {
            $topicAddChatPostSql = 'INSERT INTO topic_chat_post(topic_id, post_id) VALUES(?, ?)'
                . ' ON DUPLICATE KEY UPDATE post_id = post_id';
            $conn->executeUpdate($topicAddChatPostSql, array($topicId, $post->id),
                array(\PDO::PARAM_INT, \PDO::PARAM_INT));
        }

        if ($affectedRows == 1) {
            $this->topicService->increasePostCounter($topicId, 1);
        }
    }

    /**
     * @param int $topicId
     * @param Post $post
     */
    private function topicRemovePost($topicId, $post) {
        $conn = $this->entityManager->getConnection();
        $topicRemovePostSql = 'DELETE FROM topic_post WHERE topic_id = ? AND post_id = ?';
        $affectedRows = $conn->executeUpdate($topicRemovePostSql, array($topicId, $post->id),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT));

        if ($affectedRows == 1) {
            if ($post->imGroupId) {
                $topicRemoveChatPostSql = 'DELETE FROM topic_chat_post WHERE topic_id = ? AND post_id = ?';
                $conn->executeUpdate($topicRemoveChatPostSql, array($topicId, $post->id),
                    array(\PDO::PARAM_INT, \PDO::PARAM_INT));
            }
            $this->stickyService->unstickPost($post->id);
            $this->topicService->increasePostCounter($topicId, -1);
        }
    }

    /**
     * @param \Iterator $authorIdsIterator
     * @param \Iterator $topicIdsIterator
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     *
     * @return array
     */
    public function fetchIdsByAuthorIdsAndTopicIds(
        $authorIdsIterator, $topicIdsIterator, $cursor, $count, &$nextCursor = null
    ) {
        $ids = array();
        if ($authorIdsIterator) {
            $idsByAuthors = array();
            foreach ($authorIdsIterator as $authorIds) {
                $particalIdsByAuthors = $this->fetchIdsByAuthorIds($authorIds, $cursor, $count);
                $idsByAuthors = $this->mergeIds($idsByAuthors, $particalIdsByAuthors, $count);
            }
            $ids = $this->mergeIds($ids, $idsByAuthors, $count);
        }
        if ($topicIdsIterator) {
            $idsByTopics = array();
            foreach ($topicIdsIterator as $topicIds) {
                $particalIdsByTopics = $this->fetchIdsByTopicIds($topicIds, $cursor, $count);
                $idsByTopics = $this->mergeIds($idsByTopics, $particalIdsByTopics, $count);
            }
            $ids = $this->mergeIds($ids, $idsByTopics, $count);
        }

        if (count($ids) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $ids[count($ids) - 1];
        }

        return $ids;
    }

    /**
     * @param \Iterator $authorIdsIterator
     * @param \Iterator $topicIdsIterator
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     *
     * @return array
     */
    public function fetchIdsByAuthorIdsAndTopicIdsForClient(
        $authorIdsIterator, $topicIdsIterator, $cursor, $count, &$nextCursor = null, $client = null
    ) {
        if (strtolower($client)=='ios') {
            return $this->fetchPassIdsByAuthorIdsAndTopicIds($authorIdsIterator, $topicIdsIterator, $cursor, $count, $nextCursor);
        }
        return $this->fetchIdsByAuthorIdsAndTopicIds($authorIdsIterator, $topicIdsIterator, $cursor, $count, $nextCursor);
    }

    /**
     * @param \Iterator $authorIdsIterator
     * @param \Iterator $topicIdsIterator
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     *
     * @return array
     */
    public function fetchPassIdsByAuthorIdsAndTopicIds(
        $authorIdsIterator, $topicIdsIterator, $cursor, $count, &$nextCursor = null, $client = null
    ) {
        $ids = array();
        if ($authorIdsIterator) {
            $idsByAuthors = array();
            foreach ($authorIdsIterator as $authorIds) {
                $particalIdsByAuthors = $this->fetchPassIdsByAuthorIds($authorIds, $cursor, $count);
                $idsByAuthors = $this->mergeIds($idsByAuthors, $particalIdsByAuthors, $count);
            }
            $ids = $this->mergeIds($ids, $idsByAuthors, $count);
        }
        if ($topicIdsIterator) {
            $idsByTopics = array();
            foreach ($topicIdsIterator as $topicIds) {
                $particalIdsByTopics = $this->fetchPassIdsByTopicIds($topicIds, $cursor, $count);
                $idsByTopics = $this->mergeIds($idsByTopics, $particalIdsByTopics, $count);
            }
            $ids = $this->mergeIds($ids, $idsByTopics, $count);
        }

        if (count($ids) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $ids[count($ids) - 1];
        }

        return $ids;
    }


    /**
     * @param \Iterator $authorIdsIterator
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     *
     * @return array
     */
    public function fetchPublicIdsByAuthorIds(
        $authorIdsIterator, $cursor, $count, &$nextCursor = null
    ) {
        $ids = array();
        if ($authorIdsIterator) {
            $idsByAuthors = array();
            foreach ($authorIdsIterator as $authorIds) {
                $particalIdsByAuthors = $this->fetchIdsByAuthorIdsInPublicTopics($authorIds, $cursor, $count);
                $idsByAuthors = $this->mergeIds($idsByAuthors, $particalIdsByAuthors, $count);
            }
            $ids = $this->mergeIds($ids, $idsByAuthors, $count);
        }

        if (count($ids) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $ids[count($ids) - 1];
        }

        return $ids;
    }

    private function mergeIds($ids1, $ids2, $maxCount = null) {
        $result = array();
        if ($maxCount === null) {
            while(!empty($ids1) && !empty($ids2)){
                $result[] = $ids1[0] >= $ids2[0] ? array_shift($ids1) : array_shift($ids2);
            }
            $result = array_merge($result, $ids1, $ids2);
        } else {
            while(!empty($ids1) && !empty($ids2) && count($result) < $maxCount){
                $result[] = $ids1[0] >= $ids2[0] ? array_shift($ids1) : array_shift($ids2);
            }
            $result = array_merge($result, $ids1, $ids2);
            $result = array_slice($result, 0, $maxCount);
        }

        return $result;
    }

    /**
     * @param int $postId
     * @param int|null $deleterId
     * @throws \Exception
     * @throws PostNotFoundException
     */
    public function delete($postId, $deleterId = null) {
        $post = $this->fetchOne($postId);
        if ($post === null) {
            throw new PostNotFoundException();
        }
        if ($post->deleted === true) {
            return;
        }

        try {
            $this->entityManager->beginTransaction();
            $post->deleted = true;
            $this->storage->set($post->id, $post);
            $this->userRemovePost($post->authorId, $post);
            if ($post->topicId) {
                $this->topicRemovePost($post->topicId, $post);
            }
            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        $this->eventDispatcher->dispatch(PostEvent::DELETE, new PostEvent($post->id));

        // 新业务接该事件
        $event = [];
        $event['postId'] = $post->id;
        $this->dispatchEvent('post.delete', $event);

        if ($post->scheduleId) {
            $this->scheduleService->cancel($post->scheduleId, $deleterId ? $deleterId : $post->authorId);
        }
    }

    /**
     * @param int $postId
     * @throws \Exception
     * @throws PostNotFoundException
     */
    public function undelete($postId) {
        $post = $this->fetchOne($postId);
        if ($post === null) {
            throw new PostNotFoundException();
        }
        if ($post->deleted === false) {
            return;
        }

        try {
            $this->entityManager->beginTransaction();
            $post->deleted = false;
            $this->storage->set($post->id, $post);
            $this->userAddPost($post->authorId, $post);
            if ($post->topicId) {
                $this->topicAddPost($post->topicId, $post);
            }
            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        $this->eventDispatcher->dispatch(PostEvent::UNDELETE, new PostEvent($post->id));
    }

    /**
     * @param int $postId
     *
     * @throws PostNotFoundException
     * @throws \Exception
     */
    public function fold($postId) {
        $post = $this->fetchOne($postId);
        if ($post === null || $post->deleted === true) {
            throw new PostNotFoundException();
        }
        if ($post->folded === true) {
            return;
        }

        try {
            $this->entityManager->beginTransaction();
            $post->folded = true;
            $this->storage->set($post->id, $post);
            if ($post->topicId) {
                $this->topicRemovePost($post->topicId, $post);
            }
            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    /**
     * @param int $postId
     *
     * @throws PostNotFoundException
     * @throws \Exception
     */
    public function unfold($postId) {
        $post = $this->fetchOne($postId);
        if ($post === null || $post->deleted === true) {
            throw new PostNotFoundException();
        }
        if ($post->folded === false) {
            return;
        }

        try {
            $this->entityManager->beginTransaction();
            $post->folded = false;
            $this->storage->set($post->id, $post);
            if ($post->topicId) {
                $this->topicAddPost($post->topicId, $post);
            }
            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    /**
     * @param \DateTime $fromTime
     * @param \DateTime $toTime
     * @param int $page
     * @param int $pageLength
     * @return Post[]
     */
    public function fetchIdsAtTimeInterval($fromTime, $toTime, $page, $pageLength) {
        if ($fromTime > $toTime) {
            $upperTime = $fromTime;
            $lowerTime = $toTime;
            $order = 'DESC';
        } else {
            $upperTime = $toTime;
            $lowerTime = $fromTime;
            $order = 'ASC';
        }

        $query = $this->entityManager->createQuery('
            SELECT t FROM LycheeCoreBundle:Post t
            WHERE t.createTime >= :lowerTime
            AND t.createTime <= :upperTime
            ORDER BY t.createTime '.$order.'
        ');
        $query->setParameters(array(
            'lowerTime' => $lowerTime,
            'upperTime' => $upperTime
        ));

        $page = $page < 1 ? 1 : $page;
        $query->setFirstResult(($page - 1) * $pageLength);
        $query->setMaxResults($pageLength);
        return $query->getResult();
    }

    /**
     * @param int $postId
     * @param int|null $topicId
     * @throws \Exception
     */
    public function updateTopic($postId, $topicId) {
        try {
            $this->entityManager->beginTransaction();
            $postRef = $this->entityManager->getReference('LycheeCoreBundle:Post', $postId);
            $this->entityManager->lock($postRef, LockMode::PESSIMISTIC_READ);

            $post = $this->fetchOne($postId);
            if ($post === null || $post->topicId == $topicId) {
                $this->entityManager->rollback();
                return;
            }
            $oldTopicId = $post->topicId;

            $post->topicId = $topicId;
            $this->storage->set($postId, $post);

            if ($post->deleted == false) {
                if ($topicId != null) {
                    $this->topicAddPost($topicId, $post);
                }
                if ($oldTopicId != null) {
                    $this->topicRemovePost($oldTopicId, $post);
                }
            }

            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    /**
     * @param $post
     */
    public function update($post) {
        $this->storage->set($post->id, $post);
        $this->eventDispatcher->dispatch(PostEvent::UPDATE, new PostEvent($post->id));
    }

    /**
     * @param string $order
     * @return QueryCursorableIterator
     */
    public function iteratePost($order = 'ASC')
    {
        return $this->iterateEntity($this->entityManager, 'LycheeCoreBundle:Post', 'id', $order);
    }

    /**
     * @param \DateTime $createTime
     * @return QueryCursorableIterator
     */
    public function iteratePostByCreateTime(\DateTime $createTime)
    {
        return $this->iterateEntityByCreateTime($this->entityManager, Post::class, 'id', 'createTime', $createTime);
    }

    /**
     * @param \DateTime $createTime
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCursorAfterCreateTime(\DateTime $createTime)
    {
        $postRepo = $this->entityManager->getRepository(Post::class);
        $query = $postRepo->createQueryBuilder('p')
            ->select('p.id')
            ->where('p.createTime < :date')
            ->setParameter('date', $createTime)
            ->orderBy('p.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery();
        $minIdResult = $query->getOneOrNullResult();

        return (null === $minIdResult)? 0 : $minIdResult['id'];
    }

    /**
     * @param $startDatetime
     * @param $endDatetime
     * @return QueryCursorableIterator
     */
//    public function iteratePostByIdAndCreateTime(\DateTime $startDatetime, \DateTime $endDatetime)
//    {
//        $postRepo = $this->entityManager->getRepository(Post::class);
//        $query = $postRepo->createQueryBuilder('p')
//            ->where('p.id < :cursor')
//            ->andWhere('p.createTime <= :startDatetime')
//            ->andWhere('p.createTime > :endDatetime')
//            ->setParameter('startDatetime', $startDatetime)
//            ->setParameter('endDatetime', $endDatetime)
//            ->orderBy('p.id', 'DESC')
//            ->getQuery();
//
//        return new QueryCursorableIterator($query, 'id');
//    }

    /**
     * @param $redLineType
     * @param $frequentTopics
     * @param \DateTime $startTime
     * @param \DateTime $endTime
     * @return QueryCursorableIterator
     */
    public function iterateWithRedLine($redLineType, $frequentTopics, \DateTime $startTime, \DateTime $endTime) {
        $postRepo = $this->entityManager->getRepository(Post::class);
        $qb = $postRepo->createQueryBuilder('p')->where('p.id < :cursor');
        if (!empty($frequentTopics)) {
            if ($redLineType == 1) {
                $qb->andWhere('p.topicId IN (:topics)');
            } else {
                $qb->andWhere('p.topicId NOT IN (:topics)');
            }
        }
        $qb->andWhere('p.createTime <= :startDatetime')
            ->andWhere('p.createTime > :endDatetime')
            ->setParameter('startDatetime', $startTime)
            ->setParameter('endDatetime', $endTime);
        if (!empty($frequentTopics)) {
            $qb->setParameter('topics', $frequentTopics);
        }
        $query = $qb->orderBy('p.id', 'DESC')->getQuery();

        return new QueryCursorableIterator($query, 'id');
    }

    public function iterateFolded(\DateTime $startTime, \DateTime $endTime) {
        $postRepo = $this->entityManager->getRepository(Post::class);
        $qb = $postRepo->createQueryBuilder('p')->where('p.id < :cursor');
        $query = $qb->andWhere('p.folded = :folded')
            ->andWhere('p.deleted = 0')
            ->andWhere('p.createTime <= :startDatetime')
            ->andWhere('p.createTime > :endDatetime')
            ->setParameter('folded', true)
            ->setParameter('startDatetime', $startTime)
            ->setParameter('endDatetime', $endTime)
            ->orderBy('p.id', 'DESC')
            ->getQuery();

        return new QueryCursorableIterator($query, 'id');
    }

    public function fetchPostIdsBySpecifiedTopics($cursor = 0, $topicIds, $inTopics, \DateTime $startTime, \DateTime $endTime, $topicPrivate = false, $count = 15) {
        $conn = $this->entityManager->getConnection();
        if ($cursor == 0) {
            $cursor = PHP_INT_MAX;
        }
        if ($topicPrivate === true) {
            $topicPrivate = 1;
        } else {
            $topicPrivate = 0;
        }
        $topicIdsStr = implode(',', $topicIds);
        if(true === $inTopics) {
            // 红线上
            if (empty($topicIds)) {
                return [];
            } else {
                $sql =
                    "SELECT p.id
                FROM post p, topic t
                WHERE p.id < :cursor AND p.create_time <= :startTime AND p.create_time > :endTime
                      AND p.topic_id IN (${topicIdsStr}) AND p.topic_id = t.id AND t.private = :topicPrivate
                ORDER BY p.id DESC
                LIMIT ${count}";
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':cursor', $cursor);
                $stmt->bindValue(':startTime', $startTime->format('Y-m-d H:i:s'));
                $stmt->bindValue(':endTime', $endTime->format('Y-m-d H:i:s'));
                $stmt->bindValue(':topicPrivate', $topicPrivate);
                $stmt->execute();
                $result = $stmt->fetchAll();
            }
        } else {
            // 红线下
            if (empty($topicIds)) {
                $sql =
                    "SELECT p.id
                FROM post p, topic t
                WHERE p.id < :cursor AND p.create_time <= :startTime AND p.create_time > :endTime
                      AND p.topic_id = t.id AND t.private = :topicPrivate
                ORDER BY p.id DESC
                LIMIT ${count}";
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':cursor', $cursor);
                $stmt->bindValue(':startTime', $startTime->format('Y-m-d H:i:s'));
                $stmt->bindValue(':endTime', $endTime->format('Y-m-d H:i:s'));
                $stmt->bindValue(':topicPrivate', $topicPrivate);
                $stmt->execute();
                $result = $stmt->fetchAll();
            } else {
                $sql =
                    "SELECT p.id
                FROM post p, topic t
                WHERE p.id < :cursor AND p.create_time <= :startTime AND p.create_time > :endTime
                      AND p.topic_id NOT IN (${topicIdsStr}) AND p.topic_id = t.id AND t.private = :topicPrivate
                ORDER BY p.id DESC
                LIMIT ${count}";
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':cursor', $cursor);
                $stmt->bindValue(':startTime', $startTime->format('Y-m-d H:i:s'));
                $stmt->bindValue(':endTime', $endTime->format('Y-m-d H:i:s'));
                $stmt->bindValue(':topicPrivate', $topicPrivate);
                $stmt->execute();
                $result = $stmt->fetchAll();
            }
        }

        return array_map(function($post) {
            return $post['id'];
        }, $result);
    }

    /**
     * @param int $cursor
     * @param $count
     * @param null $nextCursor
     * @return array
     */
    public function fetchIdsByCursor($cursor = 0, $count, &$nextCursor = null) {
        if ($count === 0) {
            $nextCursor = $cursor;
            return array();
        }

        $query = $this->entityManager->createQuery('
            SELECT p.id
            FROM LycheeCoreBundle:Post p
            WHERE p.id > :cursor
            ORDER BY p.id
        ')->setMaxResults($count);
        $query->setParameter('cursor', $cursor);
        $result = $query->getArrayResult();
        $postIds = ArrayUtility::columns($result, 'id');

        if (count($postIds) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $postIds[count($postIds) - 1];
        }

        return $postIds;
    }

	/**
	 * @param int $cursor
	 * @param $count
	 * @param null $nextCursor
	 * @param null $startTime
	 * @param null $endTime
	 *
	 * @return array
	 */
    public function fetchIdsByDesc($cursor = 0, $count, &$nextCursor = null, $startTime = null, $endTime = null) {
    	0 == $cursor && $cursor = PHP_INT_MAX;
	    $sql = 'SELECT p.id
	    	FROM LycheeCoreBundle:Post p
	    	WHERE p.id < :cursor';
	    if ($startTime && $endTime) {
		    $sql .= ' AND p.createTime < :endTime AND p.createTime >= :startTime';
	    }
	    $sql .= ' ORDER BY p.id DESC';
	    $query = $this->entityManager->createQuery($sql)->setMaxResults($count);
	    $query->setParameter('cursor', $cursor);
	    if ($startTime && $endTime) {
	    	$query->setParameter('startTime', $startTime);
	    	$query->setParameter('endTime', $endTime);
	    }
	    $result = $query->getArrayResult();
	    $postIds = ArrayUtility::columns($result, 'id');
	    if (count($postIds) < $count) {
	    	$nextCursor = 0;
	    } else {
	    	$nextCursor = $postIds[count($postIds) - 1];
	    }

	    return $postIds;
    }

    public function fetchIdsByTopicIdsPerHour($topicIds, \DateTime $datetime, $cursor, $count, &$nextCursor = null) {
        if ($count === 0) {
            $nextCursor = $cursor;
            return array();
        }
        if ($cursor === 0) {
            $cursor = PHP_INT_MAX;
        }
        $endDatetime = clone $datetime;
        $endDatetime->modify('+1 hour');

        $query = $this->entityManager->createQuery('
            SELECT tp.postId
            FROM LycheeCoreBundle:TopicPost tp
            JOIN LycheeCoreBundle:Post p WITH p.id=tp.postId
            WHERE tp.topicId IN (:topicIds)
            AND tp.postId < :cursor
            AND p.createTime >= :start
            AND p.createTime < :end
            ORDER BY tp.postId DESC
        ')->setMaxResults($count);
        $result = $query->execute(array(
            'topicIds' => $topicIds,
            'cursor' => $cursor,
            'start' => $datetime->format('Y-m-d H:i:s'),
            'end' => $endDatetime->format('Y-m-d H:i:s'),
        ));
        $postIds = ArrayUtility::columns($result, 'postId');
        if (count($postIds) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $postIds[count($postIds) - 1];
        }

        return $postIds;
    }

    public function fetchIdsByAuthorIdsPerHour($authorIds, \DateTime $datetime, $cursor, $count, &$nextCursor = null) {
        if ($count === 0 || count($authorIds) == 0) {
            $nextCursor = $cursor;
            return array();
        }
        if ($cursor === 0) {
            $cursor = PHP_INT_MAX;
        }
        $endDatetime = clone $datetime;
        $endDatetime->modify('+1 hour');

        $query = $this->entityManager->createQuery('
            SELECT up.postId
            FROM LycheeCoreBundle:UserPost up
            JOIN LycheeCoreBundle:Post p WITH p.id=up.postId
            WHERE up.userId IN (:userIds)
            AND up.postId < :cursor
            AND p.createTime >= :start
            AND p.createTime < :end
            ORDER BY up.postId DESC
        ')->setMaxResults($count);
        $query->setParameters(array(
            'userIds' => $authorIds,
            'cursor' => $cursor,
            'start' => $datetime->format('Y-m-d H:i:s'),
            'end' => $endDatetime->format('Y-m-d H:i:s'),
        ));
        $result = $query->getArrayResult();
        $postIds = ArrayUtility::columns($result, 'postId');

        if (count($postIds) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $postIds[count($postIds) - 1];
        }

        return $postIds;
    }

	/**
	 * @param $userId
	 *
	 * @return int
	 */
    public function getUserLatestPostId($userId) {
    	$query = $this->entityManager->getRepository(UserPost::class)
		    ->createQueryBuilder('p')
		    ->where('p.userId=:userId')
		    ->setParameter('userId', $userId)
		    ->orderBy('p.postId', 'DESC')
		    ->setMaxResults(1)
		    ->getQuery();
	    $result = $query->getOneOrNullResult();
	    if (!$result) {
	    	return 0;
	    } else{
	    	return $result->postId;
	    }
    }

    /**
     * @param Post      $post
     * @param string    $svId
     * @param int       $bgmId
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return int
     */
    private function addShortVideoPost($post, $svId, $bgmId){

        $conn = $this->entityManager->getConnection();
        $sql = 'INSERT INTO ugsv_post (post_id, sv_id, bgm_id, author_id) VALUES(?, ?, ?, ?)';
        $affectedRows = $conn->executeUpdate($sql, array($post->id, $svId, $bgmId, $post->authorId),
            array(\PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT));

        return $affectedRows;
    }


    /**
     *
     * @param $authorId
     * @param $cursor
     * @param $count
     * @param null $nextCursor
     * @param null $client
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function fetchShortVideoIdsByAuthorIdForClient($authorId, $cursor, $count, &$nextCursor = null, $client=null) {

        if (strtolower($client)=='ios') {
            return $this->fetchPassShortVideoIdsByAuthorId($authorId, $cursor, $count, $nextCursor);
        }

        return $this->fetchShortVideoIdsByAuthorId($authorId, $cursor, $count, $nextCursor);
    }


    /**
     * 按发布时间降序排列
     * @param int      作者
     * @param int      帖子id，作为翻页的游标
     * @param int      返回的记录数
     * @param int      用于查询下一页的帖子id
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array
     */
    public function fetchShortVideoIdsByAuthorId($authorId, $cursor, $limit, &$nextCursor = null){
        if ($cursor <= 0) {
            $cursor = PHP_INT_MAX;
        }
        $sql = 'SELECT p.id FROM ugsv_post u
                INNER JOIN post p ON u.post_id = p.id
                WHERE p.deleted=0 
                and u.author_id=:authorId and p.id < :cursor ORDER BY p.id DESC limit '.($limit+1);

        $conn = $this->entityManager->getConnection();
        $sth = $conn->prepare($sql);
        $sth->execute([':authorId'=>$authorId, ':cursor'=>$cursor]);
        $res = $sth->fetchAll(\PDO::FETCH_ASSOC);
        $postIds = ArrayUtility::columns($res, 'id');

        $nextCursor = 0;
        if (isset($postIds[$limit])) {
            unset($postIds[$limit]);
            $nextCursor = end($postIds);
        }

        return $postIds;
    }

    /**
     * 按发布时间降序排列，只展示通过审核的帖子
     * @param int      作者
     * @param int      帖子id，作为翻页的游标
     * @param int      返回的记录数
     * @param int      用于查询下一页的帖子id
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array
     */
    public function fetchPassShortVideoIdsByAuthorId($authorId, $cursor, $limit, &$nextCursor = null){
        if ($cursor <= 0) {
            $cursor = PHP_INT_MAX;
        }
        $sql = 'SELECT p.id FROM ugsv_post u
                INNER JOIN post p ON u.post_id = p.id
                LEFT JOIN post_audit pa ON pa.post_id = p.id
                WHERE p.deleted=0
                AND (pa.status IS NULL OR pa.status=1)
                and u.author_id=:authorId and p.id < :cursor ORDER BY p.id DESC limit '.($limit+1);

        $conn = $this->entityManager->getConnection();
        $sth = $conn->prepare($sql);
        $sth->execute([':authorId'=>$authorId, ':cursor'=>$cursor]);
        $res = $sth->fetchAll(\PDO::FETCH_ASSOC);
        $postIds = ArrayUtility::columns($res, 'id');

        $nextCursor = 0;
        if (isset($postIds[$limit])) {
            unset($postIds[$limit]);
            $nextCursor = end($postIds);
        }

        return $postIds;
    }


    /**
     * 获取用户最后发帖时间
     * @param array      用户id数组
     *
     * @return array
     */
    public function fetchLastPubTime($userIds) {
        if (empty($userIds)) {
            return [];
        }
        $sql = 'SELECT max(p.create_time) last_create_time, up.user_id FROM post p 
        INNER JOIN user_post up ON p.id=up.post_id
        WHERE up.user_id IN (';
        $sql .= implode(',', array_fill(0, count($userIds), '?'));
        $sql .= ') group by up.user_id';
        $stat = $this->entityManager->getConnection()->prepare($sql);
        $stat->execute($userIds);
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($rows)) {
            return [];
        }
        $return = [];
        foreach ($rows as $item) {
            $return[$item['user_id']]=$item['last_create_time'];
        }

        return $return;
    }


    /**
     * 统计短视频发帖次数
     * @param array      用户id数组
     *
     * @return array
     */
    public function fetchShortVideoCountings($userIds) {
        if (empty($userIds)) {
            return [];
        }
        $sql = 'SELECT count(up.post_id) n, up.author_id 
        FROM ugsv_post up INNER JOIN post p ON p.id=up.post_id
        WHERE p.deleted=0 AND up.author_id IN (';
        $sql .= implode(',', array_fill(0, count($userIds), '?'));
        $sql .= ') group by up.author_id';
        $stat = $this->entityManager->getConnection()->prepare($sql);
        $stat->execute($userIds);
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($rows)) {
            return [];
        }
        $return = [];
        foreach ($rows as $item) {
            $return[$item['author_id']]=$item['n'];
        }
        return $return;
    }

    /**
     * 短视频帖子id列表，随机排序
     * @param    int    帖子id，作为翻页的游标
     * @param    int    返回的记录数
     * @param    int    用于查询下一页的帖子id
     *
     * @return array    帖子id数组
     */
    public function fetchShortVideoIdsRand($cursor, $limit, &$nextCursor = null) {
        $limit = intval($limit);
        if ($limit <= 0) {
            $nextCursor = $cursor;
            return array();
        }

        if ($cursor == 0) {
            $cursor = PHP_INT_MAX;
        }

        $sql = 'SELECT p.id FROM ugsv_post u
                INNER JOIN post p ON u.post_id = p.id
                WHERE p.deleted=0 and p.id < :cursor ORDER BY p.id DESC';

        $sql .= ' limit '.($limit+1);
        $conn = $this->entityManager->getConnection();
        $sth = $conn->prepare($sql);
        $sth->execute([':cursor'=>$cursor]);
        $res = $sth->fetchAll(\PDO::FETCH_ASSOC);
        $postIds = ArrayUtility::columns($res, 'id');

        $nextCursor = 0;
        if (isset($postIds[$limit])) {
            unset($postIds[$limit]);
            $nextCursor = end($postIds);
        }
        shuffle($postIds);
        return $postIds;
    }

    /**
     * 初始化审核状态
     * @param int      帖子id
     * @return bool    是否执行成功
     */
    public function initAuditStatus($postId) {

        $post = $this->fetchOne($postId);
        if (empty($post)) {
            return false;
        }

        if (in_array($post->authorId, [$this->accountService->getCiyuanjiangID()])) {
            return false;
        }

        if (!$this->isNeedAuditAtTopic($post->topicId)) {
            return false;
        }

        if ($this->isOpenAudit()) {
            return $this->initUntreatedAudit($postId);
        }
        return $this->initPassAudit($postId);
    }

    /**
     * 初始化未审核状态
     *
     * @param $postId
     * @return bool
     */
    public function initUntreatedAudit($postId)
    {
        return $this->insertAuditStatus($postId, \Lychee\Module\Post\Entity\PostAudit::UNTREATED_STATUS);
    }

    /**
     * 初始化审核通过状态
     *
     * @param $postId
     * @return bool
     */
    public function initPassAudit($postId)
    {
        $r =  $this->insertAuditStatus($postId, \Lychee\Module\Post\Entity\PostAudit::PASS_STATUS);

        $event = [];
        $event['postIds'] = [$postId];
        $this->dispatchEvent('post.pass_audit', $event);

        return $r;
    }

    public function insertAuditStatus($postId, $status)
    {
        $sql = "insert ignore into post_audit (post_id, update_time, status) values (?, ?, ?)";
        $conn = $this->entityManager->getConnection();
        $updated = $conn->executeUpdate($sql, [$postId, date('Y-m-d H:i:s'), $status]);
        if ($updated < 1) {
            return false;
        }
        return true;
    }

    /**
     * 判断在该次元发的帖子是否需要审核
     * @return bool 是否需要审核
     */
    public function isNeedAuditAtTopic($topicId) {
        $r = $this->entityManager->find(\Lychee\Module\Recommendation\Entity\RecommendableTopic::class, $topicId);
        if (empty($r)) {
            return false;
        }
        return true;
    }


    /**
     * 根据帖子审核状态查询，按帖子id降序迭代实体数据
     *
     * @param int       审核状态
     * @param array    array('发帖开始时间', '发帖结束时间')
     * @param int       删除状态，0：没删除的，1：已删除的，2：不限
     * @param int       审核来源
     *
     * @return QueryCursorableIterator
     */
    public function iterateForAuditPager($status, $createDate, $deleted=0, $source=0)
    {
        $em = $this->entityManager;

        $iterator = new \Lychee\Component\Foundation\CursorableIterator\CustomizedCursorableIterator(
            function($cursor, $step, &$nextCursor)use($em, $status, $createDate, $deleted, $source){
                $minId = 0;
                $maxId = $cursor;
                if ($createDate) {
                    foreach ($createDate as $key => $item) {
                        $createDate[$key] = date('Y-m-d H:i:s', strtotime($item));
                    }
                    $sql = "SELECT MAX(id) max_id,MIN(id) min_id FROM post WHERE create_time > ? AND create_time < ?";
                    $statement = $em->getConnection()->executeQuery($sql, $createDate);
                    $result = $statement->fetch(\PDO::FETCH_ASSOC);
                    $maxId = intval($result['max_id'])+1;
                    $minId = intval($result['min_id'])-1;
                }
                if ($cursor<=$maxId) {
                    $minId = min($minId, $cursor);
                    $maxId = min($maxId, $cursor);
                }

                $dsql = 'SELECT p.id, p.imageUrl, p.videoUrl, p.audioUrl, p.siteUrl, 
                p.createTime, p.authorId, p.topicId, p.annotation, p.content, pa.source
                FROM \Lychee\Module\Post\Entity\PostAudit pa
                INNER JOIN LycheeCoreBundle:Post p WITH p.id=pa.postId ';
                if (in_array($deleted, [0, 1])) {
                    $dsql .= " AND p.deleted=".intval($deleted);
                }

                if ($source>0) {
                    $dsql .= " AND pa.source=".intval($source);
                }

                $dsql .= "WHERE p.id > :min and p.id < :max AND pa.status = :status ORDER BY p.id DESC";

                $queryParams = [];
                $queryParams['max'] = $maxId;
                $queryParams['min'] = $minId;
                $queryParams['status'] = $status;

                $query = $em->createQuery($dsql)
                    ->setMaxResults($step+1)
                    ->setParameters($queryParams);
                $res = $query->getResult();
                $nextCursor = 0;
                if (isset($res[$step])) {
                    unset($res[$step]);
                    $last = end($res);
                    $nextCursor = $last['id'];
                }

                foreach ($res as $key => $item) {
                    $item = $this->formatPostFields($item);
                    $res[$key] = $item;
                }


                return $res;
        });

        return $iterator;
    }

    /**
     * 提供后台短视频列表分页，按帖子id降序迭代实体数据
     * @param string 关键字
     * @return QueryCursorableIterator
     */
    public function iterateForShortVideoPager($keyword=null)
    {
        $em = $this->entityManager;
        $iterator = new \Lychee\Component\Foundation\CursorableIterator\CustomizedCursorableIterator(function($cursor, $step, &$nextCursor)use($em, $keyword){

            $sqlparams=[':cursor'=>$cursor];

            $sql = "SELECT 
                up.post_id as postId 
                FROM ugsv_post up
                WHERE up.post_id<:cursor ";

            $sql .= "ORDER BY up.post_id DESC LIMIT ".($step+1);
            $conn = $em->getConnection();
            $sth = $conn->prepare($sql);
            $sth->execute($sqlparams);
            $res = $sth->fetchAll(\PDO::FETCH_ASSOC);
            $nextCursor = 0;
            if (isset($res[$step])) {
                unset($res[$step]);
                $last = end($res);
                $nextCursor = $last['postId'];
            }
            return $res;
        });

        return $iterator;
    }

    /**
     * 变更审核状态
     * @param int      帖子id
     * @return bool    是否执行成功
     */
    public function changeAuditStatus($postIds, $status, $source=1) {
        if (empty($postIds)) {
            return false;
        }
        $sql = "update post_audit set status = ?, update_time=?, source=?
        where post_id in (".implode(',', array_fill(0, count($postIds), '?')).")";
        $conn = $this->entityManager->getConnection();

        $sqlParams = [];
        $sqlParams[] = $status;
        $sqlParams[] = date('Y-m-d H:i:s');
        $sqlParams[] = $source;
        $sqlParams = array_merge($sqlParams, $postIds);
        $updated = $conn->executeUpdate($sql, $sqlParams);
        if ($updated < 1) {
            return false;
        }
        return true;
    }

    /**
     * 审核不通过并删除帖子
     * @param   array    帖子id数组
     * @return  bool     是否执行成功
     */
    public function rejectAuditAndDelete($postIds, $source=1) {
        $this->rejectAudit($postIds, $source);
        foreach ($postIds as $postId) {
            $this->delete($postId);
        }
        return true;
    }

    /**
     * 审核不通过
     * @param   array    帖子id数组
     * @return  bool     是否执行成功
     */
    public function rejectAudit($postIds, $source=1) {
        $this->changeAuditStatus($postIds, \Lychee\Module\Post\Entity\PostAudit::NOPASS_STATUS, $source);

        $event = [];
        $event['postIds'] = $postIds;
        $this->dispatchEvent('post.reject_audit', $event);

        return true;
    }

    /**
     * 审核通过
     * @param   array    帖子id数组
     * @return  bool     是否执行成功
     */
    public function passAudit($postIds) {
        $this->changeAuditStatus($postIds, \Lychee\Module\Post\Entity\PostAudit::PASS_STATUS);

        $event = [];
        $event['postIds'] = $postIds;
        $this->dispatchEvent('post.pass_audit', $event);

        return true;
    }

    /**
     * 发帖后是否需要审核
     * @return  bool     是否执行成功
     */
    public function isOpenAudit() {
        $result = $this->getAuditStrategyConfig();
        if (!$result) {
            return false;
        }
        if (\Lychee\Module\Post\Entity\PostAuditConfig::OPEN_STRATEGY_VALUE!=$result) {
            return false;
        }
        return true;
    }

    /**
     * 查询审核配置
     * @param   int   配置id
     * @return  \Lychee\Module\Post\Entity\PostAuditConfig
     */
    public function fetchAuditConfig($configId) {
        $result  = $this->entityManager->getRepository(\Lychee\Module\Post\Entity\PostAuditConfig::class)->find($configId);
        return $result;
    }

    /**
     * 查询审核策略配置
     * @return  \Lychee\Module\Post\Entity\PostAuditConfig
     */
    public function getAuditStrategyConfig() {
        $r = $this->fetchAuditConfig(\Lychee\Module\Post\Entity\PostAuditConfig::STRATEGY_ID);
        if ($r) {
            return $r->value;
        }
        return 1;
    }

    /**
     * 更新审核配置
     * @param int       配置id
     * @param string    配置值
     * @return bool     是否执行成功
     */
    public function updateAuditConfig($configId, $value) {
        if (!in_array($value, [
                \Lychee\Module\Post\Entity\PostAuditConfig::OPEN_STRATEGY_VALUE,
                \Lychee\Module\Post\Entity\PostAuditConfig::CLOSE_STRATEGY_VALUE
            ])) {
            return false;
        }
        $sql = "UPDATE post_audit_config SET value=?, update_time=? WHERE id=?";
        $conn = $this->entityManager->getConnection();
        $sqlParams = [];
        $sqlParams[] = $value;
        $sqlParams[] = date('Y-m-d H:i:s');
        $sqlParams[] = $configId;
        $updated = $conn->executeUpdate($sql, $sqlParams);
        if ($updated < 1) {
            return false;
        }
        return true;
    }

    /**
     * 初始化审核配置
     */
    public function initAuditConfig() {
        $configs = [];
        $configs[] = [\Lychee\Module\Post\Entity\PostAuditConfig::STRATEGY_ID, '帖子审核策略', \Lychee\Module\Post\Entity\PostAuditConfig::OPEN_STRATEGY_VALUE];
        $conn = $this->entityManager->getConnection();
        $sqlParams = [];
        $inserts = [];
        foreach ($configs as $config) {
            $inserts[] = '(?, ?, ?, ?)';
            list($id, $title, $value) = $config;
            $sqlParams[] = $id;
            $sqlParams[] = $title;
            $sqlParams[] = $value;
            $sqlParams[] = date('Y-m-d H:i:s');
        }
        $sql = "INSERT IGNORE INTO post_audit_config (id, title, value, update_time) VALUES ".implode(',', $inserts);
        $updated = $conn->executeUpdate($sql, $sqlParams);
    }

    /**
     * 根据视频文件id查询帖子id
     *
     * @param int   $svid   视频文件id
     * @return int
     */
    public function fetchIdBySVId($svid) {
        $result  = $this->entityManager->getRepository(\Lychee\Module\UGSV\Entity\Post::class)->findOneBy(['svId'=>$svid]);
        if (empty($result)) {
            return 0;
        }
        return $result->postId;
    }

    /**
     * 删除涉黄短视频
     *
     * @param $postId
     * @throws PostNotFoundException
     */
    public function deletePronShortVideo($postId) {
        $this->rejectAuditAndDelete([$postId], Entity\PostAudit::TXVOD_AI_REVIEW_PORN_SOURCE);
    }

    /**
     * 获取审核来源列表
     *
     * @return array
     */
    public  function getAuditSources() {
        $mapping = [];
        $mapping[Entity\PostAudit::ADMIN_SOURCE] = '后台审核';
        $mapping[Entity\PostAudit::TXVOD_AI_REVIEW_PORN_SOURCE] = '短视频智能鉴黄';
        return $mapping;
    }


    /**
     * 获取热门短视频id
     *
     * @param $cursor
     * @param $limit
     * @param null $nextCursor
     * @return mixed
     */
    public function getHotShortVideoIds($cursor, $limit, &$nextCursor = null)  {
        if ($cursor<0) {
            return [];
        }
        $redis = $this->serviceContainer->get('snc_redis.recommendation_video');

        $endIndex = $cursor+$limit-1;
        $postIds = $redis->lrange('hot_videos', $cursor, $endIndex+1);
        $nextCursor = 0;
        if (isset($postIds[$limit])) {
            $nextCursor = $endIndex+1;
            unset($postIds[$limit]);
        }
        return $postIds;
    }

    /**
     * 获取最近短视频id
     *
     * @param $cursor
     * @param $limit
     * @param null $nextCursor
     * @return mixed
     */
    public function getNewLyShortVideoIds($cursor, $limit, &$nextCursor = null)  {
        if ($cursor<0) {
            return [];
        }
        $redis = $this->serviceContainer->get('snc_redis.recommendation_video');

        $endIndex = $cursor+$limit-1;
        $postIds = $redis->lrange('newly_videos', $cursor, $endIndex+1);
        $nextCursor = 0;
        if (isset($postIds[$limit])) {
            $nextCursor = $endIndex+1;
            unset($postIds[$limit]);
        }
        return $postIds;
    }

    /**
     * 按时间降序，随机获取top n条，在nx10的范围内随机
     * @param $limit
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getTopNewLyShortVideoIdsRand($limit) {
        $limit = intval($limit);
        if ($limit <= 0
        ||$limit>=PHP_INT_MAX) {
            return array();
        }
        $maxLimit = 500;
        $qLimit = $limit*10;
        if ($qLimit>$maxLimit) {
            $qLimit = $maxLimit;
        }
        $sql = 'SELECT p.id FROM ugsv_post u
                INNER JOIN post p ON u.post_id = p.id
                WHERE p.deleted=0 ORDER BY p.id DESC';

        $sql .= ' limit '.$qLimit;
        $conn = $this->entityManager->getConnection();
        $sth = $conn->executeQuery($sql);
        $res = $sth->fetchAll(\PDO::FETCH_ASSOC);
        $postIds = ArrayUtility::columns($res, 'id');
        shuffle($postIds);
        $postIds = array_slice($postIds, 0, $limit);
        return $postIds;
    }

    /**
     * 按点赞数降序，随机获取top n条，在nx10的范围内随机
     * @param $limit
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getTopHotShortVideoIdsRand($limit) {
        $limit = intval($limit);
        if ($limit <= 0
            ||$limit>=PHP_INT_MAX) {
            return array();
        }
        $maxLimit = 500;
        $qLimit = $limit*10;
        if ($qLimit>$maxLimit) {
            $qLimit = $maxLimit;
        }
        $sql = 'SELECT p.id FROM ugsv_post u
                INNER JOIN post p ON u.post_id = p.id
                INNER JOIN post_counting pc ON u.post_id = pc.post_id
                WHERE p.deleted=0 and pc.liked_count>2 ORDER BY pc.liked_count DESC';

        $sql .= ' limit '.$qLimit;
        $conn = $this->entityManager->getConnection();
        $sth = $conn->executeQuery($sql);
        $res = $sth->fetchAll(\PDO::FETCH_ASSOC);
        $postIds = ArrayUtility::columns($res, 'id');
        shuffle($postIds);
        $postIds = array_slice($postIds, 0, $limit);
        return $postIds;
    }


    public function fetchPlainIdsByAuthorId($authorId, $cursor, $count, &$nextCursor = null) {
        return $this->fetchPlainIdsByAuthorIds(array($authorId), $cursor, $count, $nextCursor);
    }

    public function fetchPlainIdsByAuthorIds($authorIds, $cursor, $count, &$nextCursor = null) {
        if ($count === 0 || count($authorIds) == 0) {
            $nextCursor = $cursor;
            return array();
        }
        if ($cursor === 0) {
            $cursor = PHP_INT_MAX;
        }

        $query = $this->entityManager->createQuery('
            SELECT up.postId
            FROM LycheeCoreBundle:UserPost up
            LEFT JOIN LycheeCoreBundle:Post p WITH p.id=up.postId 
            WHERE up.userId IN (:userIds)
            AND p.type != '.Post::TYPE_SHORT_VIDEO .' AND up.postId < :cursor
            ORDER BY up.postId DESC
        ')->setMaxResults($count);
        $query->setParameters(array('userIds' => $authorIds, 'cursor' => $cursor));
        $result = $query->getArrayResult();
        $postIds = ArrayUtility::columns($result, 'postId');

        if (count($postIds) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $postIds[count($postIds) - 1];
        }

        return $postIds;
    }



    public function fetchPlainIdsByAuthorIdInPublicTopicForClient($authorId, $cursor, $count, &$nextCursor = null, $client=null) {

        if (strtolower($client)=='ios') {
            return $this->fetchPassPlainIdsByAuthorIdInPublicTopic($authorId, $cursor, $count, $nextCursor);
        }

        return $this->fetchPlainIdsByAuthorIdInPublicTopic($authorId, $cursor, $count, $nextCursor);
    }
    public function fetchPassPlainIdsByAuthorIdInPublicTopic($authorId, $cursor, $count, &$nextCursor = null) {
        if ($count == 0) {
            $nextCursor = $cursor;
            return array();
        }
        if ($cursor == 0) {
            $cursor = PHP_INT_MAX;
        }

        $sql = 'SELECT u.post_id FROM user_post u 
            LEFT JOIN topic t ON u.topic_id = t.id
            LEFT JOIN post p ON p.id = u.post_id
            LEFT JOIN post_audit pa ON pa.post_id = p.id 
            WHERE u.user_id = ? AND u.post_id < ? 
            AND t.private = 0 AND p.type!= '.Post::TYPE_SHORT_VIDEO .' 
            AND (pa.status IS NULL OR pa.status=1) ORDER BY u.post_id DESC LIMIT ?';
        $stat = $this->entityManager->getConnection()->executeQuery($sql, array($authorId, $cursor, $count),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        $postIds = ArrayUtility::columns($rows, 'post_id');

        if (count($postIds) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $postIds[count($postIds) - 1];
        }

        return $postIds;
    }

    public function fetchPlainIdsByAuthorIdInPublicTopic($authorId, $cursor, $count, &$nextCursor = null) {
        if ($count == 0) {
            $nextCursor = $cursor;
            return array();
        }
        if ($cursor == 0) {
            $cursor = PHP_INT_MAX;
        }

        $sql = 'SELECT u.post_id FROM user_post u 
            LEFT JOIN topic t ON u.topic_id = t.id
            LEFT JOIN post p ON p.id = u.post_id'
            .' WHERE u.user_id = ? AND u.post_id < ? AND t.private = 0 AND p.type!= '.Post::TYPE_SHORT_VIDEO .' ORDER BY u.post_id DESC LIMIT ?';
        $stat = $this->entityManager->getConnection()->executeQuery($sql, array($authorId, $cursor, $count),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        $postIds = ArrayUtility::columns($rows, 'post_id');

        if (count($postIds) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $postIds[count($postIds) - 1];
        }

        return $postIds;
    }

    public function fetchIdsByTopicIdOrderByHotForClient($topicId, $cursor, $count, &$nextCursor = null, $client=null) {

        if (strtolower($client)=='ios') {
            return $this->fetchPassIdsByTopicIdOrderByHot($topicId, $cursor, $count, $nextCursor);
        }

        return $this->fetchIdsByTopicIdOrderByHot($topicId, $cursor, $count, $nextCursor);
    }

    public function fetchPassIdsByTopicIdOrderByHot($topicId, $cursor, $count, &$nextCursor = null) {
        return $this->fetchPassIdsByTopicIdsOrderByHot(array($topicId), $cursor, $count, $nextCursor);
    }

    /**
     * 根据次元id，获取审核通过的帖子id
     * @param array  次元id数组
     * @param int    结果必须>该帖子id
     * @param int    限制查询的记录数
     * @param int    最后一条帖子id
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function fetchPassIdsByTopicIdsOrderByHot($topicIds, $cursor, $count, &$nextCursor = null) {
        if ($count === 0) {
            $nextCursor = $cursor;
            return array();
        }

        $query = $this->entityManager->createQuery('
            SELECT tp.postId
            FROM LycheeCoreBundle:TopicPost tp
            LEFT JOIN \Lychee\Module\Post\Entity\PostAudit pa WITH tp.postId=pa.postId
            LEFT JOIN \Lychee\Module\Like\Entity\PostPeriodLikeCount pplc WITH pplc.postId = tp.postId
            LEFT JOIN \Lychee\Bundle\CoreBundle\Entity\PostCounting pc WITH pc.postId = tp.postId
            WHERE tp.topicId IN (:topicIds)
            AND ( pa.status IS NULL OR pa.status = :auditStatus )
            ORDER BY pplc.count DESC, pc.likedCount DESC, tp.postId DESC
        ')->setFirstResult($cursor)->setMaxResults($count+1);
        $result = $query->execute(array(
            'topicIds' => $topicIds,
            'auditStatus'=>\Lychee\Module\Post\Entity\PostAudit::PASS_STATUS));
        $nextCursor = 0;
        if (isset($result[$count])) {
            $nextCursor = $cursor+$count;
            unset($result[$count]);
        }
        $postIds = ArrayUtility::columns($result, 'postId');
        return $postIds;
    }


    public function fetchIdsByTopicIdOrderByHot($topicId, $cursor, $count, &$nextCursor = null) {
        return $this->fetchIdsByTopicIdsOrderByHot(array($topicId), $cursor, $count, $nextCursor);
    }

    /**
     *
     * 根据热度排序，1周点赞数 desc, 总点赞数 desc, 发帖时间 desc
     * @param $topicIds
     * @param $cursor
     * @param $count
     * @param null $nextCursor
     * @return array
     */
    public function fetchIdsByTopicIdsOrderByHot($topicIds, $cursor, $count, &$nextCursor = null) {
        if ($count === 0) {
            $nextCursor = $cursor;
            return array();
        }

        $query = $this->entityManager->createQuery('
            SELECT tp.postId
            FROM LycheeCoreBundle:TopicPost tp
            LEFT JOIN \Lychee\Module\Like\Entity\PostPeriodLikeCount pplc WITH pplc.postId = tp.postId
            LEFT JOIN \Lychee\Bundle\CoreBundle\Entity\PostCounting pc WITH pc.postId = tp.postId
            WHERE tp.topicId IN (:topicIds)
            ORDER BY pplc.count DESC, pc.likedCount DESC, tp.postId DESC
        ')->setFirstResult($cursor)->setMaxResults($count+1);
        $result = $query->execute(array('topicIds' => $topicIds));
        $nextCursor = 0;
        if (isset($result[$count])) {
            $nextCursor = $cursor+$count;
            unset($result[$count]);
        }
        $postIds = ArrayUtility::columns($result, 'postId');
        return $postIds;
    }

    /**
     * 获取推荐短视频id
     *
     * @param $cursor
     * @param $limit
     * @param null $nextCursor
     * @return mixed
     */
    public function getRecShortVideoIds($cursor, $limit, &$nextCursor = null)  {
        if ($cursor<0) {
            return [];
        }
        $redis = $this->serviceContainer->get('snc_redis.recommendation_video');

        $endIndex = $cursor+$limit-1;
        $postIds = $redis->lrange('rec_videos', $cursor, $endIndex+1);
        $nextCursor = 0;
        if (isset($postIds[$limit])) {
            $nextCursor = $endIndex+1;
            unset($postIds[$limit]);
        }
        return $postIds;
    }

    /**
     * 迁移短视频封面图到七牛云
     * @param $postId
     * @return bool
     */
    public function moveShortVideoCoverStoreById($postId) {
        $post = $this->fetchOne($postId);
        if ($post->type!=POST::TYPE_SHORT_VIDEO) {
            return false;
        }
        $annotation = json_decode($post->annotation, true);
        if (empty($annotation['video_cover'])) {
            return false;
        }
        if (strpos($annotation['video_cover'], 'qn.ciyo.cn')
        ||strpos($annotation['video_cover'], 'qn.ciyocon.com')) {
            return false;
        }
        $oldCoverUrl = $annotation['video_cover'];
        $oldCoverInfo = get_headers($oldCoverUrl, 1);
        if (in_array('404', explode(' ', $oldCoverInfo[0]))) {
            return false;
        }

        $storage = $this->serviceContainer->get('lychee.component.storage');
        $key = 'ugsvcover/'.md5($oldCoverUrl);

        $tmpPath =  tempnam(sys_get_temp_dir(), 'ciyo_ugsv');
        copy($annotation['video_cover'], $tmpPath);
        $cover = $storage->putWithAutoRenewToken($tmpPath, $key);
        unlink($tmpPath);
        if (empty($cover)) {
            return false;
        }
        $annotation['video_cover'] = $cover;
        $post->imageUrl = $cover;
        $post->annotation = json_encode($annotation);
        $this->update($post);
        return true;
    }

    /**
     * 迁移短视频播放次数统计
     *
     * @param $startTime
     * @param $endTime
     */
    public function movePlayStatStore($startTime, $endTime)
    {
        $txVodApi = $this->serviceContainer->get('lychee.component.video');
        $startDate = date('Y-m-d', $startTime);
        $endDate = date('Y-m-d', $endTime);
        $r = $txVodApi->getPlayStatLogList($startDate, $endDate);

        $storage = $this->serviceContainer->get('lychee.component.storage');
        $list = [];
        foreach ($r['fileList'] as $item) {

            usleep(100000);

            $oldUrl = $item['url'];
            $key = 'ugsvplaystat/'.date('Ymd', strtotime($item['date'])).'.csv.gz';
            $list[$item['date']] = $key;

            try {
                $storage->stat($key);
                continue;
            } catch (\Lychee\Component\Storage\StorageException $e) {}

            $tmpPath =  tempnam(sys_get_temp_dir(), 'ciyo_ugsv_playstat');
            copy($oldUrl, $tmpPath);
            $res = $storage->putWithAutoRenewToken($tmpPath, $key);
            unlink($tmpPath);
            $list[$item['date']] = $res;
        }
        return $list;
    }

    /**
     * 发帖后事件，消费消息队列异步执行
     *
     * @param $eventBody
     */
    public function asyncAfterCreate($eventBody) {
        $postId = $eventBody['postId'];

        try {
            $this->serviceContainer->get('lychee.module.relation.robot')->dispatchFollowUserTaskWhenPostEventHappen($postId);
        } catch (\Exception $e) {}

        return true;
    }

    /**
     * 删帖后事件，消费消息队列异步执行
     *
     * @param $eventBody
     */
    public function asyncAfterDelete($eventBody) {
        $postId = $eventBody['postId'];
        $post = $this->fetchOne($postId);

        try {
            $this->serviceContainer->get('lychee.module.search.superiorPostIndexer')->remove($post);
        } catch (ResponseException $e){}

        return true;
    }


    /**
     * 短视频播放结束后事件，消费消息队列异步执行
     *
     * @param $eventBody
     */
    public function asyncAfterStopPlayShortVideo($eventBody) {
        return true;
    }

    /**
     * 审核通过后事件，消费消息队列异步执行
     *
     * @param $eventBody
     */
    public function asyncAfterPassAudit($eventBody) {
        $postIds = $eventBody['postIds'];
        $posts = $this->fetch($postIds);
        foreach ($posts as $post) {
            if (empty($post)) {
                continue;
            }
            try {
                $this->getSuperiorPostIndexer()->add($post);
            } catch (ResponseException $e){}
        }

        return true;
    }

    /**
     * 被拒绝通过后事件，消费消息队列异步执行
     *
     * @param $eventBody
     */
    public function asyncAfterRejectAudit($eventBody) {
        $postIds = $eventBody['postIds'];
        $posts = $this->fetch($postIds);
        foreach ($posts as $post) {
            try {
                $this->getSuperiorPostIndexer()->remove($post);
            } catch (ResponseException $e){}
        }
        return true;
    }

    /**
     * @return PostIndexer
     */
    private function getSuperiorPostIndexer()
    {
        return $this->serviceContainer->get('lychee.module.search.superiorPostIndexer');
    }

    /**
     * @param $posts
     * @return ContentResolver
     */
    public function buildContentResolver($posts)
    {
        return null;
        if (empty($posts)) {
            return new ContentResolver([]);
        }
        $contentMap = [];

        $urlPlacer = $this->serviceContainer->get('lychee.component.url_replacer');
        $topic = $this->serviceContainer->get('lychee.module.topic');

        foreach ($posts as $post) {
            if (!$topic->isReplaceUrl($post->topicId)) {
                continue;
            }
            $contentMap[$post->id] = $urlPlacer->all($post->content);
        }
        return new ContentResolver($contentMap);
    }

}