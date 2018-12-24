<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 15-2-3
 * Time: 下午4:17
 */

namespace app\module\contentmanagement;
use app\module\contentmanagement\model\InputDomain;
use app\module\contentmanagement\model\InputDomainRecord as InputDomainRecordModel;

/**
 * Class InputDomainRecorder
 * @package Lychee\Module\ContentManagement
 */
class InputDomainRecorder
{

    /**
     * @param $userId
     * @param $domain
     * @return bool
     */
    public function record($userId, $domain)
    {
        $domainEntity = $this->getDomain($domain);
        $this->increaseDomain($domainEntity);
        $data = [
            'datetime' => new \DateTime(),
	        'domain_id' => $domainEntity->id,
	        'user_id' => $userId
        ];
	    InputDomainRecordModel::create($data);
	    $domainEntity->save();
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
        #$query = $this->entityManager->getRepository(InputDomain::class)
        #    ->createQueryBuilder('i')
        #    ->where('i.name = :name')
        #    ->setParameter('name', $domainName)
        #    ->setMaxResults(1)
        #    ->getQuery();
	    #$result = $query->getOneOrNullResult();
	
	    $where = ['name'=>$domainName];
		$result = InputDomain::where($where)->find();
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
		$inputDomain->save();
        return $inputDomain;
    }
}