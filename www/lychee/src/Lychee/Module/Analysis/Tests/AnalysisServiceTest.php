<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 15-3-2
 * Time: 下午4:09
 */

namespace Lychee\Module\Analysis\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Analysis\AnalysisType;

class AnalysisServiceTest extends ModuleAwareTestCase {

    public function testAnalysisApi() {
        $analysisType = AnalysisType::ACTIVE_USERS;
        $dailyRows = [1, 3, 5];
        $dates = [new \DateTime(), new \DateTime('+1 day'), new \DateTime('+2 day')];
        for ($i = 0; $i < 3; $i++) {
            $this->analysis()->setDailyAnalysis($analysisType, $dates[$i], $dailyRows[$i]);
        }
    }
}