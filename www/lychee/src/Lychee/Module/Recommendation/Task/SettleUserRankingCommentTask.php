<?php
namespace Lychee\Module\Recommendation\Task;

use Lychee\Constant;
use Lychee\Module\Recommendation\UserRankingType;
use Lychee\Component\Foundation\ArrayUtility;

class SettleUserRankingCommentTask extends SettleTask {
    /**
     * @return string
     */
    public function getName() {
        return 'Settle_User_Ranking_Comment';
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
            'LycheeCoreBundle:Comment', 'id', 'createTime'
        );

        $entityManager = $this->em();
        $query = $entityManager->createQuery('
            SELECT t.authorId, COUNT(t.id) as commentCount, MAX(t.id) as maxCommentId
            FROM LycheeCoreBundle:Comment t
            WHERE t.id >= :startId
            AND t.id <= :endId
            AND t.authorId != '.Constant::CIYUANJIANG_ID.'
            AND t.deleted = false
            AND t.imageUrl IS NULL
            GROUP BY t.authorId
            ORDER BY commentCount DESC, maxCommentId ASC
        ');
        $query->setParameters(array(
            'startId' => $minId,
            'endId' => $maxId
        ));
        $query->setMaxResults(50);
        $result = $query->getArrayResult();
        $scoresByIds = ArrayUtility::columns($result, 'commentCount', 'authorId');

        if (count($scoresByIds) > 0) {
            $rankingList = $this->recommendation()->getUserRankingIdList(UserRankingType::COMMENT);
            $rankingList->update($scoresByIds);
        }
    }
} 