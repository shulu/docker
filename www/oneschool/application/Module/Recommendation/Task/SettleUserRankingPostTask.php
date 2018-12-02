<?php
namespace Lychee\Module\Recommendation\Task;

use Lychee\Constant;
use Lychee\Module\Recommendation\UserRankingType;
use Lychee\Component\Foundation\ArrayUtility;

class SettleUserRankingPostTask extends SettleTask {
    /**
     * @return string
     */
    public function getName() {
        return 'Settle_User_Ranking_Post';
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
        return new \DateInterval('P2D');
    }

    /**
     * @return void
     */
    public function run() {
        list($minId, $maxId) = $this->getSettleIdRange(
            'LycheeCoreBundle:Post', 'id', 'createTime'
        );

        $entityManager = $this->em();
        $query = $entityManager->createQuery('
            SELECT t.authorId, COUNT(t.id) as postCount, MAX(t.id) as maxPostId
            FROM LycheeCoreBundle:Post t
            WHERE t.id >= :startId
            AND t.id <= :endId
            AND t.authorId != '.Constant::CIYUANJIANG_ID.'
            AND t.deleted = false
            GROUP BY t.authorId
            ORDER BY postCount DESC, maxPostId ASC
        ');
        $query->setParameters(array(
            'startId' => $minId,
            'endId' => $maxId
        ));
        $query->setMaxResults(50);
        $result = $query->getArrayResult();
        $scoresByIds = ArrayUtility::columns($result, 'postCount', 'authorId');

        if (count($scoresByIds) > 0) {
            $rankingList = $this->recommendation()->getUserRankingIdList(UserRankingType::POST);
            $rankingList->update($scoresByIds);
        }
    }
} 