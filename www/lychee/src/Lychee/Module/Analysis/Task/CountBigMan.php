<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 2017/5/8
 * Time: 下午5:09
 */

namespace Lychee\Module\Analysis\Task;

use Lsw\MemcacheBundle\Cache\MemcacheInterface;
use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Database\DoctrineUtility;
use Lychee\Component\Task\Task;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Lychee\Component\Foundation\ArrayUtility;

class CountBigMan implements Task {

	use ContainerAwareTrait;
	use ModuleAwareTrait;
	/** @var  MemcacheInterface */
	private $memcache;

	public function getName() {
		return 'count big man';
	}

	public function getDefaultInterval() {
		return 3600 * 24;
	}

	public function run() {

		$endTime = new \DateTime('now');
		$endTime->setTime(0, 0, 0);

        $now = new \DateTime('now');
		$startTime = $now->modify('-7 days');

		$em = $this->container()->get('doctrine')->getManager();
		$this->memcache = $this->container()->get('memcache.default');
		$maxPostId = DoctrineUtility::getMaxIdWithTime($em, Post::class, 'id', 'createTime', $endTime);
		$minPostId = DoctrineUtility::getMinIdWithTime($em, Post::class, 'id', 'createTime', $startTime);
		$sql = "SELECT target_id FROM user_following_counting
	    		WHERE target_id IN (
	    			SELECT uv.user_id FROM `user_vip` AS uv 
	    			INNER JOIN post AS p ON p.author_id = uv.user_id 
	    			WHERE p.id>:min_post_id AND p.id<:max_post_id AND p.deleted = 0
	    	    ) ORDER BY follower_count DESC LIMIT 0, 50";
		/** @var \PDO $conn */
		$conn = $this->container()->get('doctrine')->getConnection();
		$stmt = $conn->prepare($sql);
		$stmt->bindValue(':min_post_id', $minPostId);
		$stmt->bindValue(':max_post_id', $maxPostId);

		$stmt->execute();
		$result = $stmt->fetchAll();

		$bigManUserIds = ArrayUtility::columns($result,'target_id');

		var_dump($bigManUserIds);

		$this->memcache->delete('recommendBigMan');
        $this->memcache->set('recommendBigMan', $bigManUserIds);
//		$this->memcache->delete('bigManMinPostId');
//		$this->memcache->set('bigManMinPostId', $minPostId);
//        $this->memcache->delete('bigManMaxPostId');
//		$this->memcache->set('bigManMaxPostId', $maxPostId);
	}
}