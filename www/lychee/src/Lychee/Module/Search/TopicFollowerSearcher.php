<?php
namespace Lychee\Module\Search;

use Elastica\Filter\Term;
use Elastica\Query;
use Elastica\Type;

class TopicFollowerSearcher extends AbstractSearcher {
    private $topicId = 0;

    /**
     * @param int $topicId
     */
    public function setTopicId($topicId) {
        $this->topicId = $topicId;
    }

    /**
     * @return int
     */
    public function getTopicId() {
        return $this->topicId;
    }

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
        $query->setQuery(new Query\Filtered($queryString, new Term(array('topic_id' => $this->topicId))));
        $query->setFrom($offset);
        $query->setSize($limit);
        try {
            $rs = $this->type->search($query);
            $result = $rs->getResults();
            $total = $rs->getTotalHits();
            return array_map(function($hit){return intval(explode('-', $hit->getId(), 2)[1]);}, $result);
        } catch (\Exception $e) {
            return array();
        }
    }
}