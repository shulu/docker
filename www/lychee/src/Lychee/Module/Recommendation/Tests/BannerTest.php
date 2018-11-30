<?php

namespace Lychee\Module\Recommendation\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;

class BannerTest extends ModuleAwareTestCase {
    public function test() {
//        $banner = $this->promotion()->createBanner($url, 'http://aaa', 20, 20, 'kaka', 'lalaa');
//        $this->promotion()->updateAvailableBanners(array(2,1));
        $r = $this->recommendationBanner()->fetchAvailableBanners();
        var_dump($r);
    }
}
 