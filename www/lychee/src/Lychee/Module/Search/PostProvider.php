<?php
namespace Lychee\Module\Search;

use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Component\Foundation\CursorableIterator\QueryCursorableIterator;

class PostProvider extends AbstractProvider {

    protected function getClassName() {
        return Post::class;
    }

    /**
     * @return CursorableIterator
     */
    protected function getCursorableIterator() {
        $dql = 'SELECT t FROM '.Post::class.' t WHERE t.id > :cursor AND t.deleted = 0 ORDER BY t.id ASC';
        $query = $this->em->createQuery($dql);
        return new QueryCursorableIterator($query, 'id');
    }

}