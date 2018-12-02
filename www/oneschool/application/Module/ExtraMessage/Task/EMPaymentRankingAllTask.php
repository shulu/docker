<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 2017/10/27
 * Time: 下午6:23
 */

namespace Lychee\Module\ExtraMessage\Task;


use Lsw\MemcacheBundle\Cache\MemcacheInterface;
use Lychee\Component\Task\Task;
use Lychee\Module\ExtraMessage\ExtraMessageService;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class EMPaymentRankingAllTask implements Task {

	use ContainerAwareTrait;
	use \Lychee\Bundle\CoreBundle\ContainerAwareTrait;

	/**
	 * @var MemcacheInterface
	 */
	private $memcache;

	/**
	 * @var ExtraMessageService
	 */
	private $extraMessageService;

	public function getName() {
		return 'extramessage_payment_ranking_all';
	}

	public function getDefaultInterval() {
		return 300;
	}

	public function run() {

		$this->memcache = $this->container()->get('memcache.default');
		$this->extraMessageService = $this->container()->get('lychee.module.extramessage');

		$totalStoryId = 7;
		$returnResult = array();

		for($i=1;$i<=$totalStoryId;$i++){

			$results = $this->extraMessageService->getRecentPaymentRanking($i, 3);
			$storyResult = array();
			foreach($results as $productPurchased) {

				$ranking = array(
					'nickname' => $productPurchased['nickname'],
					'avatarUrl' => $productPurchased['avatar_url'] ? $productPurchased['avatar_url'] : '',
					'gender' => $productPurchased['gender'] ? $productPurchased['gender'] : 1,
					'age' => $productPurchased['age'] ? $productPurchased['age'] : '',
					'total_fee' => $productPurchased['total_fee']
				);

				$storyResult[] = $ranking;
			}

			$returnResult[] = array(
				'story_id' => $i,
				'users' => $storyResult
			);
		}

		$this->memcache->set('cache_extramessage_payment_ranking_all', $returnResult);
	}

}