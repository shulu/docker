<?php
namespace Lychee\Module\Search\Tests;

use Elastica\Document;
use Lychee\Component\Test\ModuleAwareTestCase;
use Elastica\Query;
use Elastica\Type;

class ElasticsearchTest extends ModuleAwareTestCase {

    /**
     * @return Type object
     */
    private function getTopicType() {
        return $this->container()->get('fos_elastica.index.ciyo.topic');
    }

    public function test() {
//        $this->getTopicType()->addDocument(new Document(13, array('title' => '泥马大蛋糕')));
        $query = new Query();
        $query->setFields(array());
        $query->setQuery(new Query\QueryString('次元'));
        $query->setFrom(0);
        $query->setSize(10);
        $result = $this->getTopicType()->search($query)->getResults();
        var_dump($result);
        $ids = array_map(function($hit){return $hit->getId();}, $result);
        var_dump($ids);
    }
    
}