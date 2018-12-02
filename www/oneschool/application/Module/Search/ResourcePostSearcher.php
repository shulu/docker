<?php
namespace Lychee\Module\Search;

use Elastica\Query;
use Elastica\Type;
use Elastica\Filter;

class ResourcePostSearcher extends AbstractSearcher {

    /**
     * @param string $query
     * @param int $offset
     * @param int $limit
     * @param int $total
     * @return mixed
     */
    public function search($keywords, $offset = 0, $limit = 20, &$total = null) {
        $queryString = new Query\QueryString($keywords);
        $query = new Query();
        $query->setFields(array());
        $query->setQuery(new Query\Filtered($queryString,
            new Filter\BoolAnd(array(
                new Filter\Term(array('topic_private' => false)),
                new Filter\Exists('resource'))
            )));
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