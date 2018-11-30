<?php
namespace Lychee\Module\Recommendation\Post;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Foundation\CursorableIterator\ArrayCursorableIterator;
use Lychee\Module\Recommendation\LastModifiedManager;

class GroupPostsService {

    /**
     * @var EntityManager
     */
    private $em;

    private $lmm;
    private $serviceContainer;

    /**
     * @param Registry $doctrine
     * @param LastModifiedManager $lmm
     */
    public function __construct($doctrine, $lmm, $serviceContainer) {
        $this->em = $doctrine->getManager();
        $this->lmm = $lmm;
        $this->serviceContainer = $serviceContainer;
    }

    public function listPostIdsInGroup($groupId, $cursor, $count, &$nextCursor = null) {
        if ($count <= 0) {
            $nextCursor = $cursor;
            return array();
        }

        if ($cursor == 0) {
            $cursor = PHP_INT_MAX;
        }

        $sql = 'SELECT seq_id, post_id FROM rec_group_posts WHERE group_id = ? AND seq_id < ?'
            .' ORDER BY seq_id DESC LIMIT ?';
        $stat = $this->em->getConnection()->executeQuery($sql, array($groupId, $cursor, $count),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        $postIds = ArrayUtility::columns($rows, 'post_id');
        if (count($postIds) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $rows[count($rows) - 1]['seq_id'];
        }

        return $postIds;
    }

    public function deletePostIdsInGroup($groupId, $postIds) {
        $conn = $this->em->getConnection();
        $offset = 0;
        do {
            $postIdsSlice = array_slice($postIds, $offset, 200, true);
            if (count($postIdsSlice) == 0) {
                break;
            }
            $postIdsInStr = implode(',', $postIds);
            $sql = 'DELETE FROM rec_group_posts WHERE group_id = '.$groupId.' AND post_id IN ('.$postIdsInStr.')';
            $conn->executeUpdate($sql);

            $offset += 200;
        } while (true);

        $this->lmm->updateLastModified('hots');
    }

    public function addPostIdsToGroup($groupId, $postIds, $reAddToTop = false) {
        $conn = $this->em->getConnection();
        $offset = 0;
        do {
            $postIdsSlice = array_slice($postIds, $offset, 200, true);
            if (count($postIdsSlice) == 0) {
                break;
            }
            $values = array();
            foreach ($postIdsSlice as $postId) {
                $values[] = '('.$groupId.','.$postId.')';
            }

            $sql = 'INSERT INTO rec_group_posts(group_id, post_id) VALUES'
                .implode(',', $values)
                .($reAddToTop ? ' ON DUPLICATE KEY UPDATE seq_id = VALUES(seq_id)' : '');
            $conn->executeUpdate($sql);

            $offset += 200;
        } while (true);

        $maxLen = $this->getMaxLenByGroup($groupId);
        $this->truncateGroup($groupId, $maxLen);
        $this->lmm->updateLastModified('hots');
    }

    public function getMaxLenByGroup($groupId) {
        $config = [];
        $config[PredefineGroup::ID_COSPLAY_TOPIC] = 100;
        $config[PredefineGroup::ID_ZHAIWU] = 50;
        if (isset($config[$groupId])) {
            return $config[$groupId];
        }
        return 300;
    }

    private function truncateGroup($groupId, $len) {
        $conn = $this->em->getConnection();
        $querySql = 'SELECT seq_id FROM rec_group_posts WHERE group_id = ? ORDER BY seq_id DESC LIMIT ?, 1';
        $stat = $conn->executeQuery($querySql, array($groupId, $len),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT));
        $row = $stat->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            return;
        }
        $minIdToKeep = $row['seq_id'];
        $deleteSql = 'DELETE FROM rec_group_posts WHERE group_id = ? AND seq_id <= ?';
        $conn->executeUpdate($deleteSql, array($groupId, $minIdToKeep),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT));
    }

    public function filterNoInGroupPosts($groupId, $postIds) {
        if (count($postIds) == 0) {
            return array();
        }

        $slicePostIdItor = new ArrayCursorableIterator($postIds);
        $slicePostIdItor->setStep(500);
        $result = $postIds;
        foreach ($slicePostIdItor as $slicePostIds) {
            if (empty($slicePostIds)) {
                continue;
            }

            $sql = 'SELECT post_id FROM rec_group_posts WHERE group_id = '.$groupId.' AND post_id IN('.implode(',', $slicePostIds).') ';
            $stat = $this->em->getConnection()->executeQuery($sql);
            $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
            $alreadyInListPostIds = ArrayUtility::columns($rows, 'post_id');
            $result = ArrayUtility::diffValue($result, $alreadyInListPostIds);
        }
        return $result;
    }

    /**
     * @param $groupId
     * @param $count
     * @param null $nextCursor
     *
     * @return array
     */
    public function randomListPostIdsInGroupForClient($groupId, $count, &$nextCursor = null, $client=null) {

        if (strtolower($client)=='ios') {
            return $this->randomListPassAuditPostIdsInGroup($groupId, $count, $nextCursor);
        }
        return $this->randomListPostIdsInGroup($groupId, $count, $nextCursor);
    }


    public function randomListPassAuditPostIdsInGroupFromCache($groupId, $limit) {
        $redis = $this->serviceContainer->get('snc_redis.recommendation_cache');
        $listKey = 'pass_audit_hot_tab_posts:'.intval($groupId);
        $postIds = $redis->lrange($listKey, 0, 500);
        shuffle($postIds);
        if (empty($postIds[$limit])) {
            return $postIds;
        }
        return array_splice($postIds, 0, $limit);
    }

    /**
     * @param $groupId
     * @param $count
     * @param null $nextCursor
     *
     * @return array
     */
    public function randomListPassAuditPostIdsInGroup($groupId, $count, &$nextCursor = null) {

        $postIds = $this->randomListPassAuditPostIdsInGroupFromCache($groupId, $count);
        if ($postIds) {
            $nextCursor = $postIds[0];
            return $postIds;
        }

        $groupIds = [$groupId];
        $subGroupIds =  GroupManager::getSubGroupIds($groupId);
        if ($subGroupIds) {
            $groupIds = array_merge($groupIds, $subGroupIds);
        }

        $sql = 'SELECT gp.seq_id, gp.post_id 
				FROM rec_group_posts gp
				JOIN post p ON p.id=gp.post_id
                LEFT JOIN post_audit pa ON pa.post_id = p.id
                WHERE p.deleted=0 
                AND (pa.status IS NULL OR pa.status=1) 
				AND group_id in (';
        $sql .= implode(',', array_fill(0, count($groupIds), '?'));
        $sql .= ') ORDER BY RAND() LIMIT '.intval($count);

        $sqlParams = $groupIds;

        $stat = $this->em->getConnection()->executeQuery($sql, $sqlParams);
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        $postIds = ArrayUtility::columns($rows, 'post_id');

        if (isset($groupIds[1])) {
            $postIds = array_unique($postIds);
        }

        $nextCursor = $postIds[0];

        return $postIds;
    }


	/**
	 * @param $groupId
	 * @param $count
	 * @param null $nextCursor
	 *
	 * @return array
	 */
	public function randomListPostIdsInGroup($groupId, $count, &$nextCursor = null) {

        $groupIds = [$groupId];
	    $subGroupIds =  GroupManager::getSubGroupIds($groupId);
	    if ($subGroupIds) {
	        $groupIds = array_merge($groupIds, $subGroupIds);
        }

		$sql = 'SELECT gp.seq_id, gp.post_id 
				FROM rec_group_posts gp
				JOIN post p ON p.id=gp.post_id
				WHERE p.deleted=0 AND group_id in (';
		$sql .= implode(',', array_fill(0, count($groupIds), '?'));
		$sql .= ') ORDER BY RAND() LIMIT '.intval($count);

		$sqlParams = $groupIds;

		$stat = $this->em->getConnection()->executeQuery($sql, $sqlParams);
		$rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
		$postIds = ArrayUtility::columns($rows, 'post_id');

		if (isset($groupIds[1])) {
		    $postIds = array_unique($postIds);
        }

		$nextCursor = $postIds[0];

		return $postIds;
	}

    /**
     * 短视频帖子id列表，按发布时间降序排
     * @param    int    类型id
     * @param    int    帖子id，作为翻页的游标
     * @param    int    返回的记录数
     * @param    int    用于查询下一页的帖子id
     *
     * @return array    帖子id数组
     */
    public function listShortVideoPostIdsInGroupOrderByPub($groupId, $cursor, $count, &$nextCursor = null) {
        $count = intval($count);
        if ($count <= 0) {
            $nextCursor = $cursor;
            return array();
        }

        if ($cursor == 0) {
            $cursor = PHP_INT_MAX;
        }
        $sql = 'SELECT p.id 
        FROM rec_group_posts gp
        INNER JOIN post p ON p.id=gp.post_id
        WHERE gp.group_id = :groupId AND gp.post_id < :cursor 
        AND p.type = :type AND p.deleted=0
        ORDER BY gp.post_id DESC LIMIT '.($count+1);
        $conn = $this->em->getConnection();
        $sth = $conn->prepare($sql);
        $sth->execute([
            ':groupId'=>$groupId, 
            ':cursor'=>$cursor, 
            ':type'=>\Lychee\Bundle\CoreBundle\Entity\Post::TYPE_SHORT_VIDEO]);
        $res = $sth->fetchAll(\PDO::FETCH_ASSOC);
        $postIds = ArrayUtility::columns($res, 'id');

        $nextCursor = 0;
        if (isset($postIds[$count])) {
            unset($postIds[$count]);
            $nextCursor = end($postIds);
        }

        return $postIds;
    }


}