<?php

namespace Lychee\Bundle\AdminBundle\Controller;

use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Component\Foundation\ImageUtility;
use Lychee\Component\KVStorage\MemcacheStorage;
use Lychee\Module\Analysis\AnalysisType;
use Lychee\Module\Analysis\Entity\AdminDailyAnalysis;
use Lychee\Module\Analysis\Entity\TopicsViews;
use Lychee\Module\Topic\Entity\Topic;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DashboardController
 * @package Lychee\Bundle\AdminBundle\Controller
 * @Route("/dashboard")
 */
class DashboardController extends BaseController
{
    /**
     * @return string
     */
    public function getTitle()
    {
        return '仪表板';
    }

    /**
     * @Route("/")
     * @Template
     * @return array
     */
    public function indexAction() {
        return [
            'active_users' => $this->fetchActiveUsers(),
            'newest_topics' => $this->fetchNewestTopics(),
            'hottest_topics' => $this->fetchHottestTopics(),
            'today_users' => $this->fetchTodayUsers(),
        ];
    }

    /**
     * 获取最近7日内活跃用户数
     * @return mixed
     */
    private function fetchActiveUsers() {
        $entityManager = $this->getDoctrine()->getManager();
        $dailyAnalysisRepo = $entityManager->getRepository(AdminDailyAnalysis::class);
        $query = $dailyAnalysisRepo->createQueryBuilder('da')
            ->where('da.type = :type')
            ->setParameter('type', AnalysisType::ACTIVE_USERS)
            ->orderBy('da.date', 'DESC')
            ->setMaxResults(14)
            ->getQuery();
        return $query->getResult();
    }

    /**
     * 获取最新的次元
     * @return array
     */
    private function fetchNewestTopics() {
        $topicIds = $this->topic()->fetchNewestTopicIds(8);

        $topics = $this->topic()->fetch($topicIds);
        rsort($topics);
        foreach ($topics as $t) {
            if (!$t->indexImageUrl) {
                $t->indexImageUrl = 'http://qn.ciyocon.com/admin/dashboard/app_icon.png';
            }
        }

        return $topics;
    }

    private function fetchHottestTopics() {
        $date = new \DateTime('yesterday');
        $em = $this->getDoctrine()->getManager();
        $logRepo = $em->getRepository(TopicsViews::class);
        $query = $logRepo->createQueryBuilder('l')
            ->where('l.date=:date')
            ->setParameter('date', $date)
            ->orderBy('l.views', 'DESC')
            ->setMaxResults(8)
            ->getQuery();
        $result = $query->getResult();
        $topicIds = array_map(function($item) {
            return $item->topicId;
        }, $result);

        return $this->topic()->fetch($topicIds);
    }
    
    private function fetchTodayUsers() {
        $today = new \DateTime('2016-03-29');
        /**
         * @var $conn \PDO
         */
        $conn = $this->getDoctrine()->getConnection();

        $memService = new MemcacheStorage($this->get('memcache.default'), 'admin:dashboard:', 3600 * 24);
        $key = date('Ymd') . '_first_post_id';
        if (!($firstPostId = $memService->get($key))) {
            $stmt = $conn->prepare(
                'SELECT id FROM post
              WHERE create_time<:today
              ORDER BY id DESC
              LIMIT 1'
            );
            $stmt->bindValue(':today', $today->format('Y-m-d H:i:s'));
            $stmt->execute();
            $firstPost = $stmt->fetch();
            $firstPostId = $firstPost['id'];
            $memService->set($key, $firstPostId);
        }
        $stmt = $conn->prepare(
            'SELECT author_id, COUNT(id) post_count
            FROM post
            WHERE id > :id AND deleted = 0
            GROUP BY author_id
            ORDER BY post_count DESC
            LIMIT 8'
        );
        $stmt->bindValue(':id', $firstPostId);
        $stmt->execute();
        $userPosts = $stmt->fetchAll();
        $users = [];
        foreach ($userPosts as $up) {
            $userId = $up['author_id'];
            $user = $this->account()->fetchOne($userId);
            if (!$user->avatarUrl) {
                $user->avatarUrl = 'http://qn.ciyocon.com/admin/dashboard/app_icon.png';
            }
            $users[] = [
                'id' => $user->id,
                'nickname' => $user->nickname,
                'avatarUrl' => ImageUtility::resize($user->avatarUrl, 50, 50),
                'signature' => $user->signature,
                'post_count' => $up['post_count'],
            ];
        }

        return $users;
    }

    /**
     * @Route("/newest_topics")
     * @param Request $request
     * @return JsonResponse
     */
    public function getNewestTopics(Request $request) {
        $latestTopicId = $request->request->get('topic_id');
        $em = $this->getDoctrine()->getManager();
        $query = $em->getRepository(Topic::class)->createQueryBuilder('t')
            ->select('t.id')
            ->where('t.id > :tid')
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(5)
            ->setParameter('tid', $latestTopicId)
            ->getQuery();
        $result = $query->getResult();
        $topicIds = array_map(function($item) { return $item['id']; }, $result);
        $topics = $this->topic()->fetch($topicIds);
        krsort($topics);
        $topics = array_map(function($item) {
            if (!$item->indexImageUrl) {
                $item->indexImageUrl = 'http://qn.ciyocon.com/admin/dashboard/app_icon.png';
            }
            $item->indexImageUrl = ImageUtility::resize($item->indexImageUrl, 50, 50);

            return $item;
        }, $topics);

        return new JsonResponse([
            'topics' => array_values($topics)
        ]);
    }

    /**
     * @Route("/today_users")
     * @return JsonResponse
     */
    public function getTodayUsers() {
        $users = $this->fetchTodayUsers();

        return new JsonResponse([
            'users' => $users,
        ]);
    }
}
