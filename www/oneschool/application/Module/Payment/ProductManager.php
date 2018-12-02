<?php
namespace Lychee\Module\Payment;

use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Payment\Entity\PaymentProduct;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManagerInterface;

class ProductManager {

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * ProductManager constructor.
     * @param RegistryInterface $doctrine
     */
    public function __construct($doctrine) {
        $this->em = $doctrine->getManager();
    }

	public function fetch( $ids = array() ) {
		$result = $this->em->createQueryBuilder()
			->select('p')
			->from(PaymentProduct::class, 'p')
			->where('p.id IN (:ids)')
			->setParameter('ids', $ids)
			->getQuery()->getResult();
		return ArrayUtility::mapByColumn($result, 'id');
    }

    /**
     * @param $id
     * @return PaymentProduct|null
     */
    public function getProductById($id) {
        return $this->em->find(PaymentProduct::class, $id);
    }

	/**
	 * @param $appStoreId
	 *
	 * @return null|PaymentProduct
	 */
    public function getProductByAppStoreId($appStoreId) {
    	return $this->em->getRepository(PaymentProduct::class)->findOneBy([
    		'appStoreId' => $appStoreId
	    ]);
    }

    /**
     * @return PaymentProduct[]
     */
    public function listProducts() {
        return $this->em->getRepository(PaymentProduct::class)->findAll();
    }

	/**
	 * @param $client
	 *
	 * @return mixed
	 */
    public function listCiyoCoinProducts($client) {
    	if ($client === 'android') {
    		$minProductId = 20000;
		    $maxProductId = 30000;
	    } else {
	    	$minProductId = 30000;
		    $maxProductId = 40000;
	    }

    	$query = $this->em->getRepository(PaymentProduct::class)->createQueryBuilder('p')
		    ->where("p.id>=$minProductId AND p.id<$maxProductId AND p.status=1")
		    ->orderBy('p.price')
		    ->getQuery();

	    return $query->getResult();
    }


}