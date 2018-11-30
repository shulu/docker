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

class EMPaymentRankingStoryTask implements Task {

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
		return 'extramessage_payment_ranking_story';
	}

	public function getDefaultInterval() {
		return 300;
	}

	public function run() {

		$this->memcache = $this->container()->get('memcache.default');
		$this->extraMessageService = $this->container()->get('lychee.module.extramessage');


		$totalStoryId = 7;

		for($i=1;$i<=$totalStoryId;$i++){

			$storyId = $i;
			$results = $this->extraMessageService->getPaymentRanking($storyId, 100);

			$returnResult = array();
			foreach($results as $productPurchased){

				$paymentComment = $this->extraMessageService->getLatestPaymentComment($productPurchased['user_id'], $storyId);

				$comment = '';
				if(!empty($paymentComment)){
					$comment = $paymentComment->comment;
				}

				$ranking = array(
					'nickname' => $productPurchased['nickname'],
					'avatarUrl' => $productPurchased['avatar_url'] ? $productPurchased['avatar_url'] : '',
					'gender' => $productPurchased['gender'] ? $productPurchased['gender'] : 1,
					'age' => $productPurchased['age'] ? $productPurchased['age'] : '',
					'comment' => $comment,
					'total_fee' => $productPurchased['total_fee']
				);

				$returnResult[] = $ranking;
			}
			
			$this->memcache->set('cache_extramessage_payment_ranking_story_' . $storyId, $returnResult);
		}
	}

}