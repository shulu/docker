<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 3/11/15
 * Time: 4:24 PM
 */

namespace Lychee\Module\ContentManagement\Tests;


use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\ContentManagement\Entity\Sticker;

class StickerManagementTest extends ModuleAwareTestCase {

    public function testAddSticker() {
        $sticker = new Sticker();
        $sticker->isNew = 1;
        $sticker->name = 'Test';
        $sticker->thumbnailUrl = 'http://ciyocon.qiniudn.com/biaoqing_13.png';
        $sticker->url = 'http://ciyocon.qiniudn.com/biaoqing_13.zip';
        $this->sticker()->addSticker($sticker);
    }

    public function testGetStickersWithJson() {
        $str = $this->sticker()->getStickersWithJson();
        echo $str;
    }
}