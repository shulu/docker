<?php

namespace Lychee\Module\Topic\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Component\GraphStorage\Doctrine\DoctrineFollowingStorage;
use Lychee\Module\Topic\Entity\UserTopicFollowing;

class DoctrineTest extends ModuleAwareTestCase {

    /**
     * @return DoctrineFollowingStorage
     */
    private function getStorage() {
        $storage = new DoctrineFollowingStorage(
            $this->container->get('doctrine')->getManager(), UserTopicFollowing::class, null
        );
        return $storage;
    }

    public function test() {
        $storage = $this->getStorage();
        $r1 = $storage->countFollowers(25073);
        $r2 = $storage->countFollowees(31728);
        $r = $storage->buildCounter(array(31728, 25073));
        var_dump($r1, $r2, $r);
    }
}
 