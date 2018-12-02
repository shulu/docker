<?php
/**
 * Created by PhpStorm.
 * User: ys160726
 * Date: 2017/2/27
 * Time: 下午1:27
 */

namespace Lychee\Module\Analysis\Task;

use Lsw\MemcacheBundle\Cache\MemcacheInterface;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Task\Task;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Validator\Constraints\DateTime;


/**
 * Class CountUserFollowing
 * @package Lychee\Module\Analysis\Task
 */
class CountUserFollowing implements Task
{
    use ContainerAwareTrait;
    use \Lychee\Bundle\CoreBundle\ContainerAwareTrait;
    private $conn;
    /** @var  MemcacheInterface */
    private $memcache;
    public function getName()
    {
        return 'count user following';
    }

    public function getDefaultInterval()
    {
        return 60 * 60;
    }

    public function run()
    {   
        $this->conn= $this->container()->get('doctrine')->getConnection();
        $this->memcache = $this->container()->get('memcache.default');
        $sql = 'SELECT ufc.target_id FROM user_following_counting AS ufc WHERE ufc.target_id IN ( SELECT uv.user_id FROM user_vip AS uv WHERE uv.user_id != "31721" ) ORDER BY ufc.follower_count DESC LIMIT 0, 50';
        $results = $this->conn->query($sql)->fetchAll();
        $array = array_column($results, 'target_id');
        $this->memcache->delete('count_following');
        $this->memcache->set('count_following', $array);
    }
}