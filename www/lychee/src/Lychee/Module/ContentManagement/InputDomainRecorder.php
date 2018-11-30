<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 15-2-3
 * Time: 下午4:17
 */

namespace Lychee\Module\ContentManagement;

use Lychee\Module\ContentManagement\Entity\InputDomainRecord;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManager;
use Lychee\Module\ContentManagement\Entity\InputDomain;

/**
 * Class InputDomainRecorder
 * @package Lychee\Module\ContentManagement
 */
class InputDomainRecorder {

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param RegistryInterface $doctrineRegistry
     */
    public function __construct($doctrineRegistry) {
        $this->entityManager = $doctrineRegistry->getManager();
    }

    /**
     * @param $userId
     * @param $domain
     * @return bool
     */
    public function record($userId, $domain) {
        $domainEntity = $this->getDomain($domain);
        $inputDomainRecord = new InputDomainRecord();
        $inputDomainRecord->datetime = new \DateTime();
        $inputDomainRecord->domainId = $domainEntity->id;
        $inputDomainRecord->userId = $userId;

        $this->entityManager->persist($inputDomainRecord);
        $this->increaseDomain($domainEntity);
        $this->entityManager->flush();

        return true;
    }

    /**
     * @param $domain
     */
    protected function increaseDomain($domain) {
        $domain->count += 1;
    }

    /**
     * @param $domainName
     * @return InputDomain|mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getDomain($domainName) {
        $query = $this->entityManager->getRepository(InputDomain::class)
            ->createQueryBuilder('i')
            ->where('i.name = :name')
            ->setParameter('name', $domainName)
            ->setMaxResults(1)
            ->getQuery();

        $result = $query->getOneOrNullResult();
        if (null === $result) {
            return $this->addDomain($domainName);
        } else {
            return $result;
        }
    }

    /**
     * @param $domainName
     * @return InputDomain
     */
    private function addDomain($domainName) {
        $inputDomain = new InputDomain();
        $inputDomain->name = $domainName;
        $this->entityManager->persist($inputDomain);
        $this->entityManager->flush();

        return $inputDomain;
    }
}