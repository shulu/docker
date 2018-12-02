<?php
namespace Lychee\Module\ContentManagement;

use Doctrine\ORM\NoResultException;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Lychee\Module\ContentManagement\Entity\BlockingTopic;

class ContentBlockingService {

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
     * @param string $channel
     * @param string $version
     *
     * @return int[]
     */
    public function getBlockingTopicIdsByChannel($channel, $version) {
        $query = $this->em->createQuery('SELECT t.topics FROM '.BlockingTopic::class.
            ' t WHERE t.channel = :channel AND t.version = :version');
        $query->setParameters(array('channel' => $channel, 'version' => $version));
        try {
            $topicIdsCSV = $query->getSingleResult()['topics'];
        } catch (NoResultException $e) {
            return array();
        }
        return explode(',', $topicIdsCSV);
    }

    /**
     * @param string $channel
     * @param string $version
     * @param int[] $topicIds
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function updateBlockingTopicIdsByChannel($channel, $version, $topicIds) {
        $topicIds = array_filter($topicIds, 'is_numeric');
        if (count($topicIds) == 0) {
            $sql = 'DELETE FROM blocking_topic WHERE channel = ?, version = ?';
            $this->em->getConnection()->executeUpdate($sql, array($channel, $version));
        } else {
            $topicIdsCSV = implode(',', $topicIds);
            $sql = 'INSERT INTO blocking_topic(channel, version, topics) VALUE (?, ?, ?)
                ON DUPLICATE KEY UPDATE topics = ?';
            $this->em->getConnection()->executeUpdate($sql, array($channel, $version, $topicIdsCSV, $topicIdsCSV));
        }
    }

    /**
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     *
     * @return BlockingTopic[]
     */
    public function listBlockingTopics($cursor, $count, &$nextCursor) {
        if ($count <= 0) {
            $nextCursor = $cursor;
            return array();
        }

        if ($cursor == 0) {
            $cursor = PHP_INT_MAX;
        }

        $query = $this->em->createQuery('SELECT t FROM '.BlockingTopic::class.
            ' t WHERE t.id < :cursor ORDER BY t.id DESC');
        $query->setParameters(array('cursor' => $cursor));
        $query->setMaxResults($count);
        $result = $query->getResult();

        if (count($result) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $result[count($result) - 1]->id;
        }

        return $result;
    }

}