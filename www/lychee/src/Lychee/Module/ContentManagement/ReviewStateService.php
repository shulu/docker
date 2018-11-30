<?php
namespace Lychee\Module\ContentManagement;

use Doctrine\ORM\NoResultException;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Lychee\Module\ContentManagement\Entity\ReviewState;

class ReviewStateService {

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @param RegistryInterface $registry
     */
    public function __construct($registry) {
        $this->em = $registry->getManager();
    }

    /**
     * @param int $appId
     * @param string $channel
     * @param string $version
     *
     * @return bool
     */
    public function channelInReview($appId, $channel, $version) {
        $query = $this->em->createQuery('SELECT t.inReview FROM '.ReviewState::class.
            ' t WHERE t.appId = :appId AND t.channel = :channel AND t.version = :version');
        $query->setParameters(array('appId' => $appId, 'channel' => $channel, 'version' => $version));
        try {
            return $query->getSingleResult()['inReview'];
        } catch (NoResultException $e) {
            return false;
        }
    }

    /**
     * @param int $appId
     * @param string $channel
     * @param string $version
     * @param bool $inReview
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function updateChannelReviewState($appId, $channel, $version, $inReview) {
        $sql = 'INSERT INTO review_state(app_id, channel, version, in_review) VALUE (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE version = VALUES(version) in_review = VALUES(in_review)';
        $this->em->getConnection()->executeUpdate($sql, array($appId, $channel, $version, $inReview ? 1 : 0));

    }

    /**
     * @param int $appId
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     *
     * @return ReviewState[]
     */
    public function listChannelReivewStates($appId, $cursor, $count, &$nextCursor) {
        if ($count <= 0) {
            $nextCursor = $cursor;
            return array();
        }

        if ($cursor == 0) {
            $cursor = PHP_INT_MAX;
        }

        $query = $this->em->createQuery('SELECT t FROM '.ReviewState::class.
            ' t WHERE t.appId = :appId AND t.id < :cursor ORDER BY t.id DESC');
        $query->setParameters(array('appId' => $appId, 'cursor' => $cursor));
        $query->setMaxResults($count);
        $result = $query->getResult();

        if (count($result) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $result[count($result) - 1]->id;
        }

        return $result;
    }

    /**
     * @return array
     * 获取所有的应用id
     */
    public function getAppIds() {
        $apps = $this->em->getRepository(ReviewState::class)->createQueryBuilder('rs')
            ->select('distinct rs.appId')
            ->orderBy('rs.appId', 'asc')
            ->getQuery()
            ->getResult();
        if (!$apps) {
            return array();
        }
        return array_column($apps, 'appId');
    }

    /**
     * @param $appId
     * @param $page
     * @param $count
     * @param $total
     * @return array
     * 根据appid分页获取该appid下的记录
     */
    public function getReviewStates($appId, $page, $count, &$total) {
        $cursor = ($page - 1) * $count;
        $query = $this->em->getRepository(ReviewState::class)->createQueryBuilder('rs')
            ->where('rs.appId = :appId')
            ->orderBy('rs.version', 'desc')
            ->addOrderBy('rs.id', 'desc')
            ->setParameter('appId', $appId);
        $totalReviewStates = $query->getQuery()->getResult();
        if ($totalReviewStates) {
            $total = count($totalReviewStates);
        }
        $reviewStates = $query->setFirstResult($cursor)->setMaxResults($count)->getQuery()->getResult();
        if (!$reviewStates) {
            return array();
        }
        return $reviewStates;
    }

    /**
     * @param $version
     * @param $reviewstateId
     * @param $appId
     * 编辑版本信息
     */
    public function editVersion($version, $reviewstateId, $appId) {
        /** @var ReviewState $reviewstate */
        $reviewstate = $this->em->getRepository(ReviewState::class)->findOneBy(array('id'=>$reviewstateId, 'appId'=>$appId));
        if ($reviewstate) {
            $reviewstate->version = $version;
            $this->em->flush();
        }
    }

    /**
     * @param $channel
     * @param $version
     * @param $state
     * @param $appId
     */
    public function addReviewState($channel, $version, $state, $appId) {
        $reviewState = new ReviewState();
        $reviewState->channel = $channel;
        $reviewState->version = $version;
        $reviewState->inReview = $state;
        $reviewState->appId = $appId;
        $this->em->persist($reviewState);
        $this->em->flush();
    }

    /**
     * @param $ids
     * @param $appId
     * @param $state
     * 编辑审核状态
     */
    public function editReviewState($ids, $appId, $state) {
        $reviewstates = $this->em->getRepository(ReviewState::class)->createQueryBuilder('rs')
            ->where('rs.appId=:appId')
            ->andWhere('rs.id in (:ids)')
            ->setParameter('appId', $appId)
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
        if ($reviewstates) {
            foreach ($reviewstates as $reviewstate) {
                $reviewstate->inReview = $state;
                $this->em->flush();
            }
        }
    }
}