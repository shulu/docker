<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 5/27/15
 * Time: 4:01 PM
 */

namespace Lychee\Module\ContentManagement\Tests;


use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\ContentManagement\SearchType;

class SearchKeywordTest extends ModuleAwareTestCase {

    public function testRecord() {
        $userIds = range(1, 100);
        foreach ($userIds as $user) {
            $this->searchKeyword()->record(uniqid(), SearchType::TOPIC, $user);
        }
    }
}