<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 15-2-4
 * Time: 上午10:50
 */

namespace Lychee\Module\ContentManagement\Tests;


use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\ContentManagement\InputDomainRecorder;
use Lychee\Module\ContentManagement\Entity\InputDomain;
use Lychee\Module\ContentManagement\Entity\InputDomainRecord;

/**
 * Class InputDomainRecordTest
 * @package Lychee\Module\ContentManagement\Tests
 */
class InputDomainRecordTest extends ModuleAwareTestCase {

    /**
     * @var
     */
    private $domain;

    /**
     *
     */
    public function testRecordWithUniqueDomain() {
        $this->domain = uniqid() . '.demo.com';
        $userId = mt_rand(10000, 99999);
        $this->record($userId);
    }

    /**
     *
     */
    public function testRecordWithSameDomain() {
        $this->domain = uniqid() . '.demo.com';
        $this->record($this->generateUserId());
        $this->record($this->generateUserId());

        $domainEntity = $this->container->get('doctrine')->getManager()->getRepository(InputDomain::class)
            ->findOneBy(array('name' => $this->domain));
        $this->assertEquals(2, $domainEntity->count);
    }

    /**
     * @return int
     */
    private function generateUserId() {
        return mt_rand(10000, 99999);
    }

    /**
     * @param $userId
     */
    private function record($userId) {
        $doctrine = $this->container->get('doctrine');
        $domainRecord = new InputDomainRecorder($doctrine, $this->container->get('memcache.default'));
        $this->assertTrue($domainRecord->record($userId, $this->domain));
    }

    /**
     *
     */
    protected function tearDown() {
        $entityManager = $this->container->get('doctrine')->getManager();
        $domainEntity = $entityManager->getRepository(InputDomain::class)->findOneBy(array(
            'name' => $this->domain,
        ));
        $this->assertNotNull($domainEntity);

        $domainRecordEntities = $entityManager->getRepository(InputDomainRecord::class)->findBy(array(
            'domainId' => $domainEntity->id,
        ));
        $this->assertNotNull($domainRecordEntities);
        foreach ($domainRecordEntities as $domainRecordEntity) {
            $entityManager->remove($domainRecordEntity);
        }
        $entityManager->remove($domainEntity);
        $entityManager->flush();

        parent::tearDown();
    }
}