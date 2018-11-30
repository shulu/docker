<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 9/20/16
 * Time: 6:28 PM
 */

namespace Lychee\Module\Game\Task;


use Doctrine\ORM\EntityManager;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Task\Task;
use Lychee\Module\Game\Entity\Banner;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class GameBannerViewsTask implements Task {

	use ContainerAwareTrait;
	use ModuleAwareTrait;

	public function getName() {
		return 'calculate-game-banner-views';
	}

	public function getDefaultInterval() {
		return 3600 * 8;
	}

	public function run() {
		$tbName = 'ciyocon_oss.event_game_banner_view';
		/** @var EntityManager $em */
		$em = $this->container()->get('doctrine')->getManager();
		$conn = $em->getConnection();
		$stmt = $conn->prepare(
			"SELECT DISTINCT(banner_id)
			FROM $tbName"
		);
		$stmt->execute();
		$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		if ($result) {
			$bannerIds = array_map(function($item) {
				return $item['banner_id'];
			}, $result);

			$bannerIdsStr = implode(',', $bannerIds);
			$stmt = $conn->prepare(
				"SELECT banner_id, COUNT(id) banner_views
				FROM $tbName
				WHERE banner_id IN ($bannerIdsStr)
				GROUP BY banner_id"
			);
			$stmt->execute();
			$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			$views = array_reduce($result, function($result, $item) {
				$result[$item['banner_id']] = $item['banner_views'];

				return $result;
			});

			$stmt = $conn->prepare(
				"SELECT tb.banner_id, COUNT(tb.user_id) unique_views
				FROM (SELECT banner_id, user_id
				FROM $tbName
				WHERE banner_id IN ($bannerIdsStr)
				GROUP BY banner_id, user_id) tb
				GROUP BY tb.banner_id"
			);
			$stmt->execute();
			$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			$uniqueViews = array_reduce($result, function($result, $item) {
				$result[$item['banner_id']] = $item['unique_views'];

				return $result;
			});

			$banners = $em->getRepository(Banner::class)->findBy([
				'id' => $bannerIds
			]);
			if ($banners) {
				foreach ($banners as $banner) {
					/** @var Banner $banner */
					$banner->views = $banner->views + $views[$banner->id];
					$banner->uniqueViews = $banner->uniqueViews + $uniqueViews[$banner->id];
					$em->flush($banner);
				}
			}
			$conn->exec("DELETE FROM $tbName WHERE banner_id IN ($bannerIdsStr)");
		}

	}
}