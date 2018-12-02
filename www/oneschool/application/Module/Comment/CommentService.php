<?php
namespace Lychee\Module\Comment;

use Lychee\Bundle\CoreBundle\Entity\Comment;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Foundation\CursorableIterator\QueryCursorableIterator;
use Lychee\Component\Foundation\IteratorTrait;
use Lychee\Component\KVStorage\MemcacheStorage;
use Lychee\Module\Comment\Exception\CommentNotFoundException;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Lsw\MemcacheBundle\Cache\MemcacheInterface;
use Lychee\Component\KVStorage\CachedDoctrineStorage;
use Lychee\Component\IdGenerator\IdGenerator;
use Lychee\Module\Post\PostService;
use Lychee\Module\Account\AccountService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Lychee\Component\Foundation\CursorableIterator\CustomizedCursorableIterator;

class CommentService {

    use IteratorTrait;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var CachedDoctrineStorage
     */
    private $storage;

    /**
     * @var MemcacheStorage
     */
    private $cacheStorage;

    /**
     * @var IdGenerator
     */
    private $idGenerator;

    /**
     * @var PostService
     */
    private $postService;

    /**
     * @var AccountService
     */
    private $accountService;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ContainerInterface
     */
    private $serviceContainer;

    /**
     * @param ManagerRegistry $registry
     * @param MemcacheInterface $memcache
     * @param IdGenerator $idGenerator
     * @param PostService $postService
     * @param AccountService $accountService
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        $registry,
        $memcache,
        $idGenerator,
        $postService,
        $accountService,
        $eventDispatcher,
        $serviceContainer
    ) {
        $this->entityManager = $registry->getManager($registry->getDefaultManagerName());

        $this->cacheStorage = new MemcacheStorage($memcache, 'comment:', 3600);
        $this->storage = new CachedDoctrineStorage($this->entityManager, 'LycheeCoreBundle:Comment', $this->cacheStorage);
        $this->idGenerator = $idGenerator;

        $this->postService = $postService;
        $this->accountService = $accountService;

        $this->eventDispatcher = $eventDispatcher;
        $this->serviceContainer = $serviceContainer;
    }



    public function increaseLikedCounter($commentId, $delta) {
        $comment = $this->storage->get($commentId);
        $query = $this->entityManager->createQuery('
            UPDATE LycheeCoreBundle:Comment c
            SET c.likedCount = c.likedCount + :delta
            WHERE c.id = :id
        ')->setParameters(array('id' => $commentId, 'delta' => $delta));
        $query->execute();
        $this->entityManager->detach($comment);
        $this->cacheStorage->delete($commentId);
    }



    /**
     * @param int $postId
     * @param int $authorId
     * @param string $ip
     * @param string $content
     * @param int $repliedId
     * @param string|null $imageUrl
     * @param string|null $district
     * @param string|null $annotation
     *
     * @return Comment
     * @throws \Exception
     */
    public function create($postId, $authorId, $content, $repliedId, $imageUrl, $ip, $district, $annotation) {
        $comment = new Comment();
        $comment->id = $this->idGenerator->generate();
        $comment->createTime = new \DateTime();
        $comment->postId = $postId;
        $comment->authorId = $authorId;
        $comment->repliedId = $repliedId;
        $comment->ip = $ip;
        $comment->content = $content;
        $comment->imageUrl = $imageUrl;
        $comment->district = $district;
        $comment->annotation = $annotation;

        try {
            $this->entityManager->beginTransaction();
            $this->storage->set($comment->id, $comment);
            $this->postAddComment($postId, $comment);
            $this->userAddComment($authorId, $comment);

            $this->entityManager->commit();

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        $this->eventDispatcher->dispatch(CommentEvent::CREATE, new CommentEvent($comment->id));


        if ($comment->id) {
            $event = [];
            $event['commentId'] = $comment->id;
            $event['postId'] = $postId;
            $event['userId'] = $comment->authorId;
            $this->dispatchEvent('comment.create', $event);
        }

        return $comment;
    }

    /**
     * @param int $commentId
     * @throws \Exception
     * @throws CommentNotFoundException
     */
    public function delete($commentId) {
        try {
            $comment = $this->fetchOne($commentId);
            if ($comment === null) {
                throw new CommentNotFoundException();
            }
            if ($comment->deleted === true) {
                return;
            }

            $this->entityManager->beginTransaction();
            $this->postRemoveComment($comment->postId, $comment);
            $this->userRemoveComment($comment->authorId, $comment);
            $comment->deleted = true;
            $this->storage->set($comment->id, $comment);

            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        $this->eventDispatcher->dispatch(CommentEvent::DELETE, new CommentEvent($commentId));
    }

    public function undelete($commentId) {
        try {
            $comment = $this->fetchOne($commentId);
            if ($comment === null) {
                throw new CommentNotFoundException();
            }
            if ($comment->deleted === false) {
                return;
            }

            $this->entityManager->beginTransaction();
            $this->userAddComment($comment->authorId, $comment);
            if ($comment->postId) {
                $this->postAddComment($comment->postId, $comment);
            }
            $comment->deleted = false;
            $this->storage->set($comment->id, $comment);

            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        $this->eventDispatcher->dispatch(CommentEvent::UNDELETE, new CommentEvent($commentId));
    }

    /**
     * @param array $ids
     * @return array
     */
    public function fetch($ids) {
        return $this->storage->getMulti($ids);
    }

    /**
     * @param int $id
     *
     * @return Comment|null
     */
    public function fetchOne($id) {
        return $this->storage->get($id);
    }

    /**
     * @param int $postId
     * @param Comment $comment
     */
    private function postAddComment($postId, $comment) {
        $hasImage = $comment->imageUrl ? 1 : 0;
        $sql = 'INSERT INTO post_comment(post_id, comment_id, has_image)
            VALUE (?, ?, ?) ON DUPLICATE KEY UPDATE has_image = ?';
        $affectedRows = $this->entityManager->getConnection()->executeUpdate($sql,
            array($postId, $comment->id, $hasImage, $hasImage));

        if ($affectedRows == 1) {
            $this->postService->increaseCommentedCounter($comment->postId, 1);
        }
    }

    /**
     * @param int $postId
     * @param Comment $comment
     */
    private function postRemoveComment($postId, $comment) {
        $sql = 'DELETE FROM post_comment WHERE post_id = ? AND comment_id = ?';
        $affectedRows = $this->entityManager->getConnection()->executeUpdate($sql, array($postId, $comment->id));
        if ($affectedRows == 1) {
            $this->postService->increaseCommentedCounter($comment->postId, -1);
        }
    }

    /**
     * @param int $userId
     * @param Comment $comment
     */
    private function userAddComment($userId, $comment) {
        $hasImage = $comment->imageUrl ? 1 : 0;
        $sql = 'INSERT INTO user_comment(user_id, comment_id, has_image)
            VALUE (?, ?, ?) ON DUPLICATE KEY UPDATE has_image = ?';
        $affectedRows = $this->entityManager->getConnection()->executeUpdate($sql,
            array($userId, $comment->id, $hasImage, $hasImage));

        if ($affectedRows == 1 && $comment->imageUrl !== null) {
            $this->accountService->increaseImageCommentCounter($userId, 1);
        }
    }

    /**
     * @param int $userId
     * @param Comment $comment
     */
    private function userRemoveComment($userId, $comment) {
        $sql = 'DELETE FROM user_comment WHERE user_id = ? AND comment_id = ?';
        $affectedRows = $this->entityManager->getConnection()->executeUpdate($sql, array($userId, $comment->id));

        if ($affectedRows == 1 && $comment->imageUrl !== null) {
            $this->accountService->increaseImageCommentCounter($userId, -1);
        }
    }

    /**
     * @param int $postId
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     *
     * @return array
     */
    public function fetchIdsByPostId($postId, $cursor, $count, &$nextCursor = null) {
        if ($count === 0) {
            return array();
        }

        $query = $this->entityManager->createQuery('
            SELECT pc.commentId
            FROM LycheeCoreBundle:PostComment pc
            WHERE pc.postId = :postId
            AND pc.commentId > :curosr
            ORDER BY pc.commentId ASC
        ')->setMaxResults($count);
        $result = $query->execute(array('postId' => $postId, 'curosr' => $cursor));
        $commentIds = ArrayUtility::columns($result, 'commentId');
        if (count($commentIds) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $commentIds[count($commentIds) - 1];
        }

        return $commentIds;
    }

    public function fetchLatestIdsByPostIds($postIds, $count) {
        if ($count === 0) {
            return array_fill_keys($postIds, array());
        }
        if (count($postIds) === 0) {
            return array();
        }

        $sql = '';
        foreach ($postIds as $postId) {
            if (strlen($sql) > 0) {
                $sql .= 'UNION';
            }
            $sql .= '(
                SELECT post_id, comment_id FROM post_comment
                WHERE post_id = '. intval($postId) .'
                ORDER BY comment_id DESC LIMIT '. intval($count) .'
            )';
        }
        $statement = $this->entityManager->getConnection()->executeQuery($sql);
        $queryResult = $statement->fetchAll(\PDO::FETCH_COLUMN | \PDO::FETCH_GROUP);
        $result = array();
        foreach ($postIds as $postId) {
            if (isset($queryResult[$postId])) {
                $result[$postId] = $queryResult[$postId];
            } else {
                $result[$postId] = array();
            }
        }
        return $result;
    }

    public function fetchIdsByAuthorId($authorId, $cursor, $count, &$nextCursor = null) {
        if ($count === 0) {
            return array();
        }
        if ($cursor == 0) {
            $cursor = PHP_INT_MAX;
        }

        $query = $this->entityManager->createQuery('
            SELECT pc.commentId
            FROM LycheeCoreBundle:UserComment pc
            WHERE pc.userId = :authorId
            AND pc.commentId < :curosr
            ORDER BY pc.commentId DESC
        ')->setMaxResults($count);
        $result = $query->execute(array('authorId' => $authorId, 'curosr' => $cursor));
        $commentIds = ArrayUtility::columns($result, 'commentId');
        if (count($commentIds) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $commentIds[count($commentIds) - 1];
        }

        return $commentIds;
    }

    /**
     * @param \DateTime $createTime
     * @return QueryCursorableIterator
     */
    public function iterateCommentWithImage(\DateTime $createTime = null)
    {
        $repo = $this->getRepository('Comment');
        $fieldName = 'id';
        $queryBuilder = $repo->createQueryBuilder('c')
            ->where("c.$fieldName > :cursor")
            ->andWhere('c.imageUrl is not null')
            ->andWhere("c.imageUrl != ''")
            ->orderBy("c.$fieldName");
        if (null !== $createTime) {
            $queryBuilder->andWhere('c.createTime >= :createTime')
                ->setParameter('createTime', $createTime);
        }

        return new QueryCursorableIterator($queryBuilder->getQuery(), $fieldName);
    }

    /**
     * @return QueryCursorableIterator
     */
    public function iterateCommentOnlyCharacter(\DateTime $createTime = null)
    {
        $repo = $this->getRepository('Comment');
        $fieldName = 'id';
        $queryBuilder = $repo->createQueryBuilder('c')
            ->where("c.$fieldName > :cursor AND (c.imageUrl is null OR c.imageUrl = '')")
            ->orderBy("c.$fieldName");
        if (null !== $createTime) {
            $queryBuilder->andWhere('c.createTime >= :createTime')
                ->setParameter('createTime', $createTime);
        }

        return new QueryCursorableIterator($queryBuilder->getQuery(), $fieldName);
    }

    /**
     * @param $entityName
     * @return \Doctrine\ORM\EntityRepository
     */
    private function getRepository($entityName)
    {
        return $this->entityManager->getRepository('LycheeCoreBundle:' . $entityName);
    }

    /**
     * @param \DateTime $createTime
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCursorAfterCreateTime(\DateTime $createTime)
    {
        $commentRepo = $this->entityManager->getRepository(Comment::class);
        $query = $commentRepo->createQueryBuilder('c')
            ->select('c.id')
            ->where('c.createTime < :date')
            ->setParameter('date', $createTime)
            ->orderBy('c.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery();
        $minResult = $query->getOneOrNullResult();

        return (null === $minResult)? 0 : $minResult['id'];
    }

    /**
     * @return QueryCursorableIterator
     */
    public function iterateComment()
    {
        return $this->iterateEntity($this->entityManager, Comment::class);
    }

    public function getCommentCountByPostId($postId) {
	    $query = $this->entityManager->createQuery('
            SELECT COUNT(pc.commentId) comment_count
            FROM LycheeCoreBundle:PostComment pc
            WHERE pc.postId = :postId
        ')->setParameter('postId', $postId);
	    $result = $query->getOneOrNullResult();
	    if (!$result) {
	    	return 0;
	    } else {
	    	return (int)$result['comment_count'];
	    }
    }

    /**
     * 获取热评
     *
     * @param $postId         帖子id
     * @param $minLikedCount  最少点赞数
     * @param $cursor
     * @param $count
     * @param null $nextCursor
     * @return array
     */

    public function fetchHotIdsByPostId($postId, $minLikedCount, $cursor, $count, &$nextCursor = null) {
        if ($count <= 0) {
            return array();
        }

        $query = $this->entityManager->createQuery('
            SELECT t.id
            FROM LycheeCoreBundle:Comment t
            WHERE t.postId = :postId
            AND t.deleted = :deleted
            AND t.likedCount >= :minLikedCount
            ORDER BY t.likedCount DESC
        ')->setMaxResults($count)
            ->setFirstResult($cursor);
        $result = $query->execute(
            array('postId' => $postId, 'deleted' => false, 'minLikedCount' => $minLikedCount)
        );
        $commentIds = ArrayUtility::columns($result, 'id');
        if (count($commentIds) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $cursor + $count;
        }

        return $commentIds;
    }


    /**
     * 帖子评论后事件，消费消息队列异步执行
     *
     * @param $eventBody
     */
    public function asyncAfterCreateComment($eventBody) {

        $triggers = [];
        $triggers[] = [$this->serviceContainer->get('lychee.module.comment.robot'), 'dispatchCommentTaskWhenCommentEventHappen'];

        foreach ($triggers as $trigger) {
            try {

                list($class, $method) = $trigger;
                $class->$method($eventBody['postId'], $eventBody['userId'], $eventBody['commentId']);

            } catch (\Exception $e) {
                $this->serviceContainer->get('logger')->error($e->__toString());
            }
        }
        return true;
    }

    private function dispatchEvent($eventName, $event)
    {
        $this->serviceContainer->get('lychee.dynamic_dispatcher_async')->dispatch($eventName, $event);
    }

    /**
     * @param $comments
     * @return ContentResolver
     */
    public function buildContentResolver($comments)
    {
        return null;
        if (empty($comments)) {
            return new ContentResolver([]);
        }
        $contentMap = [];
        $urlPlacer = $this->serviceContainer->get('lychee.component.url_replacer');
        $commentIds = [];
        foreach ($comments as $comment) {
            $commentIds[] = $comment->id;
        }
        try {
            $sql = 'select post_id,id from comment where id in ('.implode(',', $commentIds).')';
            $statement = $this->entityManager->getConnection()->executeQuery($sql);
            $queryResult = $statement->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return new ContentResolver([]);
        }
        if (empty($queryResult)) {
            return new ContentResolver([]);
        }

        $needReplaceComments = [];
        $postIds = [];
        foreach ($queryResult as $item) {
            $needReplaceComments[$item['id']] = $item['post_id'];
            $postIds[] = $item['post_id'];
        }

        $post = $this->serviceContainer->get('lychee.module.post');
        $postList = $post->fetch($postIds);
        $topic = $this->serviceContainer->get('lychee.module.topic');

        foreach ($needReplaceComments as $topicId => $postId) {
            if (empty($postList[$postId])) {
                continue;
            }
            if ($topic->isReplaceUrl($postList[$postId]->topicId)) {
                continue;
            }
            unset($needReplaceComments[$topicId]);
        }

        foreach ($comments as $comment) {
            if (empty($needReplaceComments[$comment->id])) {
                continue;
            }
            $contentMap[$comment->id] = $urlPlacer->all($comment->content);
        }
        return new ContentResolver($contentMap);
    }
} 
