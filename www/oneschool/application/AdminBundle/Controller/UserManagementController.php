<?php

namespace Lychee\Bundle\AdminBundle\Controller;

use Lychee\Bundle\AdminBundle\Components\Foundation\Paginator;
use Lychee\Bundle\AdminBundle\Service\ManagerLog\OperationType;
use Lychee\Bundle\CoreBundle\Entity\User;
use Lychee\Module\Account\Exception\EmailDuplicateException;
use Lychee\Module\Account\Exception\NicknameDuplicateException;
use Lychee\Module\Account\Exception\PhoneDuplicateException;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\ContentAudit\ContentAuditService;
use Lychee\Module\IM\Message;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class UserManagementController
 * @package Lychee\Bundle\AdminBundle\Controller
 * @Route("/user_management")
 */
class UserManagementController extends BaseController
{

    public function getTitle()
    {
        return '用户管理';
    }

    /**
     * @Route("/")
     * @Template()
     * @return array
     */
    public function indexAction()
    {
        $request = $this->get('Request');
        $query = $request->query->get('query');
        $users = array();
        if (null !== $query) {
            $users = $this->account()->fetchByKeyword($query, 0, 1000);
            if (filter_var($query, FILTER_VALIDATE_INT)) {
                $userQueryById = $this->account()->fetchOne($query);
                if (null !== $userQueryById && !isset($users[$userQueryById->id])) {
                    $users[$userQueryById->id] = $userQueryById;
                }
            }
        }
        arsort($users);

        $topicService = $this->topic();
        $managerTopics = array_reduce($users, function ($result, $user) use ($topicService) {
            $managerId = $user->id;
            $topicIds = $topicService->fetchIdsByManager($managerId, 0, 2, $nextCursor);
            $topics = $topicService->fetch($topicIds);
            if ($nextCursor) {
                array_push($topics, []);
            }
            $result[$managerId] = $topics;

            return $result;
        });

        $ciyoUser = $this->account()->fetchOne($this->account()->getCiyuanjiangID());

        $deviceBlock = array_reduce($users, function ($result, $user) {
            !$result && $result = [];
            /**
             * @var \Lychee\Module\Account\DeviceBlocker $deviceBlocker
             */
            $deviceBlocker = $this->get('lychee.module.account.device_blocker');
            $platformAndDevice = $deviceBlocker->getUserDeviceId($user->id);
            if (is_array($platformAndDevice)) {
                if ($deviceBlocker->isDeviceBlocked($platformAndDevice[0], $platformAndDevice[1])) {
                    array_push($result, $user->id);
                }
            }

            return $result;
        });

        return $this->response($this->getTitle(), array(
            'users' => $users,
            'genderMale' => User::GENDER_MALE,
            'genderFemale' => User::GENDER_FEMALE,
            'ciyoUser' => $ciyoUser,
            'managerTopics' => $managerTopics,
            'deviceBlock' => $deviceBlock,
        ));
    }

    static public function getAdminModuleName()
    {
        return self::$moduleName;
    }

    /**
     * @Route("/block")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function blockAction(Request $request)
    {
        $userId = $request->request->get('user_id');
        $cascadeDelete = $request->request->get('cascade_delete');
        $blockDevice = $request->request->get('block_device');
        $ajax = $request->request->get('freeze_user_ajax');
        $this->account()->freeze($userId);
        /**
         * @var \Lychee\Module\Authentication\TokenIssuer $tokenService
         */
        $tokenService = $this->get('lychee.module.authentication.token_issuer');
        $tokenService->revokeTokensByUser($userId);
        /** @var ContentAuditService $contentAuditService */
        $contentAuditService = $this->get('lychee.module.content_audit');
        $contentAuditService->removeUserFromAntiRubbish($userId);

        $managerLogDesc = [];
        if ($cascadeDelete == '1') {
            /**
             * @var \Lychee\Module\Account\AccountCleaner $cleaner
             */
            $cleaner = $this->get('lychee.module.account.posts_cleaner');
            $cleaner->cleanUser($userId);
            $managerLogDesc['delete_posts_and_comments'] = true;
        }
        if ($blockDevice == '1') {
            /**
             * @var \Lychee\Module\Account\DeviceBlocker $blocker
             */
            $blocker = $this->get('lychee.module.account.device_blocker');
            try {
                $blocker->blockUserDevice($userId);
            } catch (\Exception $e) {
                return $this->redirect($this->generateUrl('lychee_admin_error', [
                    'errorMsg' => $e->getMessage(),
                    'callbackUrl' => $request->headers->get('referer'),
                ]));
            }
            $managerLogDesc['block_device'] = true;
        }
        $this->managerLog()->log($this->getUser()->id, OperationType::BLOCK_USER, $userId, $managerLogDesc);

        if ($ajax == 1) {
            return new JsonResponse();
        } else {
            $referer = $request->headers->get('referer');

            return $this->redirect($referer);
        }
    }

    /**
     * @Route("/create_account")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Lychee\Module\Account\Exception\EmailDuplicateException
     * @throws \Lychee\Module\Account\Exception\PhoneDuplicateException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Exception
     */
    public function createAccountAction(Request $request)
    {
        $account = $request->request->get('account');
        $nickname = $request->request->get('nickname');
        if (!$nickname) {
            throw $this->createNotFoundException('Nickname is Empty');
        }
        if (null !== $this->account()->fetchOneByNickname($nickname)) {
            throw new \Exception('Nickname already exists.');
        }
        if (!$account) {
            throw $this->createNotFoundException('Account is Empty.');
        }
        if (false !== filter_var($account, FILTER_VALIDATE_EMAIL)) {
            if (null !== $this->account()->fetchOneByEmail($account)) {
                throw new EmailDuplicateException();
            }
            $user = $this->account()->createWithEmail($account, $nickname);
        } elseif (false !== filter_var($account, FILTER_VALIDATE_INT)) {
            if (null !== $this->account()->fetchOneByPhone('86', $account)) {
                throw new PhoneDuplicateException();
            }
            $user = $this->account()->createWithPhone($account, '86', $nickname);
        } else {
            throw new \Exception('Invalid Account');
        }
        $gender = (int)$request->request->get('gender', User::GENDER_MALE);
        if ($gender !== User::GENDER_MALE) {
            $gender = User::GENDER_FEMALE;
        }
        $user->gender = $gender;
        if ($request->files->has('avatar')) {
            $imageFile = $request->files->get('avatar');
            if (file_exists($imageFile)) {
                $user->avatarUrl = $this->storage()->put($imageFile);
            }
        }
        $this->account()->updateInfo($user->id, $gender, $user->avatarUrl, $user->signature);
        $password = $request->request->get('password');
        if ($password) {
            $this->authentication()->createPasswordForUser($user->id, $password);
        }

        return $this->redirect($this->generateUrl('lychee_admin_usermanagement_index'));
    }

    /**
     * @Route("/unfreeze")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function unfreezeAction(Request $request)
    {
        $userId = $request->request->get('user_id');
        $this->account()->unfreeze($userId);
        $this->managerLog()->log($this->getUser()->id, OperationType::UNBLOCK_USER, $userId);

        return new JsonResponse();
    }

    /**
     * @Route("/black_list")
     * @Template
     * @param Request $request
     * @return array
     */
    public function blacklistAction(Request $request)
    {
        $paginator = new Paginator($this->account()->frozenUserIterator('DESC'));
        $cursor = $request->query->get('cursor', 0);
        if (0 >= (int)$cursor) {
            $cursor = PHP_INT_MAX;
        }
        $paginator->setCursor($cursor)
            ->setPage($request->query->get('page', 1))
            ->setStep(20)
            ->setStartPageNum($request->query->get('start_page', 1));

        $frozenUsers = $paginator->getResult();
        $userIds = array_keys(ArrayUtility::mapByColumn($frozenUsers, 'userId'));
        $users = $this->account()->fetch($userIds);

        return $this->response('黑名单', array(
            'paginator' => $paginator,
            'users' => $users,
            'frozenUsers' => $frozenUsers,
        ));
    }

    /**
     * @Route("/detail/{userId}")
     * @Template
     * @param $userId
     * @return array
     */
    public function detailAction($userId)
    {
        $user = $this->account()->fetchOne($userId);
        $profile = $this->account()->fetchOneUserProfile($userId);
        $followers = $this->relation()->countUserFollowers($userId);
        $auth = $this->authentication()->getTokenByUserId($userId);
        $ciyoUserId = 31721;
        $ciyoUser = $this->account()->fetchOne($ciyoUserId);
        $lastSignin = $this->get('lychee.module.account.sign_in_recorder')->getUserRecord($userId);
        if ($lastSignin) {
            $lastLogin = $lastSignin->time->format('Y-m-d H:i:s');
        } else {
            $lastLogin = '没有登录信息';
        }

        return $this->response('用户详细信息', [
            'ciyoUser' => $ciyoUser,
            'user' => $user,
            'profile' => $profile,
            'followers' => $followers,
            'auth' => $auth,
            'lastLogin' => $lastLogin,
        ]);
    }

    /**
     * @Route("/sendMessage")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function sendMessageAction(Request $request)
    {
        $from = $request->request->get('chat_from');
        $sendTo = $request->request->get('chat_to');
        $body = $request->request->get('message');
        if (!$sendTo) {
            throw $this->createNotFoundException('Receiver not found');
        }
        $to = [];
        array_push($to, $sendTo);
        $type = 0;
        $message = new Message();
        $message->from = $from;
        $message->to = $to;
        $message->type = $type;
        $message->time = time();
        $message->body = $body;
        $this->get('lychee.module.im')->dispatch([$message]);

        return new JsonResponse();
    }

    /**
     * @Route("/topics/{managerId}/{cursor}", requirements={"managerId" = "\d+", "cursor" = "\d+"})
     * @Template
     * @param $managerId
     * @param int $cursor
     * @return array
     */
    public function managerTopicsAction($managerId, $cursor = 0)
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $prevCursor = $request->query->get('prev_cursor');
        $count = 50;
        $topicIds = $this->topic()->fetchIdsByManager($managerId, $cursor, $count, $nextCursor);
        $topics = $this->topic()->fetch($topicIds);

        return $this->response('领主次元', [
            'managerId' => $managerId,
            'prevCursor' => $prevCursor,
            'cursor' => $cursor,
            'nextCursor' => $nextCursor,
            'topics' => $topics,
        ]);
    }

    /**
     * @Route("/unblock_device")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function unblockDeviceAction(Request $request)
    {
        $userId = $request->request->get('user_id');
        /**
         * @var \Lychee\Module\Account\DeviceBlocker $deviceBlocker
         */
        $deviceBlocker = $this->get('lychee.module.account.device_blocker');
        $platformAndDevice = $deviceBlocker->getUserDeviceId($userId);
        if (is_array($platformAndDevice)) {
            if ($deviceBlocker->isDeviceBlocked($platformAndDevice[0], $platformAndDevice[1])) {
                $deviceBlocker->unblockDevice($platformAndDevice[0], $platformAndDevice[1]);
                $this->managerLog()->log($this->getUser()->id, OperationType::UNBLOCK_DEVICE, $userId, [
                    'platform' => $platformAndDevice[0],
                    'device' => $platformAndDevice[1]
                ]);
            }
        }

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/change_nickname")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function changenicknameAction(Request $request)
    {
        $id = $request->request->get('id');
        $nickname = $request->request->get('nickname');
        try {
            $this->account()->updateNickname($id, $nickname);
        } catch (NicknameDuplicateException $e) {
            return $this->redirect($this->generateUrl('lychee_admin_error', [
                'errorMsg' => '昵称已存在',
                'callbackUrl' => $request->headers->get('referer'),
            ]));
        } catch (\Exception $e) {
            return $this->redirect($this->generateUrl('lychee_admin_error', [
                'errorMsg' => $e->getMessage(),
                'callbackUrl' => $request->headers->get('referer'),
            ]));
        }

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/comments_by_author/{authorId}")
     * @Template
     * @param $authorId
     * @param Request $request
     * @return array
     */
    public function commentsByAuthorAction($authorId, Request $request)
    {
        $cursor = $request->query->get('cursor', 0);
        $prevCursor = $request->query->get('prev_cursor');
        $commentIds = $this->comment()->fetchIdsByAuthorId($authorId, (int)$cursor, 20, $nextCursor);
        $comments = $this->comment()->fetch($commentIds);

        return $this->response('用户评论', [
            'authorId' => $authorId,
            'comments' => $comments,
            'cursor' => $cursor,
            'prevCursor' => $prevCursor,
            'nextCursor' => $nextCursor,
        ]);
    }

    /**
     * @Route("/delete_content")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteComment(Request $request)
    {
        $commentId = $request->request->get('comment_id');
        $this->comment()->delete($commentId);

        return $this->redirect($request->headers->get('referer'));
    }


    /**
     * @Route("/check_sensitive_words/{userId}")
     * @Method("GET")
     * @param Request $request
     * @return JsonResponse
     */
    public function checkSensitiveWords($userId)
    {
        $profile = $this->account()->fetchOneUserProfile($userId);
        $properties = array(
            'signature' => '签名',
            'honmei' => '本命',
            'attributes' => '属性',
            'skills' => '技能',
            'constellation' => '星座',
            'location' => '所在地',
            'fancy' => '喜好'
        );
        $sensitiveWordCheckerService = $this->get('lychee_core.sensitive_word_checker');
        $ret = [];
        foreach ($properties as $key => $title) {
            $list = $sensitiveWordCheckerService->extractSensitiveWords($profile->$key);
            if (empty($list)) {
                continue;
            }
            $ret[] = ['title' => $title, 'words' => $list, 'value' => $profile->$key];
        }
        return new JsonResponse($ret);
    }
}
