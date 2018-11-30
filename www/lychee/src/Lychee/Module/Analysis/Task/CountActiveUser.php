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

class CountActiveUser implements Task {

	use ContainerAwareTrait;
	use ModuleAwareTrait;
	/** @var  MemcacheInterface */
	private $memcache;

	public function getName() {
		return 'count active user';
	}

	public function getDefaultInterval() {
		return 60 * 60;
	}

	public function run() {

		$startTime = new \DateTimeImmutable('Monday last week');
		$endTime = new \DateTimeImmutable('Monday this week');
		$em = $this->container()->get('doctrine')->getManager();
		$this->memcache = $this->container()->get('memcache.default');
		$maxPostId = DoctrineUtility::getMaxIdWithTime($em, Post::class, 'id', 'createTime', $endTime);
		$minPostId = DoctrineUtility::getMinIdWithTime($em, Post::class, 'id', 'createTime', $startTime);
		$sql = "SELECT p.author_id, COUNT(lp.liker_id) AS likes FROM like_post lp
			JOIN post p ON p.id=lp.post_id
	    	JOIN topic t ON t.id=p.topic_id
	    	WHERE p.author_id IN (SELECT id FROM `user` WHERE level >= 11) AND
	    	 p.id>:min_post_id AND p.id<:max_post_id AND p.deleted=0 AND t.private=0 AND lp.state=0 AND 
	    	 lp.update_time>=:start_time AND lp.update_time<:end_time GROUP BY p.author_id ORDER BY likes DESC
	    ";
		/** @var \PDO $conn */
		$conn = $this->container()->get('doctrine')->getConnection();
		$stmt = $conn->prepare($sql);
		$stmt->bindValue(':min_post_id', $minPostId);
		$stmt->bindValue(':max_post_id', $maxPostId);
		$stmt->bindValue(':start_time', $startTime->format('Y-m-d H:i:s'));
		$stmt->bindValue(':end_time', $endTime->format('Y-m-d H:i:s'));
		$stmt->execute();
		$result = $stmt->fetchAll();
		$sql2 = 'SELECT user_id, SUM(duration) AS durationTime FROM ciyo_live.live_record WHERE start_time >= :start_time AND start_time < :end_time AND user_id IN (SELECT user_id FROM `user` WHERE level >= 11) GROUP BY user_id';
		$stmt2 = $conn->prepare($sql2);
		$stmt2->bindValue(':start_time', $startTime->format('Y-m-d H:i:s'));
		$stmt2->bindValue(':end_time', $endTime->format('Y-m-d H:i:s'));
		$stmt2->execute();
		$result2 = $stmt2->fetchAll();
		$likeActiveUserIds = array_column($result,'author_id');
		$liveActiveUserIds =  array_column($result2, 'user_id');
		$result = array_column($result, 'likes', 'author_id');
		$result2 = array_column($result2, 'durationTime', 'user_id');
		$activeUsers = [];
		$diffUserIds = array_diff($liveActiveUserIds, $likeActiveUserIds);
		foreach ($likeActiveUserIds as $id) {
			$activeUsers[$id] = $result[$id] + (isset($result2[$id]) ? $result2[$id] : 0);
		}
		if ($diffUserIds) {
			foreach ($diffUserIds as $id) {
				$activeUsers[$id] = $result2[$id];
			}
		}
		arsort($activeUsers);

		$limitedUser = array_slice($activeUsers, 0, 50, true);

		$this->memcache->delete('recommendActiveUser');
		$this->memcache->set('recommendActiveUser', $limitedUser);
	}
}