<?php
namespace Lychee\Module\Search;

use Lychee\Component\Foundation\CursorableIterator\QueryCursorableIterator;
use Lychee\Module\Topic\Entity\Topic;

class TopicProvider extends AbstractProvider {

    protected function getClassName() {
        return Topic::class;
    }

    /**
     * @return CursorableIterator
     */
    protected function getCursorableIterator() {
        $dql = 'SELECT t FROM '.Topic::class.' t WHERE t.id > :cursor AND t.deleted = 0 ORDER BY t.id ASC';
        $query = $this->em->createQuery($dql);
        return new QueryCursorableIterator($query, 'id');
    }

}