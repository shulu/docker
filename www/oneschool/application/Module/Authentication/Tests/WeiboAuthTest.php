<?php

namespace Lychee\Module\Authentication\Tests;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Lychee\Component\Test\ModuleAwareTestCase;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Lychee\Module\Authentication\Entity\QQAuth;
use Doctrine\DBAL\Driver\PDOException;
use Lychee\Module\Authentication\Entity\WeiboAuth;

class WeiboAuthTest extends ModuleAwareTestCase {

    /**
     * @return EntityManager
     */
    private function getManager() {
        return $this->container->get('doctrine')->getManager();
    }

    public function test() {
        $em = $this->getManager();
        try {
            $auth = new WeiboAuth();
            $auth->weiboUid = 1002197357;
            $auth->userId = 81646;
            $em->persist($auth);
            $em->flush($auth);
        } catch (UniqueConstraintViolationException $e) {
            var_dump($e);
            var_dump($e->getErrorCode());
            var_dump($e->getSQLState());
        }
    }
}
 