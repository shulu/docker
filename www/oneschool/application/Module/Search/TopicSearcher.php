<?php
namespace Lychee\Module\Search;

use Elastica\Filter\Term;
use Elastica\Query;
use Elastica\Type;
use Elastica\Script;

class TopicSearcher extends AbstractSearcher {
    /**
     * @param string $keywords
     * @param int $offset
     * @param int $limit
     * @param int $total
     * @return mixed
     */
    public function search($keywords, $offset = 0, $limit = 20, &$total = null) {
        $query = new Query();
        $query->setFields(array());

        $functionScore = new Query\FunctionScore();
        $functionScore->setQuery(new Query\QueryString($keywords));
        $script = new Script('doc[\'certified\'].value == \'T\' ? certified_factor : 1', array('certified_factor' => 1.5));
        $functionScore->addScriptScoreFunction($script);
        $query->setQuery($functionScore);
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