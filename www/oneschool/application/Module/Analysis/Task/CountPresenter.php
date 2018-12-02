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

class CountPresenter implements Task {

	use ContainerAwareTrait;
	use ModuleAwareTrait;
	/** @var  MemcacheInterface */
	private $memcache;

	public function getName() {
		return 'count presenter';
	}

	public function getDefaultInterval() {
		return 3600 * 24;
	}

	public function run() {

		$endTime = new \DateTime('now');
		$endTime->setTime(0, 0, 0);

		$now = new \DateTime('now');
		$startTime = $now->modify('-3 days');

		$em = $this->container()->get('doctrine')->getManager();
		$this->memcache = $this->container()->get('memcache.default');

		$sql = "SELECT lr.user_id AS target_id, SUM(lr.duration) AS duration FROM ciyo_live.live_record AS lr
				INNER JOIN user_following_counting as ufc ON lr.user_id = ufc.target_id
				GROUP BY lr.user_id
				HAVING SUM(lr.duration) >= 15 
				ORDER BY ufc.follower_count DESC LIMIT 0, 50";
		/** @var \PDO $conn */
		$conn = $this->container()->get('doctrine')->getConnection();
		$stmt = $conn->prepare($sql);

		$stmt->execute();
		$result = $stmt->fetchAll();

		$presenter = ArrayUtility::columns($result,'target_id');

		var_dump($presenter);

		$this->memcache->delete('recommendPresenter');
		$this->memcache->set('recommendPresenter', $presenter);
//		$this->memcache->delete('bigManMinPostId');
//		$this->memcache->set('bigManMinPostId', $minPostId);
//        $this->memcache->delete('bigManMaxPostId');
//		$this->memcache->set('bigManMaxPostId', $maxPostId);
	}
}