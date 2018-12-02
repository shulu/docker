<?php
namespace Lychee\Module\Search;

use Lychee\Component\Foundation\CursorableIterator\QueryCursorableIterator;
use Lychee\Module\Recommendation\Entity\RecommendableTopic;
use Lychee\Module\Topic\Entity\Topic;

class SuperiorTopicProvider extends AbstractProvider {

    protected function getClassName() {
        return RecommendableTopic::class;
    }

    /**
     * @return int
     */
    protected function getEntitiesCount() {
        $dql = 'SELECT count(1) FROM '.RecommendableTopic::class.' rt 
        INNER JOIN '.Topic::class.' t WITH rt.topicId=t.id 
        WHERE t.deleted = 0';
        $query = $this->em->createQuery($dql);
        return intval($query->getSingleScalarResult());
    }

    /**
     * @return CursorableIterator
     */
    protected function getCursorableIterator() {
        $dql = 'SELECT t FROM '.RecommendableTopic::class.' rt 
        INNER JOIN '.Topic::class.' t WITH rt.topicId=t.id 
        WHERE t.id > :cursor AND t.deleted = 0 ORDER BY t.id ASC';
        $query = $this->em->createQuery($dql);
        return new QueryCursorableIterator($query, 'id');
    }

}