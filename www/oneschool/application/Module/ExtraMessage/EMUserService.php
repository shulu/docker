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
 * Class ExtraMessageUserService
 * @package Lychee\Module\ExtraMessage
 */
class EMUserService {

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
        $this->entityManager = $doctrine->getManager($doctrine->getDefaultManagerName());
        $this->em = $doctrine->getManager();
        $this->memcache = $memcache;
    }

	public function fetch( $ids = array() ) {
		$result = $this->em->createQueryBuilder()->select('u')
			->from(EMUser::class, 'u')
			->where('u.id in (:ids)')
			->getQuery()->execute(['ids' => $ids]);
		return ArrayUtility::mapByColumn($result, 'id');
    }
    
    public function getUser($name) {
        $result = $this->em->getRepository(EMUser::class)->findBy(
            array(
                'nickname' => $name,
            )
        );
        return $result;
    }

    public function getUserById($id) {
        $result = $this->em->getRepository(EMUser::class)->findBy(
            array(
                'id' => $id,
            )
        );
        return $result;
    }

    public function getSomeUser($count) {
        $sql = 'SELECT * FROM ciyo_extramessage.em_user ORDER BY id LIMIT ?';
        $stat = $this->em->getConnection()->executeQuery($sql,array( $count), array(\PDO::PARAM_INT));
        $row = $stat->fetch();
        return $row;
    }

    /**
     * @param string $order
     * @return \Lychee\Component\Foundation\CursorableIterator\QueryCursorableIterator
     */
    public function iterateTopic($order = 'ASC',$keyword)
    {
        return $this->iterateEntityByKeyword($this->entityManager, EMUser::class,$keyword ,'nickname','id', $order);
    }

    /**
     * @param string $order
     * @return \Lychee\Component\Foundation\CursorableIterator\QueryCursorableIterator
     */
    public function iterateUser($order = 'ASC')
    {
        return $this->iterateEntity($this->entityManager, EMUser::class, 'id', $order);
    }
}