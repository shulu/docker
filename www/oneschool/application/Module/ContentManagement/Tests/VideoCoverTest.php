<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 7/12/16
 * Time: 6:49 PM
 */

namespace Lychee\Module\ContentManagement\Tests;


use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Test\ModuleAwareTestCase;

class VideoCoverTest extends ModuleAwareTestCase {
    use ModuleAwareTrait;

    public function test() {
        $miaopaiUrl = 'http://m.miaopai.com/show/channel/AoSM4DAw7490eWzgkMW12A__';
        $bilibiliUrl = 'http://www.bilibili.com/video/av5261221/';
        $contentManagementService = $this->contentManagement();
        $result = $contentManagementService->fetchVideoCover($miaopaiUrl);
        var_dump($result);
        $result = $contentManagementService->fetchVideoCover($bilibiliUrl);
        var_dump($result);
    }
}