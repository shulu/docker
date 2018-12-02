<?php
namespace Lychee\Module\Topic;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Topic\Entity\TopicTag;
use Lychee\Module\Topic\Entity\TopicTagRel;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManagerInterface;

class TopicTagService {

    const CHATING_TAG_ID = 1;
    const SCHEDULE_TAG_ID = 2;

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
     * @param string $name
     * @param string $color
     * @param int $order
     * @return TopicTag
     * @throws Exception\TagDuplicatedException
     */
    public function addTag($name, $color, $order) {
        $tag = new TopicTag();
        $tag->name = $name;
        $tag->color = $color;
        $tag->order = $order;

        try {
            $this->em->persist($tag);
            $this->em->flush($tag);
        } catch (UniqueConstraintViolationException $e) {
            throw new Exception\TagDuplicatedException();
        }

        return $tag;
    }

    /**
     * @param int $tagId
     *
     * @return TopicTag|null
     */
    public function fetchOne($tagId) {
        return $this->em->find(TopicTag::class, $tagId);
    }

    /**
     * @param int $topicId
     * @param int $tagId
     */
    public function topicAddTag($topicId, $tagId) {
        $sql = 'INSERT INTO topic_tag_rel(topic_id, tag_id, update_time)
          VALUE(?, ?, ?) ON DUPLICATE KEY UPDATE update_time = ?';
        $time = time();
        $this->em->getConnection()->executeUpdate($sql,
            array($topicId, $tagId, $time, $time),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT)
        );
    }

    /**
     * @param int $topicId
     * @param int $tagId
     */
    public function topicRemoveTag($topicId, $tagId) {
        $sql = 'DELETE FROM topic_tag_rel WHERE topic_id = ? AND tag_id = ?';
        $this->em->getConnection()->executeUpdate($sql,
            array($topicId, $tagId), array(\PDO::PARAM_INT, \PDO::PARAM_INT));
    }

    /**
     * @param int $topicId
     *
     * @return TopicTag[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getTopicTags($topicId) {
        $sql = 'SELECT c.id, c.name, c.color, c.`order_key` FROM topic_tag as c LEFT JOIN topic_tag_rel as r
          ON c.id = r.tag_id WHERE r.topic_id = '.$topicId.' ORDER BY `order_key` ASC';
        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata(TopicTag::class, 'c');
        $query = $this->em->createNativeQuery($sql, $rsm);
        return $query->getResult();
    }

    public function getTagsByTopics($topicIds) {
        if (empty($topicIds)) {
            return [];
        }
        $sql = 'SELECT r.topic_id, c.id, c.name, c.color, c.`order_key` FROM topic_tag as c LEFT JOIN topic_tag_rel as r
          ON c.id = r.tag_id WHERE r.topic_id IN('. implode(',', $topicIds) .') ORDER BY `order_key` ASC';
        $stat = $this->em->getConnection()->executeQuery($sql);
        $r = $stat->fetchAll(\PDO::FETCH_GROUP);
        return array_map(function($i){
            return array_map(function($t){
                $tag = new TopicTag();
                $tag->id = intval($t['id']);
                $tag->name = $t['name'];
                $tag->color = $t['color'];
                $tag->order = intval($t['order_key']);
                return $tag;
            }, $i);
        }, $r);
    }

    /**
     * @return TopicTag[]
     */
    public function getTags() {
        $query = $this->em->getRepository(TopicTag::class)->createQueryBuilder('t')->getQuery();
        $result = $query->getResult();
        return $result;
    }

    public function clearTopicsTagBefore($tagId, $beforeTime) {
        $sql = 'DELETE FROM topic_tag_rel WHERE tag_id = ? AND update_time < ?';
        $this->em->getConnection()->executeUpdate($sql,
            array($tagId, $beforeTime), array(\PDO::PARAM_INT, \PDO::PARAM_INT));
    }
}