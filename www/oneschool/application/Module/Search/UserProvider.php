<?php
namespace Lychee\Module\Search;

use Lychee\Bundle\CoreBundle\Entity\User;
use Lychee\Component\Foundation\CursorableIterator\QueryCursorableIterator;

class UserProvider extends AbstractProvider {

    protected function getClassName() {
        return User::class;
    }

    /**
     * @return CursorableIterator
     */
    protected function getCursorableIterator() {
        $dql = 'SELECT t FROM '.User::class.' t WHERE t.id > :cursor AND t.frozen = 0 ORDER BY t.id ASC';
        $query = $this->em->createQuery($dql);
        return new QueryCursorableIterator($query, 'id');
    }

}