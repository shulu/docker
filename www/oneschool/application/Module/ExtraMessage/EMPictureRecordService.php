<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 16/01/2017
 * Time: 8:02 PM
 */

namespace Lychee\Module\ExtraMessage;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Lsw\MemcacheBundle\Cache\MemcacheInterface;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\ExtraMessage\Entity\Contacts;
use Lychee\Module\ExtraMessage\Entity\EMClientVersion;
use Lychee\Module\ExtraMessage\Entity\EMDiary;
use Lychee\Module\ExtraMessage\Entity\EMDictionary;
use Lychee\Module\ExtraMessage\Entity\EMPaymentComment;
use Lychee\Module\ExtraMessage\Entity\EMPaymentRanking;
use Lychee\Module\ExtraMessage\Entity\EMPictureRecord;
use Lychee\Module\ExtraMessage\Entity\EMPromotionCode;
use Lychee\Module\ExtraMessage\Entity\Options;
use Lychee\Module\ExtraMessage\Entity\Plays;
use Lychee\Module\ExtraMessage\Entity\EMGameRecord;
use Lychee\Module\ExtraMessage\Entity\EMUser;
use Lychee\Module\ExtraMessage\Entity\ProductPromotionConfig;
use Lychee\Module\Payment\Entity\PaymentProductPurchased;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Lychee\Component\Foundation\IteratorTrait;

/**
 * Class EMPictureRecordService
 * @package Lychee\Module\ExtraMessage
 */
class EMPictureRecordService {

    use IteratorTrait;

	/**
	 * @var EntityManagerInterface
	 */
	private $em;

    /**
     * @var MemcacheInterface
     */
	private $memcache;

    /**
     * @var EntityManager
     */
    private $entityManager;


    /**
     * EMUserService constructor.
     *
     * @param RegistryInterface $doctrine
     *
     */
    public function __construct( RegistryInterface $doctrine, MemcacheInterface $memcache) {
        $this->em = $doctrine->getManager();
        $this->memcache = $memcache;
    }

    public function getRecord($userId) {
        $result = $this->em->getRepository(EMPictureRecord::class)->findOneBy(
            array(
                'userId' => $userId,
            )
        );
        return $result;
    }
    public function saveEntity($item){
        $this->em->persist($item);
        $this->em->flush();
    }

}