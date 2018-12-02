<?php

namespace Lychee\Module\Recommendation\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;

/**
 * @coversDefaultClass \Lychee\Module\Recommendation\Post\GroupPostsService
 */
class GroupPostsServiceTest extends ModuleAwareTestCase {

    private function getGroupPostsService() {
        return $this->container()->get('lychee.module.recommendation.group_posts');
    }

    private function getConnection() {
        return $this->container->get('doctrine')->getManager()->getConnection();
    }

    private function chunkPostIdsByGroups($groupIds) {
        $conn = $this->getConnection();
        $sql = 'SELECT gp.post_id, group_id
				FROM rec_group_posts gp
				JOIN post p ON p.id=gp.post_id
				WHERE p.deleted=0 AND group_id in ('.implode(',', $groupIds).')';
        $stat = $conn->executeQuery($sql);
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        $ret = [];
        foreach ($rows as $item) {
            if (!isset($ret[$item['group_id']])) {
                $ret[$item['group_id']] = [];
            }
            $ret[$item['group_id']][] = $item['post_id'];
        }
        return $ret;
    }

    private function fill($groupId) {

        $limit = $this->getGroupPostsService()->getMaxLenByGroup($groupId);
        $limit = $limit*2;

        $sql = 'SELECT id from post where deleted=0 order by rand() limit '.$limit;
        $stat = $this->getConnection()->executeQuery($sql);
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        $postIds = \Lychee\Component\Foundation\ArrayUtility::columns($rows, 'id');

        $postIds = $this->getGroupPostsService()->filterNoInGroupPosts($groupId, $postIds);
        $this->getGroupPostsService()->addPostIdsToGroup($groupId, $postIds);
    }

    /**
     *
     * 验证打包查询分组帖子列表，
     * 在 \Lychee\Module\Recommendation\Post\GroupManager::getSubGroupIds
     * 做了关联后起效
     *
     * @covers ::randomListPostIdsInGroup
     *
     */
    public function testPackingFetchSubGroupPosts() {

        $subGroupIds= \Lychee\Module\Recommendation\Post\GroupManager::getSubGroupIds(\Lychee\Module\Recommendation\Post\PredefineGroup::ID_JINGXUAN);
        $groupIds = [\Lychee\Module\Recommendation\Post\PredefineGroup::ID_JINGXUAN];
        $groupIds = array_merge($groupIds, $subGroupIds);

        foreach ($groupIds as $groupId) {
            $this->fill($groupId);
        }

        $mixedChunks =  $this->chunkPostIdsByGroups($groupIds);
        $totalPostIds = [];
        foreach ($mixedChunks as $groupId => $postIds) {
            $totalPostIds = array_merge($totalPostIds, $postIds);
        }
        $totalCount = count(array_unique($totalPostIds));

        $recPostIds = $this->getGroupPostsService()->randomListPostIdsInGroup(
            \Lychee\Module\Recommendation\Post\PredefineGroup::ID_JINGXUAN, $totalCount*2);

        $this->assertEquals($totalCount, count($recPostIds));

        $this->assertTrue(empty(array_diff($totalPostIds, $recPostIds))
            &&empty(array_diff($recPostIds, $totalPostIds)));
    }

}
 