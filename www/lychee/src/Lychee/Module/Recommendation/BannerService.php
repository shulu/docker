<?php
namespace Lychee\Module\Recommendation;

use Doctrine\ORM\EntityManager;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Recommendation\Entity\Banner;
use Lychee\Module\Recommendation\Entity\SubBanner;
use Lychee\Utility;

class BannerService {

    const CLIENT_URL_TOPIC = 1;
    const CLIENT_URL_POST = 2;
    const CLIENT_URL_USER = 3;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param Registry $registry
     * @param string $entityManagerName
     */
    public function __construct($registry, $entityManagerName) {
        $this->entityManager = $registry->getManager($entityManagerName);
    }

    /**
     * @param int $type
     * @param int $targetId
     * @return string
     * @throws \InvalidArgumentException
     */
    public function buildClientUrl($type, $targetId) {
        switch ($type) {
            case self::CLIENT_URL_TOPIC:
                return Utility::buildClientTopicUrl($targetId);
                break;
            case self::CLIENT_URL_POST:
                return Utility::buildClientPostUrl($targetId);
                break;
            case self::CLIENT_URL_USER:
                return Utility::buildClientUserUrl($targetId);
                break;
            default:
                throw new \InvalidArgumentException('unknown type: '.$type);
        }
    }

    /**
     * @return Banner[]
     */
    public function fetchAllBanners() {
        return $this->entityManager->getRepository(Banner::class)->findBy([], [
            'id' => 'DESC'
        ]);
    }

	/**
	 * @param int $page
	 * @param int $count
	 *
	 * @return Banner[]|null
	 */
    public function fetchBannersByPage($page = 1, $count = 20) {
    	$offset = ($page - 1) * $count;

	    return $this->entityManager->getRepository(Banner::class)->findBy([], [
		    'id' => 'DESC'
	    ], $count, $offset);
    }

    public function getBannersCount() {
    	$query = $this->entityManager->getRepository(Banner::class)->createQueryBuilder('b')
		    ->select('COUNT(b.id) banner_count')
		    ->setMaxResults(1)
		    ->getQuery();
	    $result = $query->getOneOrNullResult();

	    if (!$result) {
	    	return 0;
	    } else {
	    	return $result['banner_count'];
	    }
    }

    /**
     * @return Banner[]
     */
    public function fetchAvailableBanners() {
        $query = $this->entityManager->createQuery('
            SELECT t FROM '.Banner::class.' t
            WHERE t.position IS NOT NULL
            ORDER BY t.position ASC
        ');
        $result = $query->getResult();
        return $result;
    }

    /**
     * @param int $id
     *
     * @return null|Banner
     */
    public function fetchOneBanner($id) {
        return $this->entityManager->find(Banner::class, $id);
    }

    /**
     * @param string $url
     * @param string $imageUrl
     * @param int $imageWidth
     * @param int $imageHeight
     * @param string $title
     * @param string $description
     * @return Banner
     */
    public function createBanner($url, $imageUrl, $imageWidth, $imageHeight, $title, $description) {
        $banner = new Banner();
        $banner->url = $url;
        $banner->imageUrl = $imageUrl;
        $banner->imageWidth = $imageWidth;
        $banner->imageHeight = $imageHeight;
        $banner->title = $title;
        $banner->description = $description;
        $this->entityManager->persist($banner);
        $this->entityManager->flush($banner);
        return $banner;
    }

    /**
     * @param Banner $banner
     */
    public function updateBanner($banner) {
        $this->entityManager->flush($banner);
    }

    /**
     * @param int[] $ids normal array
     */
    public function updateAvailableBanners($ids) {
        $tableName = $this->entityManager->getClassMetadata(Banner::class)->getTableName();

        $sql = 'UPDATE '.$tableName.' t SET t.position = null;';
        $index = 1;
        foreach ($ids as $id) {
            $sql .= 'UPDATE '.$tableName.' t SET t.position = '.$index.' WHERE t.id= ?;';
            $index += 1;
        }
        $this->entityManager->getConnection()->executeQuery($sql, $ids);
    }

    /**
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMaxPosition()
    {
        $promotionBannerRepo = $this->entityManager->getRepository(Banner::class);
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

    public function fetchSubBanners($count, $page = 1, $published = true, $deleted = false) {
        $qb = $this->entityManager->getRepository(SubBanner::class)->createQueryBuilder('s');
        if ($published !== null) {
            $qb->where('s.published = :published')->setParameter('published', $published);
        }
        if ($deleted !== null) {
            $qb->andWhere('s.deleted = :deleted')->setParameter('deleted', $deleted);
        }
        $query = $qb->addOrderBy('s.position', 'ASC')
            ->addOrderBy('s.createTime', 'DESC')
            ->setFirstResult(($page - 1) * $count)
            ->setMaxResults($count)
            ->getQuery();
        $result = $query->getResult();

        return $result;
    }

    public function createSubBanner(SubBanner $subBanner) {
        $this->entityManager->persist($subBanner);
        $this->entityManager->flush();
    }

    public function fetchAllPublishedSubBanners() {
        return $this->entityManager->getRepository(SubBanner::class)
            ->findBy([
                'published' => true,
                'deleted' => false,
            ], ['position' => 'ASC']);
    }

    public function getSubBannerCount($deleted, $published = null) {
        $qb = $this->entityManager->getRepository(SubBanner::class)->createQueryBuilder('s');
        $qb->select('COUNT(s.id) banner_count')
            ->where('s.deleted = :deleted')
            ->setParameter('deleted', $deleted);
        if (is_bool($published)) {
            $qb->andWhere('s.published = :published')->setParameter('published', $published);
        }
        $query = $qb->getQuery();
        $result = $query->getOneOrNullResult();

        return $result['banner_count'];
    }

    public function removeSubBanner($id) {
        /**
         * @var SubBanner $subBanner
         */
        $subBanner = $this->entityManager->getRepository(SubBanner::class)->find($id);
        if (null !== $subBanner) {
            $subBanner->setDeleted(true);
            $subBanner->setPublished(false);
            $this->entityManager->flush($subBanner);
        }
    }

    public function recoverSubBanner($id) {
        /**
         * @var SubBanner $subBanner
         */
        $subBanner = $this->entityManager->getRepository(SubBanner::class)->find($id);
        if (null !== $subBanner) {
            $subBanner->setDeleted(false);
            $subBanner->setPublished(false);
            $this->entityManager->flush($subBanner);
        }
    }

    public function publishSubBanner($id) {
        /**
         * @var SubBanner $subBanner
         */
        $subBanner = $this->entityManager->getRepository(SubBanner::class)->find($id);
        if (null !== $subBanner) {
            $subBanner->setPublished(true);
            $subBanner->setPosition(0);
            $this->entityManager->flush();
            $conn = $this->entityManager->getConnection();
            $stmt = $conn->prepare(
                'UPDATE sub_banner SET position = position + 1 WHERE published = 1 AND deleted = 0'
            );
            $stmt->execute();
        }
    }

    public function unpublishSubBanner($id) {
        /**
         * @var SubBanner $subBanner
         */
        $subBanner = $this->entityManager->getRepository(SubBanner::class)->find($id);
        if (null !== $subBanner) {
            $subBanner->setPublished(false);
            $this->entityManager->flush();
        }
    }

    public function orderSubBanners($ids) {
        $subBanners = $this->fetchAllPublishedSubBanners();
        $subBanners = ArrayUtility::mapByColumn($subBanners, 'id');
        $index = 1;
        foreach ($ids as $id) {
            $subBanners[$id]->setPosition($index);
            $index += 1;
        }
        $this->entityManager->flush();
    }

}