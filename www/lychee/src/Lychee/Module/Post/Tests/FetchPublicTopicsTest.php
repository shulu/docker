<?php
/**
 * Created by PhpStorm.
 * User: ys160726
 * Date: 2017/1/3
 * Time: 下午1:06
 */

namespace Lychee\Module\Post\Tests;


use Lychee\Component\Foundation\CursorWrapper;
use Lychee\Component\Test\ModuleAwareTestCase;

class FetchPublicTopicsTest extends ModuleAwareTestCase
{
    public function test() {
//        $result = $this->post()->fetchIdsByAuthorIdsInPublicTopics([1094997],0,5);
//        var_dump($result);
        $userId = 866720;
        $cursor = 71645614068737;
        $count = 5;
        $userIterator = new CursorWrapper(
            function($cursor, $count, &$nextCursor) use ($userId) {
                return $this->relation()->fetchFolloweeIdsByUserId(
                    $userId, $cursor, $count, $nextCursor
                );
            },
            2
        );
        $result = $this->post()->fetchPublicIdsByAuthorIds($userIterator,$cursor,$count,$nextCursor);
        var_dump($nextCursor);
        var_dump($result);
        
    }
}