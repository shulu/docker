<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 9/9/16
 * Time: 3:40 PM
 */

namespace Lychee\Module\Game;


use Doctrine\ORM\EntityManager;
use Lychee\Module\Game\Entity\Banner;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class BannerManager
 * @package Lychee\Module\Game
 */
class BannerManager {

	/**
	 * @var EntityManager
	 */
	private $em;

	public function __construct(RegistryInterface $doctrine) {
		$this->em = $doctrine->getManager();
	}

	public function fetchPublishedBanners() {
		$query = $this->em->createQuery(
			'SELECT b
			FROM ' . Banner::class . ' b
			WHERE b.position IS NOT NULL
			ORDER BY b.position ASC'
		);
		return $query->getResult();
	}

	public function fetchAllBanners() {
		return $this->em->getRepository(Banner::class)->findBy([], ['position' => 'ASC']);
	}

	public function fetchBannersByPage($page = 1, $count = 20) {
		$offset = ($page - 1) * $count;
		$query = $this->em->getRepository(Banner::class)->createQueryBuilder('b')
			->orderBy('b.id', 'DESC')
			->setFirstResult($offset)
			->setMaxResults($count)
			->getQuery();

		return $query->getResult();
	}

	public function getBannersCount() {
		$query = $this->em->getRepository(Banner::class)->createQueryBuilder('b')
			->select('COUNT(b.id) banner_count')
			->setMaxResults(1)
			->getQuery();
		$result = $query->getOneOrNullResult();
		if (!$result) {
			return (int)$result['banner_count'];
		} else {
			return 0;
		}
	}

	public function deleteBanner($id) {
		$banner = $this->em->getRepository(Banner::class)->find($id);
		if ($banner) {
			$this->em->remove($banner);
			$this->em->flush();
		}
	}

	public function getMaxPosition() {
		$promotionBannerRepo = $this->em->getRepository(Banner::class);
		$query = $promotionBannerRepo->createQueryBuilder('p')
			->select('p.position position')
			->where('p.position IS NOT NULL')
			->orderBy('p.position', 'DESC')
			->setMaxResults(1)
			->getQuery();

		$result = $query->getOneOrNullResult();
		if (null === $result) {
			return 0;
		} else {
			return (int)$result['position'];
		}
	}

	public function updateBanner($banner) {
		$this->em->flush($banner);
	}

	public function updateAvailableBanners($ids) {
		$tableName = $this->em->getClassMetadata(Banner::class)->getTableName();

		$sql = 'UPDATE '.$tableName.' t SET t.position = null;';
		$index = 1;
		foreach ($ids as $id) {
			$sql .= 'UPDATE '.$tableName.' t SET t.position = '.$index.' WHERE t.id= ?;';
			$index += 1;
		}
		$this->em->getConnection()->executeQuery($sql, $ids);
	}
}
