<?php

namespace Lychee\Bundle\AdminBundle\Controller;

use Lychee\Bundle\CoreBundle\Entity\User;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\KVStorage\MemcacheStorage;
use Lychee\Module\Activity\Entity\Activity;
use Lychee\Module\Analysis\AnalysisType;
use Lychee\Module\Authentication\Entity\WechatAuth;
use Lychee\Module\ContentManagement\Entity\InputDomain;
use Lychee\Module\ContentManagement\Entity\InputDomainDailyRecord;
use Lychee\Utility;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Lychee\Module\Analysis\Entity\AdminDailyAnalysis;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Lychee\Module\Authentication\Entity\QQAuth;
use Lychee\Module\Authentication\Entity\WeiboAuth;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Lychee\Module\Analysis\Entity\TopicsViews;

/**
 * Class AnalysisController
 * @package Lychee\Bundle\AdminBundle\Controller
 * @Route("/analysis")
 */
class AnalysisController extends BaseController
{
    /**
     * @return string
     */
    public function getTitle()
    {
        return '统计分析';
    }

    /**
     * @Route("/")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function indexAction()
    {
        return $this->redirect($this->generateUrl('lychee_admin_analysis_user'));
    }

    /**
     * @Route("/topic")
     * @Template
     * @return array
     */
    public function topicAction()
    {
        return $this->getAnalysisDataResponse(AnalysisType::TOPIC, $this->getParameter('topic_name'));
    }

    /**
     * @Route("/user")
     * @Template
     * @return array
     */
    public function userAction()
    {
        $futureData = null;
        if ($this->container->getParameter('future_data') === true) {
            $futureData = [0, 200000];
        }
        return $this->getAnalysisDataResponse(AnalysisType::USER, '用户', $futureData);
    }

    /**
     * @Route("/post")
     * @Template
     * @return array
     */
    public function postAction()
    {
        return $this->getAnalysisDataResponse(AnalysisType::POST, '帖子');
    }

    /**
     * @Route("/character_comment")
     * @Template
     * @return array
     */
    public function characterCommentAction()
    {
        $futureData = null;
        if ($this->container->getParameter('future_data') === true) {
            $futureData = [10000, 10000];
        }
        return $this->getAnalysisDataResponse(AnalysisType::CHARACTER_COMMENT, '文字评论', $futureData);
    }

    /**
     * @Route("/image_comment")
     * @Template
     * @return array
     */
    public function imageCommentAction()
    {
        return $this->getAnalysisDataResponse(AnalysisType::IMAGE_COMMENT, '改图评论');
    }

    /**
     * @Route("/active_users")
     * @Template
     * @return array
     */
    public function activeUsersAction()
    {
        $futureData = null;
        if ($this->container->getParameter('future_data') === true) {
            $futureData = ['x2', 0];
        }
        return $this->getAnalysisDataResponse(AnalysisType::ACTIVE_USERS, '活跃用户', $futureData);
    }
    /**
 * @Route("/recharge_user")
 * @Template
 * @return array
 */
    public function rechargeUserAction()
    {
        return $this->getAnalysisDataResponse(AnalysisType::RECHARGE_USER, '充值人数');
    }

    /**
     * @Route("/recharge_times")
     * @Template
     * @return array
     */
    public function rechargeTimesAction()
    {
        return $this->getAnalysisDataResponse(AnalysisType::RECHARGE_TIMES, '充值人次');
    }

    /**
     * @Route("/recharge_income")
     * @Template
     * @return array
     */
    public function rechargeIncomeAction()
    {
        return $this->getAnalysisDataResponse(AnalysisType::RECHARGE_INCOME, '充值收入');
    }

    /**
     * @param $type
     * @param $title
     * @param null $futureData
     * @return array
     */
    private function getAnalysisDataResponse($type, $title, $futureData = null)
    {
        if (null === $futureData) {
            $futureDaily = 0;
            $futureTotal = 0;
        } else {
            $futureDaily = $futureData[0];
            $futureTotal = $futureData[1];
        }
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $limit = $request->query->get('limit', 14);
        $entityManager = $this->getDoctrine()->getManager();
        $dailyAnalysisRepo = $entityManager->getRepository(AdminDailyAnalysis::class);
        $query = $dailyAnalysisRepo->createQueryBuilder('da')
            ->where('da.type = :type')
            ->setParameter('type', $type)
            ->orderBy('da.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery();
        $result = $query->getResult();
        if (null === $result) {
            $result = [];
        }
        $result = array_reverse($result);
        $dates = [];
        $daily = [];
        $total = [];
        foreach ($result as $row) {
            $dates[] = $row->date->format('Y-m-d');
            if (substr($futureDaily, 0, 1) === 'x') {
                $daily[] = $row->dailyCount * (int)substr($futureDaily, 1);
            } else {
                $daily[] = $row->dailyCount + $futureDaily;
            }
            if (substr($futureTotal, 0, 1) === 'x') {
                $total[] = $row->totalCount * (int)substr($futureTotal, 1);
            } else {
                $total[] = $row->totalCount + $futureTotal;
            }
        }
        $totalYAxisMin = intval(min($total) / 10) * 10;
        $dates = array_map(function($item) {
            return "'" . $item . "'";
        }, $dates);

        return $this->response($title, array(
            'dates' => implode(',', $dates),
            'daily' => implode(',', $daily),
            'total' => implode(',', $total),
            'totalYAxisMin' => $totalYAxisMin,
        ));
    }

    /**
     * @Route("/post_like")
     * @Template
     * @return array
     */
    public function postLikeAction()
    {
        $futureData = null;
        if ($this->container->getParameter('future_data') === true) {
            $futureData = ['x2', 'x2'];
        }
        return $this->getAnalysisDataResponse(AnalysisType::POST_LIKE, '帖子点赞', $futureData);
    }

    /**
     * @Route("/comment_like")
     * @Template
     * @return array
     */
    public function commentLikeAction()
    {
        $futureData = null;
        if ($this->container->getParameter('future_data') === true) {
            $futureData = ['x2', 'x2'];
        }
        return $this->getAnalysisDataResponse(AnalysisType::COMMENT_LIKE, '评论点赞', $futureData);
    }

    /**
     * @Route("/following")
     * @Template
     * @return array
     */
    public function followingAction()
    {
        $futureData = null;
        if ($this->container->getParameter('future_data') === true) {
            $futureData = ['x2', 'x2'];
        }
        return $this->getAnalysisDataResponse(AnalysisType::FOLLOWING, '圈Ta', $futureData);
    }

    /**
     * @Route("/content_contribution")
     * @Template
     * @return array
     */
    public function contentContributionAction()
    {
        $futureData = null;
        if ($this->container->getParameter('future_data') === true) {
            $futureData = ['x3', 0];
        }
        return $this->getAnalysisDataResponse(AnalysisType::CONTENT_CONTRIBUTION, '内容贡献', $futureData);
    }

    /**
     * @Route("/sign_in_analysis")
     * @Template
     * @return array
     */
    public function signInAnalysisAction()
    {
        $memcacheService = new MemcacheStorage($this->get('memcache.default'), 'signin:analysis:', 3600 * 12);
        if (!$memcacheService->get('qq')) {
            $em = $this->getDoctrine()->getManager();
            $userRepo = $em->getRepository(User::class);
            $query = $userRepo->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->setMaxResults(1)
                ->getQuery();
            $result = $query->getOneOrNullResult();
            $total = $result[1];

            $qqRepo = $em->getRepository(QQAuth::class);
            $query = $qqRepo->createQueryBuilder('q')
                ->select('COUNT(q.userId)')
                ->setMaxResults(1)
                ->getQuery();
            $qqResult = $query->getOneOrNullResult();
            $qqCount = $qqResult[1];

            $weiboRepo = $em->getRepository(WeiboAuth::class);
            $query = $weiboRepo->createQueryBuilder('w')
                ->select('COUNT(w.userId)')
                ->setMaxResults(1)
                ->getQuery();
            $weiboResult = $query->getOneOrNullResult();
            $weiboCount = $weiboResult[1];

            $wechatRepo = $em->getRepository(WechatAuth::class);
            $query = $wechatRepo->createQueryBuilder('w')
                ->select('COUNT(w.userId)')
                ->setMaxResults(1)
                ->getQuery();
            $wechatResult = $query->getOneOrNullResult();
            $wechatCount = $wechatResult[1];

            $qqPercentage = round($qqCount / $total * 100, 1);
            $weiboPercentage = round($weiboCount / $total * 100, 1);
            $wechatPercentage = round($wechatCount / $total * 100, 1);
            $otherPercentage = 100 - $qqPercentage - $weiboPercentage;

            $memcacheService->setMulti(array(
                'qq' => $qqPercentage,
                'weibo' => $weiboPercentage,
                'wechat' => $wechatPercentage,
                'others' => $otherPercentage,
            ));
        }
        $data = $memcacheService->getMulti(array('qq', 'weibo', 'wechat', 'others'));

        return $this->response('登录方式', $data);
    }

    /**
     * @Route("/export")
     * @Template
     * @return array
     */
    public function exportAction()
    {
        return $this->response('导出数据');
    }

    /**
     * @Route("/export_csv")
     * @param Request $request
     * @return StreamedResponse
     */
    public function exportCsvAction(Request $request)
    {
        $duration = $request->request->get('duration');
        $startDate = $request->request->get('start_date');
        $endDate = $request->request->get('end_date');
        $container = $this->container;
        $type = $request->request->get('type');
        if ('last month' === $duration) {
            $startDate = date('Y-m-01', strtotime('-1 month'));
            $endDate = date('Y-m-01');
        } elseif (null !== $duration) {
            $startDate = date('Y-m-d', strtotime('-31 days'));
            $endDate = date('Y-m-d', strtotime('-1 day'));
        }
        $response = new StreamedResponse(function() use($container, $startDate, $endDate, $type) {

            $queryBuilder = $container->get('doctrine')->getRepository(AdminDailyAnalysis::class)
                ->createQueryBuilder('d');
            if ($type == 'income') {
                $typeList = ["recharge_user","recharge_times","recharge_income"];
                $queryBuilder->where('d.date >= :start_date AND d.date < :end_date AND d.type IN (:typeList)')
                    ->setParameter('start_date', new \DateTime($startDate))
                    ->setParameter('end_date', new \DateTime($endDate))
                    ->setParameter('typeList', $typeList)
                    ->orderBy('d.date');
                $query = $queryBuilder->getQuery();
                $results = $query->getResult();
                $arrays = [];
                foreach ($results as $result) {
                    $arrays[$result->date->format('Y-m-d')][$result->type] = ['dailyCount'=>$result->dailyCount,'totalCount'=>$result->totalCount];
                }
                $df = fopen('php://output', 'r+');
                fputcsv($df, ['日期','当日充值人数','当前日期累计充值人数','当日充值人次','当前日期累计充值人次','当日充值收入','当前日期累计充值收入']);
                foreach ($arrays as $key => $values) {
                    fputcsv($df, array(
                        $key,
                        $values['recharge_user']['dailyCount'],
                        $values['recharge_user']['totalCount'],
                        $values['recharge_times']['dailyCount'],
                        $values['recharge_times']['totalCount'],
                        $values['recharge_income']['dailyCount'],
                        $values['recharge_income']['totalCount']
                    ));
                }
                fclose($df);
            }
            else {
                $queryBuilder->where('d.date >= :start_date AND d.date < :end_date')
                    ->setParameter('start_date', new \DateTime($startDate))
                    ->setParameter('end_date', new \DateTime($endDate))
                    ->orderBy('d.type, d.date');
                $query = $queryBuilder->getQuery();
                $result = $query->getResult();
                $df = fopen('php://output', 'r+');
                foreach ($result as $row) {
                    fputcsv($df, array($row->type, $row->date->format('Y-m-d'), $row->dailyCount, $row->totalCount));
                }
                fclose($df);
            }

        });

        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition', 'attachment; filename="ciyocon_' . $startDate . '.csv"');

        return $response;
    }

    /**
     * @param Request $request
     * @Route("/export_recharge_ciyocoin_detail")
     * @return StreamedResponse
     */
    public function exportRechargeCiyocoinDetailCsv(Request $request) {
        $startDate = $request->request->get('start_date');
        $endDate = $request->request->get('end_date');
        $container = $this->container();
        $response = new StreamedResponse(function() use($container, $startDate, $endDate) {
            
            $results = $this->purchaseRecorder()->getAllRechargeDetailByDate($startDate, $endDate);
            $userIds = array_column($results, 'payer');
            $users = $this->account()->fetch($userIds);
            $userNames = ArrayUtility::columns($users, 'nickname', 'id');
            $df = fopen('php://output', 'r+');
            fputcsv($df, ['用户名','充值时间','充值金额','充值次元币','订单号','平台','支付渠道']);
            foreach ($results as $item) {
                fputcsv($df, array(
                    isset($item['payer']) ? (isset($userNames[$item['payer']]) ? $userNames[$item['payer']] : '') : '',
                    $item['purchase_time'],
                    $item['total_fee'],
                    $item['ciyo_coin'],
                    $item['transaction_id'] ? $item['transaction_id'] : $item['appstore_transaction_id'],
                    $item['transaction_id'] ? '安卓' : '苹果',
                    ($item['pay_type'] == 'wechat') ? '微信' : (($item['pay_type'] == 'alipay') ? '支付宝' : (($item['pay_type'] == 'qpay') ? 'QQ支付': '苹果商店'))
                ));
            }
            fclose($df);
        });

        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition', 'attachment; filename="recharge_ciyocon_detail' . $startDate . '.csv"');

        return $response;
    }

    /**
     * @param Request $request
     * @Route("/exchange_detail_csv")
     * @return StreamedResponse
     */
    public function exportExchangeDetailCsv(Request $request) {
        $startDate = $request->request->get('start_date');
        $endDate = $request->request->get('end_date');
        $response = new StreamedResponse(function() use($startDate, $endDate) {

            $results = $this->purchaseRecorder()->getAllExchangeDetailByDate($startDate, $endDate);
            $userIds = array_column($results, 'user_id');
            $users = $this->account()->fetch($userIds);
            $userNames = ArrayUtility::columns($users, 'nickname', 'id');
            $df = fopen('php://output', 'r+');
            fputcsv($df, ['用户名','兑换时间','兑换次元币', '兑换项', '订单号']);
            foreach ($results as $item) {
                fputcsv($df, array(
                    $item['user_id']? $userNames[$item['user_id']] : '',
                    $item['finish_time'],
                    $item['ciyo_total_fee'],
                    $item['item_name'],
                    $item['out_trade_no']
                ));
            }
            fclose($df);
        });

        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition', 'attachment; filename="exchange_detail' . $startDate . '.csv"');

        return $response;
    }

    /**
     * @Route("/input_domain")
     * @Template
     * @param Request $request
     * @return array
     */
    public function inputDomainAction(Request $request)
    {
        $query = $this->getDoctrine()->getRepository(InputDomain::class)
            ->createQueryBuilder('d')
            ->orderBy('d.count', 'DESC')
            ->setMaxResults(20)
            ->getQuery();
        $domains = $query->getResult();
        $data = [];
        if (null !== $domains) {
            foreach ($domains as $domain) {
                $data[] = [$domain->name, (int)$domain->count];
            }
        }

        $limit = $request->query->get('limit', 14);
        $yesterday = new \DateTime('yesterday midnight');
        $today = new \DateTime('midnight');
        $query = $this->getDoctrine()->getRepository(InputDomainDailyRecord::class)
            ->createQueryBuilder('r')
            ->select(['r.date', 'SUM(r.count) as daily_sum'])
            ->groupBy('r.date')
            ->orderBy('r.date')
            ->setMaxResults($limit)
            ->getQuery();
        $result = $query->getResult();
        $dailyData = [];
        if (null !== $result) {
            foreach ($result as $row) {
                $dailyData[] = [$row['date']->format('m-d'), (int)$row['daily_sum']];
            }
        }

        return $this->response('网站分享', array(
            'data' => $this->formatToString($data),
            'dailyData' => $this->formatToString($dailyData),
        ));
    }

    /**
     * @param $array
     * @return string
     */
    private function formatToString($array) {
        $temp = [];
        foreach ($array as $key => $row) {
            for ($i = 0, $len = count($row); $i < $len; $i++) {
                if (is_string($row[$i])) {
                    $row[$i] = "'" . $row[$i] . "'";
                }
            }
            $rowStr = implode(',', $row);
            if (!is_int($key)) {
                $temp[] = '[' . implode(',', [$key, $rowStr]) . ']';
            } else {
                $temp[] = '[' . $rowStr . ']';
            }
        }

        return '[' . implode(',', $temp) . ']';
    }

    /**
     * @Route("/old_active_users")
     * @Template
     * @return array
     */
    public function oldActiveUsersAction() {
        $memcache = new MemcacheStorage($this->get('memcache.default'), '');
        $resultSet = $memcache->get('old_active_users');

        return $this->response('活跃老用户', array(
            'data' => $resultSet
        ));
    }

    /**
     * @Route("/keyword")
     * @Template
     * @return array
     */
    public function keywordAction() {
        $keywords = $this->searchKeyword()->fetchLatestPopularKeywords(1, 50);
        $result = array_reduce($keywords, function($result, $item) {
            empty($result) && $result = [];
            $result['keyword'][] = $item['keyword'];
            $result['count'][] = (int)$item['record_count'];
            return $result;
        });

        return $this->response('关键字排行', [
            'keywords' => json_encode($result['keyword']),
            'count' => json_encode($result['count']),
        ]);
    }

//    private function imageIncrement($startDate, $endDate) {
//        /**
//         * @var \PDO $conn
//         */
//        $conn = $this->getDoctrine()->getManager()->getConnection();
//        $sql = "SELECT *
//                FROM post
//                WHERE create_time >= :start_time AND create_time <= :end_time
//                ORDER BY id DESC";
//        $stmt = $conn->prepare($sql);
//        $stmt->bindParam('start_time', $startDate->format('Y-m-d H:i:s'));
//        $stmt->bindParam('end_time', $endDate->format('Y-m-d H:i:s'));
//        $stmt->execute();
//        $posts = $stmt->fetchAll();
//        $imageCount = 0;
//        foreach ($posts as $post) {
//            /**
//             * @var \Lychee\Bundle\CoreBundle\Entity\Post $post
//             */
//            $date = (new \DateTime($post->createTime))->format('Y-m-d');
//            if ($post->annotation) {
//                $annotation = json_decode($post->annotation);
//                if (isset($annotation->multi_photos)) {
//                    $imageCount += count($annotation->multi_photos);
//                } elseif ($post->imageUrl) {
//                    $imageCount += 1;
//                }
//            }
//        }
//    }

    /**
     * @Route("/topics_views")
     * @Template
     * @param Request $request
     * @return array
     */
    public function topicsViewsAction(Request $request) {
        $dateStr = $request->query->get('date');
        $orderQuery = $request->query->get('order');
        $order = 'l.views';
        if ('2' == $orderQuery) {
            $order = 'l.uniViews';
        } else {
            $orderQuery = 1;
        }
        if (!$dateStr) {
            $date = new \DateTime('yesterday');
        } else {
            $date = new \DateTime($dateStr);
        }
        $em = $this->getDoctrine()->getManager();
        $logRepo = $em->getRepository(TopicsViews::class);
        $query = $logRepo->createQueryBuilder('l')
            ->where('l.date=:date')
            ->setParameter('date', $date)
            ->orderBy($order, 'DESC')
            ->getQuery();
        $result = $query->getResult();
        $topicIds = array_map(function($item) {
            return $item->topicId;
        }, $result);
        $topics = $this->topic()->fetch($topicIds);

        return $this->response('次元排行', [
            'queryDate' => $date,
            'queryOrder' => $orderQuery,
            'result' => $result,
            'topics' => $topics,
        ]);
    }

    /**
     * @Route("/topic_following")
     * @Template
     * @return array
     */
    public function topicFollowingAction()
    {
        $futureData = null;
        if ($this->container->getParameter('future_data') === true) {
            $futureData = ['x2', 0];
        }
        return $this->getAnalysisDataResponse(AnalysisType::TOPIC_FOLLOWING, '次元入驻', $futureData);
    }

    /**
     * @param Request $request
     * @return array
     * @Route("/recharge_ciyocoin_detail")
     * @Template
     */
    public function rechargeCiyocoinDetail(Request $request) {
        $startDate = $request->query->get('startDate', (new \DateTime())->format('Y-m-d'));
        $endDate = $request->query->get('endDate', (new \DateTime())->format('Y-m-d'));
        $page = $request->query->get('page', 1);
        $count = 20;
        $total = 0;
        $result = $this->purchaseRecorder()->getRechargeDetailByDate($page,$count,$total,$startDate,$endDate);
        $userIds = array_column($result, 'payer');
        $users = $this->account()->fetch($userIds);
        $userNames = ArrayUtility::columns($users, 'nickname', 'id');
        return $this->response('充值明细', array(
            'items' => $result,
            'page' => $page,
            'pageCount' => ceil($total/$count),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'userNames' => $userNames
        ));
    }

    /**
     * @param Request $request
     * @return array
     * @Route("/exchange_detail")
     * @Template
     */
    public function exchangeDetailAction(Request $request) {
        $startDate = $request->query->get('startDate', (new \DateTime())->format('Y-m-d'));
        $endDate = $request->query->get('endDate', (new \DateTime())->format('Y-m-d'));
        $page = $request->query->get('page', 1);
        $count = 20;
        $total = 0;
        $results = $this->purchaseRecorder()->getExchangeDetailByDate($page, $count, $total, $startDate, $endDate);
        $userIds = array_column($results, 'user_id');
        $users = $this->account()->fetch($userIds);
        $userNames = ArrayUtility::columns($users, 'nickname', 'id');
        return $this->response('兑换明细', array(
            'items' => $results,
            'page' => $page,
            'pageCount' => ceil($total/$count),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'userNames' => $userNames
        ));
    }

}
