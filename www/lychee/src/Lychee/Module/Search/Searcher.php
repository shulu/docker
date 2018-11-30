<?php

namespace Lychee\Module\Search;


interface Searcher {
    /**
     * @param string $query
     * @param int $offset
     * @param int $limit
     * @param int $total
     * @return mixed
     */
    public function search($query, $offset = 0, $limit = 20, &$total);
}