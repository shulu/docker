<?php
namespace Lychee\Module\Search;

use Elastica\Query;
use Elastica\Type;
use Lychee\Component\Foundation\ArrayUtility;

class AbstractSearcher implements Searcher {

    protected $type;

    /**
     * @param Type $type
     */
    public function __construct($type) {
        $this->type = $type;
    }

    /**
     * @param string $query
     * @param int $offset
     * @param int $limit
     * @param int $total
     * @return mixed
     */
    public function search($keywords, $offset = 0, $limit = 20, &$total = null) {
        $query = new Query();
        $query->setFields(array());
        $query->setQuery(new Query\QueryString($keywords));
        $query->setFrom($offset);
        $query->setSize($limit);
        try {
            $rs = $this->type->search($query);
            $result = $rs->getResults();
            $total = $rs->getTotalHits();
            return array_map(function($hit){return $hit->getId();}, $result);
        } catch (\Exception $e) {
            return array();
        }
    }

}