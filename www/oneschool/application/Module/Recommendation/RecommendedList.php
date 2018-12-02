<?php
namespace Lychee\Module\Recommendation;

use Doctrine\ORM\NoResultException;
use Lychee\Component\Foundation\CursorableIterator\CursorableIterator;
use Lychee\Component\Foundation\CursorableIterator\QueryCursorableIterator;
use Doctrine\ORM\EntityManager;
use Lsw\MemcacheBundle\Cache\MemcacheInterface;
use Lychee\Module\Recommendation\Entity\RecommendedSequence;

class RecommendedList {

    const TYPE_TOPIC = 'topic';
    const TYPE_POST = 'post';
    const TYPE_COMMENT = 'comment';
    const TYPE_USER = 'user';

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var MemcacheInterface
     */
    private $memcache;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $class;

    /**
     * @var string
     */
    private $targetFieldName;

    /**
     * @param EntityManager $entityManager
     * @param MemcacheInterface $memcache
     * @param string $type
     * @throws \InvalidArgumentException
     */
    public function __construct($entityManager, $memcache, $type) {
        if (!in_array($type, array(
            self::TYPE_TOPIC, self::TYPE_POST,
            self::TYPE_COMMENT, self::TYPE_USER
        ))) {
            throw new \InvalidArgumentException("Unknown type {$type}");
        }
        $this->type = $type;
        $this->class = 'Lychee\Module\Recommendation\Entity\Recommended' . ucfirst($type);
        $this->targetFieldName = $type.'Id';
        $this->entityManager = $entityManager;
        $this->memcache = $memcache;
    }

    private function getCacheKey($sequence) {
        return 'recommended_'.$this->type.'s_'.$sequence;
    }

    /**
     * @param int $sequence
     * @param array $excluded
     *
     * @return CursorableIterator
     */
    public function getIterator($excluded = array(), $sequence = null) {
        if ($sequence === null) {
            $sequence = $this->getCurrentSequence();
        }

        $query = $this->entityManager->createQuery('
            SELECT t
            FROM '.$this->class.' t
            WHERE t.sequence = :sequence
            AND t.id > :cursor
            AND t.'.$this->targetFieldName.' NOT IN (:excluded)
            ORDER BY t.id ASC
        ');
        array_push($excluded, 0);
        $query->setParameters(array(
            'sequence' => $sequence,
            'excluded' => $excluded
        ));
        $backIterator = new QueryCursorableIterator($query, 'id', $this->targetFieldName);

//        $cacheIterator = new MemcacheCursorableIterator(
//            $this->memcache, $this->getCacheKey($sequence), $backIterator
//        );
        return $backIterator;
    }

    /**
     * @param int[] $ids
     * @param int $sequence
     */
    public function addIds($ids, $sequence = null) {
        if ($sequence === null) {
            $sequence = $this->getMaxSequence() + 1;
        }

        $metadata = $this->entityManager->getClassMetadata($this->class);
        $entitiesToFlush = array();
        foreach ($ids as $id) {
            $entity = $metadata->newInstance();
            $entity->sequence = $sequence;
            $entity->{$this->targetFieldName} = $id;
            $this->entityManager->persist($entity);
            $entitiesToFlush[] = $entity;
            if (count($entitiesToFlush) % 100 === 0) {
                $this->entityManager->flush($entitiesToFlush);
                $this->entityManager->clear();
                $entitiesToFlush = array();
            }
        }
        if (count($entitiesToFlush) > 0) {
            $this->entityManager->flush($entitiesToFlush);
        }
        $this->memcache->delete($this->getCacheKey($sequence));
    }

    public function getMaxSequence() {
        $query = $this->entityManager->createQuery('
            SELECT MAX(t.sequence) FROM '.$this->class.' t
        ');
        $maxSequence = $query->getSingleScalarResult();
        if ($maxSequence === null) {
            return 0;
        } else {
            return $maxSequence;
        }
    }

    /**
     * @return RecommendedSequence
     */
    private function fetchCurrentSequence() {
        $query = $this->entityManager->createQuery('
                SELECT t
                FROM Lychee\Module\Recommendation\Entity\RecommendedSequence t
                ORDER BY t.id
            ');
        $query->setMaxResults(1);

        try {
            $sequence = $query->getSingleResult();
            return $sequence;
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @return int
     */
    public function getCurrentSequence() {
        $cachedSequences = $this->memcache->get('recommended_sequences');
        if ($cachedSequences === false) {
            $sequence = $this->fetchCurrentSequence();
            if ($sequence === null) {
                return 0;
            }
            $this->memcache->set('recommended_sequences', $sequence);
            $cachedSequences = $sequence;
        }

        return $cachedSequences->{$this->type.'Sequence'};
    }

    /**
     * @param int $sequence
     */
    public function setCurrentSequence($sequence) {
        $sequences = $this->fetchCurrentSequence();
        if ($sequences === null) {
            $sequences = new RecommendedSequence();
            $this->entityManager->persist($sequences);
        }
        $sequences->{$this->type.'Sequence'} = $sequence;
        $this->entityManager->flush($sequences);
        $this->memcache->delete('recommended_sequences');
    }

    /**
     * @param array $targetIds
     * @param int $sequence
     *
     * @return bool
     */
    public function removeIdsFromSequence($targetIds, $sequence) {
        $query = $this->entityManager->createQuery('
            DELETE '.$this->class.' t
            WHERE t.sequence = :sequence
            AND t.'.$this->targetFieldName.' IN (:targetIds)
        ');
        $query->setParameters(array('sequence' => $sequence, 'targetIds' => $targetIds));
        $result = $query->getResult();
        if ($result > 0) {
            $this->memcache->delete($this->getCacheKey($sequence));
            return true;
        } else {
            return false;
        }
    }
} 