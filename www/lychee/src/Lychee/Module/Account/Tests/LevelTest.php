<?php
namespace Lychee\Module\Account\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Account\Mission\LevelCalculator;

class LevelTest extends ModuleAwareTestCase {

    public function test() {
        $levelCalculator = new LevelCalculator();

        for ($i = 0; $i < 200; $i+=1) {
            $exp = rand(0, 44000);
            $this->checkExp($levelCalculator, $exp, false);
        }
        $this->checkExp($levelCalculator, 0, false);
    }

    private function checkExp($levelCalculator, $exp, $echo = false) {
        $level = $levelCalculator->calculate($exp);
        $previousExp = $levelCalculator->getExperienceByLevel($level - 1);
        $levelExp = $levelCalculator->getExperienceByLevel($level);
        $nextLevelExp = $levelCalculator->getExperienceByLevel($level + 1);
        if ($echo) {
            echo "exp: $exp, level: $level, ($previousExp, $levelExp, $nextLevelExp)\n";
        }
        $this->assertTrue(($levelExp == null || $exp >= $levelExp) && ($nextLevelExp == null || $exp < $nextLevelExp));
    }
    
}