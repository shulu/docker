<?php

namespace Lychee\Bundle\AdminBundle\Controller;

use Lychee\Bundle\AdminBundle\Service\ManagerService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Util\StringUtils;

/**
 * Class PreferenceController
 * @package Lychee\Bundle\AdminBundle\Controller
 * @Route("/preference")
 */
class PreferenceController extends Controller
{
    private function response($subTitle, $data = array())
    {
        return array_merge(array(
            'title' => '个人设置',
            'subTitle' => $subTitle,
        ), $data);
    }

    /**
     * @Route("/")
     * @Template()
     * @return array
     */
    public function indexAction() {
        return $this->response('基本资料', array(
            'user' => $this->getUser(),
        ));
    }

    /**
     * @return array
     * @Route("/password")
     * @Template()
     */
    public function passwordAction() {
        return $this->response('修改密码');
    }

    /**
     * @Route("/changepassword")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @throws \Exception
     */
    public function changePasswordAction(Request $request) {
        $oldPassword = $request->request->get('old_password');
        $newPassword = $request->request->get('new_password');
        $confirmPassword = $request->request->get('confirm_password');
        if (!StringUtils::equals($newPassword, $confirmPassword)) {
            throw new \Exception('Not Equal');
        }
        $managerService = $this->get('lychee_admin.service.manager');
        $manager = $managerService->loadManager($this->getUser()->id);
        if (false === $managerService->comparePassword($manager, $oldPassword)) {
            throw new AccessDeniedException();
        }
        $managerService->updateManagerPassword($manager, $newPassword);

        return $this->redirect($this->generateUrl('lychee_admin_preference_index'));
    }

	/**
	 * @Route("/profile/modify")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
    public function modifyProfile(Request $request) {
	    $nickname = $request->request->get('nickname');
	    $id = $request->request->get('id');
	    /** @var ManagerService $managerService */
	    $managerService = $this->get('lychee_admin.service.manager');
	    $manager = $managerService->loadManager($id);
	    if ($manager) {
	    	$manager->name = $nickname;
		    $managerService->update($manager);
	    }

	    return $this->redirect($this->generateUrl('lychee_admin_preference_index'));
    }
}
