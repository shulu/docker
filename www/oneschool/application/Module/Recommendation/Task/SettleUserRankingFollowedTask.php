<?php
namespace Lychee\Module\Recommendation\Task;

use Lychee\Constant;
use Lychee\Module\Recommendation\UserRankingType;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Relation\Entity\UserFollowing;

class SettleUserRankingFollowedTask extends SettleTask {
    /**
     * @return string
     */
    public function getName() {
        return 'Settle_User_Ranking_Followed';
    }

    /**
     * @return integer
     */
    public function getDefaultInterval() {
        return 3600 * 12;
    }

    /**
     * @return \DateInterval
     */
    public function getSettleInterval() {
        return new \DateInterval('P3D');
    }

    /**
     * @return void
     */
    public function run() {
        list($minId, $maxId) = $this->getSettleIdRange(
            UserFollowing::class, 'id', 'updateTime'
        );

        $entityManager = $this->em();
        $query = $entityManager->createQuery('
            SELECT t.followeeId, COUNT(t.followerId) as followerCount, MAX(t.id) as maxId
            FROM '.UserFollowing::class.' t
            WHERE t.id >= :startId
            AND t.id <= :endId
            AND t.followeeId != '.Constant::CIYUANJIANG_ID.'
            AND t.state = '.UserFollowing::STATE_NORMAL.'
            GROUP BY t.followeeId
            ORDER BY followerCount DESC, maxId ASC
        ');
        $query->setParameters(array(
            'startId' => $minId,
            'endId' => $maxId
        ));
        $query->setMaxResults(50);
        $result = $query->getArrayResult();
        $scoresByIds = ArrayUtility::columns($result, 'followerCount', 'followeeId');

        if (count($scoresByIds) > 0) {
            $rankingList = $this->recommendation()->getUserRankingIdList(UserRankingType::FOLLOWED);
            $rankingList->update($scoresByIds);
        }
    }
}