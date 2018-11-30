<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Module\Topic\Entity\TopicUserFollowing;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;

class SocialController extends Controller {

    /**
     * @Route("/social/phone/find")
     * @Method("POST")
     * @ApiDoc(
     *   section="social",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="phones", "dataType"="string", "required"=true}
     *   }
     * )
     */
    public function findUserByPhoneNumbers(Request $request) {
        $account = $this->requireAuth($request);
        $phonesCSV = $this->requireParam($request->request, 'phones');
        $phones = explode(',', $phonesCSV);

        $phones = array_filter($phones, 'is_numeric');
        
        $userIds = $this->account()->fetchIdsByPhones($phones);
        $users = $this->account()->fetch($userIds);
        $synthesizer = $this->getSynthesizerBuilder()->buildUserSynthesizer($users, $account->id);

        $result = array();
        foreach ($users as $user) {
            $userData = $synthesizer->synthesizeOne($user->id);
            $userData['area_code'] = $user->areaCode;
            $userData['phone'] = $user->phone;
            $result[] = $userData;
        }

        return $this->dataResponse($result);
    }

    /**
     * @Route("/social/topic/find_by_phone")
     * @Method("POST")
     * @ApiDoc(
     *   section="social",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="phones", "dataType"="string", "required"=true,
     *          "description"="用半角逗号隔开"}
     *   }
     * )
     */
    public function findTopicFollowedByPhone(Request $request) {
        $account = $this->requireAuth($request);
        $phonesCSV = $this->requireParam($request->request, 'phones');
        $phones = explode(',', $phonesCSV, 500);

        $phones = array_filter($phones, 'is_numeric');

        $userIds = $this->account()->fetchIdsByPhones($phones);
        if (count($userIds) == 0) {
            $topicIds = array();
        } else {
            /** @var EntityManager $em */
            $em = $this->get('doctrine')->getManager();
            $dql = 'SELECT t.topicId, COUNT(t.userId) as followCount FROM '.TopicUserFollowing::class
                .' t WHERE t.userId IN (:userIds) GROUP BY t.topicId ORDER BY followCount DESC';
            $query = $em->createQuery($dql);
            $query->setMaxResults(20);
            $query->execute(array('userIds' => $userIds));
            $rows = $query->getArrayResult();

            $followCountMap = array();
            $topicIds = array();
            foreach ($rows as $row) {
                $topicIds[] = $row['topicId'];
                $followCountMap[$row['topicId']] = $row['followCount'];
            }
        }
        $synthesizer = $this->getSynthesizerBuilder()->buildSimpleTopicSynthesizer($topicIds, $account->id);
        $data = $synthesizer->synthesizeAll();
        foreach ($data as &$datum) {
            $datum['friend_follow_count'] = $followCountMap[$datum['id']];
        }
        unset($datum);
        return $this->dataResponse(array('topics' => $data));
    }

    /**
     * @Route("/social/weibo/find")
     * @Method("POST")
     * @ApiDoc(
     *   section="social",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="uids", "dataType"="string", "required"=true}
     *   }
     * )
     */
    public function findUsersByWeiboId(Request $request) {
        $account = $this->requireAuth($request);
        $weiboUidsCSV = $this->requireParam($request->request, 'uids');
        $weiboUids = explode(',', $weiboUidsCSV);

        $userIdsByWeiboUids = $this->authentication()->fetchUserIdsByWeiboUids($weiboUids);

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildUserSynthesizer(array_values($userIdsByWeiboUids), $account->id);

        $result = array();
        foreach ($userIdsByWeiboUids as $weiboUid => $userId) {
            $userData = $synthesizer->synthesizeOne($userId);
            $userData['weibo_id'] = $weiboUid;
            $result[] = $userData;
        }

        return $this->dataResponse($result);
    }

    /**
     * @Route("/social/weibo/callback")
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\DBAL\DBALException
     */
    public function weiboCallback(Request $request) {
        $code = $request->query->get('code');
//        $state = $request->query->get('state');
        $oauth = new \SaeTOAuthV2($this->getParameter('sinaweibo_client_key'), $this->getParameter('sinaweibo_client_secret'));
        $keys = [];
        $keys['code'] = $code;
        $keys['redirect_uri'] = $this->getParameter('weibo_callback_url');
        try {
            $token = $oauth->getAccessToken('code', $keys);
        } catch (\OAuthException $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ]);
        }
        $conn = $this->getDoctrine()->getConnection('weibo_robot');
        $stmt = $conn->prepare('INSERT INTO authorization(uid, access_token, expires_in) VALUE(:uid, :accessToken, :expiresIn)
                                ON DUPLICATE KEY UPDATE access_token=:accessToken, expires_in=:expiresIn');
        $stmt->bindValue(':uid', $token['uid']);
        $stmt->bindValue(':accessToken', $token['access_token']);
        $stmt->bindValue(':expiresIn', $token['expires_in']);
        $stmt->execute();

        return new Response('Success');
    }

} 