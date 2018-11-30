<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 2017/3/15
 * Time: 下午4:40
 */

namespace Lychee\Module\Analysis\Task;

use Lsw\MemcacheBundle\Cache\MemcacheInterface;
use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Database\DoctrineUtility;
use Lychee\Component\Task\Task;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;


/**
 * Class CountActiveVip
 * @package Lychee\Module\Analysis\Task
 */
class CountActiveVip implements Task {

	use ContainerAwareTrait;
	use ModuleAwareTrait;
	/** @var  MemcacheInterface */
	private $memcache;

    public function getName() {
        return 'count active vip';
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
	    	JOIN user_post up ON up.post_id=lp.post_id
	    	JOIN topic t ON t.id=up.topic_id
	    	WHERE up.user_id IN (SELECT user_id FROM user_vip) AND
	    	 p.id>:min_post_id AND p.id<:max_post_id AND p.deleted=0 AND t.private=0 AND lp.state=0 AND 
	    	 lp.update_time>=:start_time AND lp.update_time<:end_time GROUP BY p.author_id
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
		$sql2 = 'SELECT user_id, SUM(duration) AS durationTime FROM ciyo_live.live_record WHERE start_time >= :start_time AND start_time < :end_time AND user_id IN (SELECT user_id FROM user_vip) GROUP BY user_id';
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
		var_dump($activeUsers);
		$this->memcache->delete('recommendActiveVip');
		$this->memcache->set('recommendActiveVip', $activeUsers);
    }
}