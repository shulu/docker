<?php
namespace Lychee\Module\Report\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;

class ReportTest extends ModuleAwareTestCase {
    public function test() {
        $s = $this->report();
        $c = $s->countPostReports(716596909731851);
        var_dump($c);
    }
}