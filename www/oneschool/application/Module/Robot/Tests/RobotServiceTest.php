<?php

namespace Lychee\Module\Robot\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Like\Entity\RobotLikePostTask;

/**
 * @group \Lychee\Module\Robot\RobotService
 */
class RobotServiceTest extends ModuleAwareTestCase {

    private function getService()
    {
        return $this->container()->get('lychee.module.robot');
    }

    private function getDBConnection()
    {
        return $this->container()->get('doctrine')->getManager()->getConnection();
    }

}
