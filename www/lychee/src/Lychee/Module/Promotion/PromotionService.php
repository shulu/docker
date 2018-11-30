<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 8/24/16
 * Time: 3:14 PM
 */

namespace Lychee\Module\Promotion;


use Doctrine\Bundle\DoctrineBundle\Registry;
use Lychee\Module\Promotion\Entity\Campaign;
use Lychee\Module\Promotion\Entity\CampaignTopic;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Query\QueryBuilder;

class PromotionService {

    /** @var EntityManagerInterface  */
    private $em;

    public function __construct(Registry $doctrine) {
        $this->em = $doctrine->getManager();
    }

    /**
     * @param $cursor
     * @param int $count
     * @param null $nextCursor
     * @return array
     */
    public function fetchCampaigns($cursor, $count = 20, &$nextCursor = null) {
        if ($cursor == 0) {
            $cursor = PHP_INT_MAX;
        }
        $result = $this->em->getRepository(Campaign::class)->createQueryBuilder('c')
            ->where('c.id<:cursor')
            ->setParameter('cursor', $cursor)
            ->setMaxResults($count)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
        if (!$result || count($result) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $result[count($result) - 1]->id;
        }

        return $result;
    }

    public function fetchCampaignsByPage($page, $count = 20) {
        $offset = ($page - 1) * $count;
        $result = $this->em->getRepository(Campaign::class)->createQueryBuilder('c')
            ->setFirstResult($offset)
            ->setMaxResults($count)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function getTotalCount() {
        $result = $this->em->getRepository(Campaign::class)->createQueryBuilder('c')
            ->select('COUNT(c.id) campaign_count')
            ->getQuery()
            ->getOneOrNullResult();

        if ($result) {
            return $result['campaign_count'];
        }

        return 0;
    }

    /**
     * @param $campaignId
     * @return array
     */
    public function getTopicIdsByCampaign($campaignId) {
        $result = $this->em->getRepository(CampaignTopic::class)->findBy([
            'campaignId' => $campaignId
        ]);

        return $result;
    }

    public function createCampaign($url, $imageUrl, \DateTime $startTime, \DateTime $endTime, $topicsPosition) {
        $campaign = new Campaign();
        $campaign->link = $url;
        $campaign->image = $imageUrl;
        $campaign->startTime = $startTime;
        $campaign->endTime = $endTime;
        $this->em->persist($campaign);
        $this->em->flush();
        foreach ($topicsPosition as $tp) {
            $campaignTopic = new CampaignTopic();
            $campaignTopic->campaignId = $campaign->id;
            $campaignTopic->topicId = $tp[0];
            $campaignTopic->position = $tp[1];
            $this->em->persist($campaignTopic);
            $this->em->flush();
        }
    }

    /**
     * @param $campaignId
     */
    public function removeCampaign($campaignId) {
        $campaign = $this->em->getRepository(Campaign::class)
            ->find($campaignId);
        if ($campaign) {
            $this->em->remove($campaign);
            $campaignTopics = $this->em->getRepository(CampaignTopic::class)
                ->findBy([
                    'campaignId' => $campaignId
                ]);
            if ($campaignTopics) {
                foreach ($campaignTopics as $ct) {
                    $this->em->remove($ct);
                }
            }
            $this->em->flush();
        }
    }

    /**
     * @param $topicId
     * @return array
     */
    public function fetchCampaignsByTopicId($topicId) {
        $now = new \DateTime();
        /** @var \PDO $conn */
        $conn = $this->em->getConnection();
        $stmt = $conn->prepare(
            "SELECT c.*, ct.position FROM campaign_topic ct
            LEFT OUTER JOIN campaign c ON c.id=ct.campaign_id
            WHERE ct.topic_id=:topicId AND c.start_time<=:time AND c.end_time>:time
            ORDER BY ct.campaign_id DESC"
        );
        $stmt->bindValue(':topicId', $topicId);
        $stmt->bindValue(':time', $now->format('Y-m-d'));
        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if ($result) {
            $ret = [];
            foreach ($result as $row) {
                $ret[] = [
                    'id' => $row['id'],
                    'link' => $row['link'],
                    'image' => $row['image'],
                    'position' => $row['position'],
                ];
            }

            return $ret;
        } else {
            return [];
        }
    }

    public function isOccupancy($topicsPosition, \DateTime $startTime, \DateTime $endTime) {
        $conn = $this->em->getConnection();
        $ret = [];
        foreach ($topicsPosition as list($topicId, $position)) {
            $stmt = $conn->prepare(
                'SELECT COUNT(*) position_count FROM campaign_topic ct
                LEFT OUTER JOIN campaign c ON c.id = ct.campaign_id
                WHERE ct.topic_id=:topicId AND ct.position=:position AND 
                (c.start_time>=:startTime AND c.start_time<:endTime OR 
                c.end_time>:startTime AND c.end_time<=:endTime OR 
                c.start_time>=:startTime AND c.end_time<=:endTime)'
            );
            $stmt->bindParam(':topicId', $topicId);
            $stmt->bindParam(':position', $position);
            $stmt->bindValue(':startTime', $startTime->format('Y-m-d'));
            $stmt->bindValue(':endTime', $endTime->format('Y-m-d'));
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($result && $result['position_count'] > 0) {
                $ret[] = [$topicId, $position];
            }
        }

        return $ret;
    }

    public function getViews($campaignId) {
        /** @var Campaign $campaign */
        $campaign = $this->em->getRepository(Campaign::class)->find($campaignId);
        if ($campaign) {
            if ($campaign->views >= 0) {
                return [$campaign->views, $campaign->uniqueViews];
            } else {
                $tableName = 'ciyocon_oss.event_promotion_view';
                $conn = $this->em->getConnection();
                $stmt = $conn->prepare(
                    "SELECT COUNT(id) views FROM $tableName WHERE promotion_id=:campaignId"
                );
                $stmt->bindParam(':campaignId', $campaignId);
                $stmt->execute();
                $result = $stmt->fetch();
                $views = $result['views'];
                $stmt = $conn->prepare(
                    "SELECT COUNT(user_id) unique_views 
                    FROM (SELECT user_id 
                      FROM $tableName 
                      WHERE promotion_id=:campaignId GROUP BY user_id) tb"
                );
                $stmt->bindParam(':campaignId', $campaignId);
                $stmt->execute();
                $result = $stmt->fetch();

                $uniqueViews = $result['unique_views'];
                $now = new \DateTime();
                if ($now > $campaign->endTime) {
                    $campaign->views = $views;
                    $campaign->uniqueViews = $uniqueViews;
                    $this->em->flush();
                    $stmt = $conn->prepare("DELETE FROM $tableName WHERE promotion_id=:campaignId");
                    $stmt->bindParam(':campaignId', $campaignId);
                    $stmt->execute();
                }

                return [$views, $uniqueViews];
            }
        }
    }

    public function fetchCampaign($id) {
        return $this->em->getRepository(Campaign::class)->find($id);
    }

    public function updateCampaign(Campaign $campaign, $topicsPosition) {
        $this->em->flush($campaign);
        $conn = $this->em->getConnection();
        $stmt = $conn->prepare('DELETE FROM campaign_topic WHERE campaign_id=:campaignId');
        $stmt->bindValue(':campaignId', $campaign->id);
        $stmt->execute();
        foreach ($topicsPosition as $tp) {
            $ct = new CampaignTopic();
            $ct->campaignId = $campaign->id;
            $ct->topicId = $tp[0];
            $ct->position = $tp[1];
            $this->em->persist($ct);
            $this->em->flush();
        }
    }
}