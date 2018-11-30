<?php
namespace Lychee\Module\Account\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Account\AccountCleaner;

class CleanTest extends ModuleAwareTestCase {

    /**
     * @return AccountCleaner
     */
    private function cleaner() {
        return $this->container()->get('lychee.module.account.posts_cleaner');
    }

    public function test() {
        $cleaner = $this->cleaner();
        $cleaner->cleanUser(31780);
    }
    
}