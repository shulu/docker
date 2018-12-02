<?php
namespace Lychee\Module\ContentManagement\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\ContentManagement\Domain\DomainType;
use Lychee\Module\ContentManagement\Domain\WhiteList;

class DomainWhitelistTest extends ModuleAwareTestCase {

    private function getWhitelist() {
        return new WhiteList($this->container->get('doctrine'), null);
    }

    public function test() {
        $whitelist = $this->getWhitelist();

        $item = $whitelist->add('虾米4', 'xiami4.com', DomainType::MUSIC);
        $this->assertTrue( $whitelist->contain('xiami4.com') );
        $r = $whitelist->getItemsByPage(1, 10);
        var_dump($r);
        $this->assertTrue(is_array($r));
        $this->assertTrue($whitelist->isValid('www2.xiami4.com'));
        $whitelist->remove($item);
        $r = $whitelist->getItemsByPage(1, 10);
        var_dump($r);


    }
    
}