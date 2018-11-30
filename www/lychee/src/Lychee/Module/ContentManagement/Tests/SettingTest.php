<?php

namespace Lychee\Module\ContentManagement\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\ContentManagement\Entity\AppLaunchImage;

class SettingTest extends ModuleAwareTestCase {
    public function test() {
        $sizes = array(
            array(480, 800),
            array(540, 960),
            array(600, 1024),
            array(640, 960),
            array(640, 1136),
            array(720, 1280),
            array(750, 1334),
            array(1080, 1920),
            array(1242, 2208),
        );
        $data = array_map(function($size){
            return new AppLaunchImage("http://dl.ciyocon.com/launch_image/huoying2_{$size[0]}x{$size[1]}.png", $size[0], $size[1]);
        }, $sizes);

        $this->contentManagement()->updateAppLaunchImages($data);

    }
}
 