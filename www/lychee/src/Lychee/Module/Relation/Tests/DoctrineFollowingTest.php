<?php

namespace Lychee\Module\Relation\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Component\GraphStorage\Doctrine\DoctrineFollowingStorage;
use Lychee\Module\Relation\Entity\UserFollowing;
use Lychee\Module\Relation\Entity\UserFollowingCounting;

class DoctrineFollowingTest extends ModuleAwareTestCase {

    /**
     * @return DoctrineFollowingStorage
     */
    private function getStorage() {
        $storage = new DoctrineFollowingStorage(
            $this->container->get('doctrine')->getManager(), UserFollowing::class, UserFollowingCounting::class
        );
        return $storage;
    }

    public function test() {
        $storage = $this->getStorage();
        $ids = array("169665", "153969", 489364, 34687, 393138, 564756, 602055, 468582, 571915, 584148, 560210, 593188, 591169);
        $ids2 = array("169665", "153969", 31728, 373995, 34142, 31722, 197802, 394456, 385941);
        $result = $storage->buildCounter($ids);
        var_dump($result);
    }
}
 