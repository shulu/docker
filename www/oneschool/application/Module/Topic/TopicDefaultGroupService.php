<?php
namespace Lychee\Module\Topic;

use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\RegistryInterface;

class TopicDefaultGroupService {

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param RegistryInterface $registry
     */
    public function __construct($registry) {
        $this->em = $registry->getManager();
    }

    /**
     * @param int $topicId
     * @param int|null $groupId
     */
    public function updateDefaultGroup($topicId, $groupId) {
        $groupId = intval($groupId);
        if ($groupId <= 0) {
            $this->removeDefaultGroup($topicId);
            return;
        }

        $sql = 'INSERT INTO topic_default_group(topic_id, group_id) VALUES(?, ?)'
            .' ON DUPLICATE KEY UPDATE group_id = VALUES(group_id)';
        $this->em->getConnection()->executeUpdate($sql, array($topicId, $groupId),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT));
    }

    /**
     * @param int $topicId
     * @return int|null
     */
    public function getDefaultGroup($topicId) {
        $sql = 'SELECT group_id FROM topic_default_group WHERE topic_id = ?';
        $stat = $this->em->getConnection()->executeQuery($sql, array($topicId),
            array(\PDO::PARAM_INT));
        $row = $stat->fetch(\PDO::FETCH_ASSOC);
        if ($row == false) {
            return null;
        } else {
            return intval($row['group_id']);
        }
    }

    /**
     * @param int $topicId
     */
    public function removeDefaultGroup($topicId) {
        $sql = 'DELETE FROM topic_default_group WHERE topic_id = ?';
        $this->em->getConnection()->executeUpdate($sql, array($topicId),
            array(\PDO::PARAM_INT));
    }

}