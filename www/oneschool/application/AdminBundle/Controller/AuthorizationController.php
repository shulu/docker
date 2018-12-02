<?php

namespace Lychee\Bundle\AdminBundle\Controller;

use Lychee\Bundle\AdminBundle\Entity\OperationAccount;
use Lychee\Bundle\AdminBundle\Service\ManagerService;
use Lychee\Component\Foundation\ArrayUtility;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Util\SecureRandom;

/**
 * Class AuthorizationController
 * @package Lychee\Bundle\AdminBundle\Controller
 * @Route("/authorization")
 */
class AuthorizationController extends BaseController
{

	private $testPhoneNum = '18520352001';

	/**
	 * @var string
	 */
	private $memcacheKeyOfAccount = 'lychee_admin_post_accounts';

    public function getTitle()
    {
        return '后台权限管理';
    }

    /**
     * @return array
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
    	/** @var ManagerService $managerService */
        $managerService = $this->get('lychee_admin.service.manager');

        return $this->response('管理员', array(
            'managers' => $managerService->listManagers(),
        ));
    }

    /**
     * @Route("/add_account")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Exception
     */
    public function addAccountAction(Request $request)
    {
        $email = $request->request->get('email');
        $nickname = $request->request->get('nickname');
        $password = $request->request->get('password');
        $retypePassword = $request->request->get('retype_password');

        if ($password !== $retypePassword) {
            // 提示用户两次输入密码不相同
            throw new \Exception('密码不相同');
        }
        /** @var ManagerService $managerService */
        $managerService = $this->get('lychee_admin.service.manager');
        if ($managerService->loadManagerByEmail($email)) {
            throw new \Exception('用户已存在');
        }
        if (!$nickname) {
	        $nickname = strstr($email, '@', true);
        }
        $managerService->createManager($email, $nickname, $password);

        return $this->redirect($this->generateUrl('lychee_admin_authorization_index'));
    }

	/**
	 * @Route("/account/edit")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
    public function modifyAccount(Request $request) {
    	$id = $request->request->get('account_id');
	    $email = $request->request->get('account_email');
	    $nickname = $request->request->get('account_nickname');
	    /** @var ManagerService $managerService */
	    $managerService = $this->get('lychee_admin.service.manager');
	    $manager = $managerService->loadManager($id);
	    if (!$manager) {
	    	return $this->redirectErrorPage('用户不存在', $request, $this->generateUrl('lychee_admin_authorization_index'));
	    }
	    $manager->email = $email;
	    $manager->name = $nickname;
	    $managerService->update($manager);

	    return $this->redirect($this->generateUrl('lychee_admin_authorization_index'));
    }

    /**
     * @Route("/permission")
     * @Template
     * @return array
     */
    public function permissionAction()
    {
        return $this->response('后台权限', array(
            'managers' => $this->get('lychee_admin.service.manager')->listManagers(),
            'modules' => $this->getDoctrine()->getRepository('LycheeAdminBundle:Role')->findAll(),
        ));
    }

    /**
     * @Route("/switch")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function switchPermissionAction(Request $request)
    {
        $authorize = $request->request->get('authorize', false);
        $roleId = $request->request->get('role_id');
        $managerId = $request->request->get('manager_id');

        $em = $this->getDoctrine()->getManager();
        $role = $this->get('lychee_admin.service.role')->fetchRole($roleId);
        $manager = $this->get('lychee_admin.service.manager')->loadManager($managerId);
        if (!$manager || !$role) {
            throw new AccessDeniedException();
        }
        $roles = $manager->getRoles();
        if (!in_array($role, $roles)) {
            if (!$authorize) {
                $manager->roles[] = $role;
            }
        } else {
            if ($authorize) {
                for ($i = 0, $count = count($roles); $i < $count; $i++) {
                    if ($roles[$i] === $role) {
                        unset($roles[$i]);
                        $manager->roles = $roles;
                        break;
                    }
                }
            }
        }
        $em->flush($manager);

        return new JsonResponse(array(
            'result' => 'ok'
        ));
    }

    /**
     * @Route("/reset_password")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function resetPasswordAction(Request $request)
    {
        $managerService = $this->get('lychee_admin.service.manager');
        $manager = $managerService->loadManager($request->request->get('manager_id', 0));
        if (null === $manager) {
            throw $this->createNotFoundException('The manager does not exist');
        }
        $newPassword = base64_encode((new SecureRandom())->nextBytes(9));
        $managerService->updateManagerPassword($manager, $newPassword);
        $message = \Swift_Message::newInstance()
            ->setSubject('上帝的看板: Reset Password')
            ->setFrom($this->container->getParameter('mailer_user'))
            ->setTo($manager->email)
            ->setBody(sprintf('You new password is: %s', $newPassword));
        $this->get('mailer')->send($message);

        return new JsonResponse(array(
            'result' => 'ok'
        ));
    }

    /**
     * @Route("/operation_account")
     * @Template
     * @return array
     */
    public function operationAccountAction()
    {
        $managers = $this->get('lychee_admin.service.manager')->listManagers();
        $managerOperationAccountIds = array_reduce($managers, function($result, $item) {
            $accountIds = array();
            foreach ($item->operationAccounts as $row) {
                $accountIds[] = $row->id;
            }
            $result[$item->id] = $accountIds;

            return $result;
        });
        $operationAccountIds = $this->get('lychee_admin.service.operation_accounts')->fetchIds();
        $operationAccountIds = array_reduce($operationAccountIds, function($result, $item) {
            $result[] = $item['id'];
            return $result;
        });
        $operationAccounts = $this->account()->fetch($operationAccountIds);

        return $this->response('运营帐号关联', array(
            'managers' => $managers,
            'operationAccounts' => $operationAccounts,
            'managerOperationAccountIds' => json_encode($managerOperationAccountIds),
        ));
    }

    /**
     * @Route("/modify_binding")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function modifyBindingAction(Request $request)
    {
        $managerId = $request->request->get('manager_id');
        $managerService = $this->get('lychee_admin.service.manager');
        $manager = $managerService->loadManager($managerId);
        if (null === $manager) {
            throw $this->createNotFoundException('Manager Not Found.');
        }
        $accounts = $request->request->get('operation_accounts');
        $operationAccounts = $this->get('lychee_admin.service.operation_accounts')->fetch($accounts);
        $manager->operationAccounts = $operationAccounts;
        $managerService->update($manager);

        return $this->redirect($this->generateUrl('lychee_admin_authorization_operationaccount'));
    }

	/**
	 * @Route("/manager/freeze")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
    public function freezeManager(Request $request) {
    	$id = $request->request->get('id');
	    $manager = $this->manager()->loadManager($id);
	    if ($manager) {
	    	$this->manager()->freezeManager($manager);
	    }

	    return $this->redirect($this->generateUrl('lychee_admin_authorization_index'));
    }

	/**
	 * @Route("/manager/unfreeze")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
    public function unfreezeManager(Request $request) {
	    $id = $request->request->get('id');
	    $manager = $this->manager()->loadManager($id);
	    if ($manager) {
		    $this->manager()->activateManager($manager);
	    }

	    return $this->redirect($this->generateUrl('lychee_admin_authorization_index'));
    }

	/**
	 * @Route("/test_account")
	 * @Template
	 * @return array
	 */
	public function testAccountAction() {
		return $this->response('测试账号', [
			'testPhoneNum' => $this->testPhoneNum,
		]);
	}

	/**
	 * @Route("/reset_test_account")
	 * @Method("POST")
	 * @throws \Exception
	 * @throws \Lychee\Module\Account\Exception\NicknameDuplicateException
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function resetTestAccountAction() {
		$user = $this->account()->fetchOneByPhone('86', $this->testPhoneNum);
		if ($user) {
			$this->account()->updatePhone($user->id, null, null);
		}

		return $this->redirect($this->generateUrl('lychee_admin_authorization_testaccount'));
	}

	/**
	 * @return array
	 * @Route("/accounts")
	 * @Template()
	 */
	public function accountsAction() {
		$operationAccountRepo = $this->getDoctrine()->getRepository('LycheeAdminBundle:OperationAccount');
		$operationAccounts = $operationAccountRepo->findAll();
		$accountIds = array_reduce($operationAccounts, function(&$result = array(), $item) {
			$result[] = $item->id;

			return $result;
		});
		$accounts = $this->account()->fetch($accountIds);

		return $this->response('运营帐号', array(
			'accounts' => $accounts,
		));
	}

	/**
	 * @Route("/add_operation_account")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function addOperationAccountAction(Request $request)
	{
		$accounts = $request->request->get('accounts');
		$memcacheService = $this->get('memcache.default');
		if ($accounts) {
			$accountsInUse = $this->getAccountsInCache();
			$em = $this->getDoctrine()->getManager();
			foreach ($accounts as $accountId) {
				$operationAccount = new OperationAccount();
				$operationAccount->id = $accountId;
				$em->persist($operationAccount);
				$accountsInUse[] = $accountId;
			}
			$em->flush();
			$memcacheService->set($this->memcacheKeyOfAccount, array_unique($accountsInUse));
		}

		return $this->redirect($this->generateUrl('lychee_admin_authorization_accounts'));
	}

	/**
	 * @return array
	 */
	private function getAccountsInCache()
	{
		$accountsInUse = $this->get('memcache.default')->get($this->memcacheKeyOfAccount);
		if ($accountsInUse === false) {
			$accounts = $this->getDoctrine()->getRepository('LycheeAdminBundle:OperationAccount')->findAll();
			$accountsInUse = array();
			foreach ($accounts as $account) {
				$accountsInUse[] = $account->id;
			}
		}

		return $accountsInUse;
	}

	/**
	 * @Route("/searchaccount")
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function searchAccountAction(Request $request)
	{
		$keyword = $request->query->get('keyword');
		$accountsResult = array();
		if ($keyword) {
			$accounts = $this->account()->fetchByKeyword($keyword, 0, 200);
			$accountsInUse = $this->getAccountsInCache();
			foreach ($accounts as $account) {
				if (!in_array($account->id, $accountsInUse)) {
					$accountsResult[] = array(
						'id' => $account->id,
						'nickname' => $account->nickname,
					);
				}
			}
		}

		return new JsonResponse($accountsResult);
	}

	/**
	 * @Route("/operation_account/delete")
	 * @Method("POST")
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function deleteOperationAccountAction(Request $request)
	{
		$memcacheService = $this->get('memcache.default');
		$accountId = $request->request->get('id');
		if ($accountId) {
			$operationAccountRepo = $this->getDoctrine()->getRepository('LycheeAdminBundle:OperationAccount');
			$account = $operationAccountRepo->find($accountId);
			if ($account) {
				$em = $this->getDoctrine()->getManager();
				$em->remove($account);
				$em->flush();

				$accountInUse = $this->getAccountsInCache();
				unset($accountInUse[array_search($account->id, $accountInUse)]);
				$memcacheService->set($this->memcacheKeyOfAccount, array_values($accountInUse));

				return new JsonResponse(array('result' => true));
			}
		}

		return new JsonResponse(array('result' => false));
	}
}
