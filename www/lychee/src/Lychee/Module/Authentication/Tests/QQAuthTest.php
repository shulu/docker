<?php

namespace Lychee\Module\Authentication\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Lychee\Module\Authentication\Entity\QQAuth;

class QQAuthTest extends ModuleAwareTestCase {

    /**
     * @return EntityManager
     */
    private function getManager() {
        return $this->container->get('doctrine')->getManager();
    }

    public function test() {
        $em = $this->getManager();
//        $id = 'EE8EFB621CAC53903D071DD9050501E6';
//        $id = 'EE8EFB621CAC5390';
//
//        $entity = new QQAuth();
//        $entity->openId = $id;
//        $entity->userId = 1;
//
//        $em->persist($entity);
//        $em->flush();
//        $em->clear();

//        $fp = fopen('php://temp', 'rb+');
//        fwrite($fp, $id);
//        fseek($fp, 0);
//        $value = $fp;
//
//        var_dump(stream_get_contents($value));
//        $entity2 = $em->find(QQAuth::class, $value);
//        var_dump($entity2);
//        var_dump(stream_get_contents($entity2->openId));

        $cursor = 80426;
        $cursor = 1;
        $query = $em->createQuery(sprintf('SELECT t FROM %s t WHERE t.userId > :cursor ORDER BY t.userId ASC', QQAuth::class));
        $query->setMaxResults(100);

        while (true) {
//            echo "$cursor,";
            $result = $query->execute(array('cursor' => $cursor));

            foreach ($result as $entity) {

                $openId = stream_get_contents($entity->openId, -1, 0);
                if (preg_match('/^[a-f0-9]{16}$/i', $openId)) {
                    $openIdBin = hex2bin($openId);
                    $em->getConnection()->executeUpdate(
                        'UPDATE auth_qq SET open_id = ? WHERE open_id = ?',
                        array($openIdBin, $openId)
                    );
                    echo "[{$entity->userId}]";
                } else if (preg_match('/^[a-f0-9]+$/i', bin2hex($openId))) {
                    $len = strlen(bin2hex($openId));
                    if ($len == 16) {
                        echo "({$entity->userId})";
                    } else if ($len == 32) {
                        echo ".";
                    } else {
                        echo "!{$entity->userId}!";
                    }
                } else {
                    var_dump($openId);
                    var_dump(bin2hex($openId));
                }
                ob_flush();
            }

            $em->clear();
            if (count($result) < 10) {
                break;
            } else {
                $cursor = $result[count($result) - 1]->userId;
            }
        }

//        $entities = $em->getRepository(QQAuth::class)->findBy(array('userId' => array(
//            80426, 80455, 80456, 80457, 80460, 80461, 80462, 80463, 80464, 80465, 80466 , 80468, 80477
//        )));
//        foreach ($entities as $entity) {
//            var_dump($entity->userId);
//            var_dump(bin2hex( stream_get_contents($entity->openId) ));
//        }

    }
}
 