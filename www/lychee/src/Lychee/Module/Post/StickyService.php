<?php
namespace Lychee\Module\Post;

use Doctrine\ORM\EntityManagerInterface;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Post\Entity\TopicStickyPost;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Lychee\Component\Foundation\CursorableIterator\CursorableIterator;
use Lychee\Component\Foundation\CursorableIterator\QueryCursorableIterator;

class StickyService {

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @param RegistryInterface $registry
     */
    public function __construct($registry) {
        $this->em = $registry->getManager();
    }

    /**
     * @param int $postId
     * @throws \Exception
     */
    public function stickPost($postId) {
        $conn = $this->em->getConnection();
        $stat = $conn->executeQuery('SELECT topic_id FROM post WHERE id = ?',
            array($postId), array(\PDO::PARAM_INT));
        $row = $stat->fetch(\PDO::FETCH_ASSOC);
        if ($row == null) {
            // post not exist
            return;
        }

        $topicId = $row['topic_id'];
        $dateFormat = $conn->getDatabasePlatform()->getDateTimeFormatString();
        $dateString = (new \DateTime())->format($dateFormat);
        $conn->executeUpdate('INSERT INTO topic_sticky_post(topic_id, post_id, level, create_time)
            VALUES(?, ?, ?, ?) ON DUPLICATE KEY UPDATE create_time = VALUES(create_time)',
            array($topicId, $postId, 0, $dateString),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_STR));
    }

    public function unstickPost($postId) {
        $conn = $this->em->getConnection();
        $stat = $conn->executeQuery('SELECT topic_id FROM post WHERE id = ?',
            array($postId), array(\PDO::PARAM_INT));
        $row = $stat->fetch(\PDO::FETCH_ASSOC);
        if ($row == null) {
            // post not exist
            return;
        }

        $topicId = $row['topic_id'];
        $conn->executeUpdate('DELETE FROM topic_sticky_post WHERE topic_id = ? AND post_id = ?',
            array($topicId, $postId), array(\PDO::PARAM_INT, \PDO::PARAM_INT));
    }

    /**
     * @param int $topicId
     * @return int
     */
    public function countStickies($topicId) {
        $sql = 'SELECT COUNT(post_id) as post_count FROM topic_sticky_post WHERE topic_id = ?';
        $stat = $this->em->getConnection()->executeQuery($sql, array($topicId), array(\PDO::PARAM_INT));
        $result = $stat->fetch(\PDO::FETCH_ASSOC);
        if ($result == null) {
            return 0;
        } else {
            return intval($result['post_count']);
        }
    }

    /**
     * @param int $topicId
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     * @return int[]
     */
    public function getStickyPostIds($topicId, $cursor, $count, &$nextCursor) {
        $sql = 'SELECT post_id FROM topic_sticky_post WHERE topic_id = ? ORDER BY create_time DESC LIMIT ?, ?';
        $stat = $this->em->getConnection()->executeQuery($sql,
            array($topicId, $cursor, $count),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
        $result = $stat->fetchAll(\PDO::FETCH_ASSOC);
        if (count($result) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $cursor + $count;
        }
        return ArrayUtility::columns($result, 'post_id');
    }

    /**
     * @return CursorableIterator
     */
    public function getStickyPostIdIterator() {
        $query = $this->em->createQuery('
            SELECT t.id, t.postId
            FROM Lychee\Module\Post\Entity\TopicStickyPost t
            WHERE t.id < :cursor
            ORDER BY t.id DESC
        ');
        $iterator = new QueryCursorableIterator(
            $query, 'id', 'postId', QueryCursorableIterator::ORDER_DESC
        );
        return $iterator;
    }

    public function clearTimeoutStickyPosts() {
        $now = new \DateTime();
        $timeoutTime = $now->sub(new \DateInterval('P7D'));

        $iterator = $this->getTimeoutStickyPostIdIterator($timeoutTime);
        $iterator->setStep(20);
        foreach ($iterator as $postIds) {
            foreach ($postIds as $postId) {
                $this->unstickPost($postId);
            }
        }
    }

    /**
     * @param \DateTime $timeoutTime
     * @return CursorableIterator
     */
    private function getTimeoutStickyPostIdIterator($timeoutTime) {
        $query = $this->em->createQuery('
            SELECT t.id, t.postId
            FROM Lychee\Module\Post\Entity\TopicStickyPost t
            WHERE t.id > :cursor AND t.createTime <= :timeoutTime
            ORDER BY t.id ASC
        ');
        $query->setParameters(array('timeoutTime' => $timeoutTime));
        $iterator = new QueryCursorableIterator(
            $query, 'id', 'postId', QueryCursorableIterator::ORDER_ASC
        );
        return $iterator;
    }
    
    /**
     * @param int $topicId
     *
     * @return array
     */
    public function fetchStickyPostIds($topicId) {
        $query = $this->em->createQuery('
            SELECT t.postId
            FROM Lychee\Module\Post\Entity\TopicStickyPost t
            WHERE t.topicId = :topicId
            ORDER BY t.postId DESC
        ');
        $query->setParameters(array('topicId' => $topicId));
        $result = $query->getResult();
        return ArrayUtility::columns($result, 'postId');
    }

    /**
     * @param $postIds
     * @return array
     */
    public function filterStickyPostIds($postIds) {
        if (count($postIds) == 0) {
            return array();
        }

        $query = $this->em->createQuery(
            'SELECT t.postId
            FROM Lychee\Module\Post\Entity\TopicStickyPost t
            WHERE t.postId IN (:postIds)'
        );
        $query->setParameter('postIds', $postIds);
        $result = $query->getResult();
        return array_map(function($item) { return $item['postId']; }, $result);
    }

    /**
     * @param int $postId
     * @param int $topicId
     * @return bool
     */
    public function isPostSticky($postId, $topicId) {
        $query = $this->em->createQuery('
            SELECT 1 FROM '.TopicStickyPost::class.' t WHERE t.topicId = :topicId AND t.postId = :postId
        ');
        $query->setParameters(array('topicId' => $topicId, 'postId' => $postId));
        return count($query->getResult()) > 0;
    }
}