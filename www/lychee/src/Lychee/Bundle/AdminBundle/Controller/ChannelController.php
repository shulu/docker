<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 29/09/2016
 * Time: 3:00 PM
 */

namespace Lychee\Bundle\AdminBundle\Controller;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Lychee\Module\Recommendation\AppChannelManagement;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;


/**
 * @Route("/channel")
 * Class ChannelController
 * @package Lychee\Bundle\AdminBundle\Controller
 */
class ChannelController extends BaseController {

	public function getTitle() {
		return '渠道管理';
	}

	/**
	 * @Route("/")
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function indexAction() {
		return $this->redirect($this->generateUrl('lychee_admin_channel_appchannelpackage'));
	}

	/**
	 * @Route("/app_channel/package")
	 * @Template
	 * @return array
	 */
	public function appChannelPackage() {
		/** @var AppChannelManagement $appChannelService */
		$appChannelService = $this->get('lychee.module.app_channel');
		return $this->response('推广渠道包', [
			'packages' => $appChannelService->listAllPackages(),
		]);
	}

	/**
	 * @Route("/app_channel/title")
	 * @Template
	 * @return array
	 */
	public function appChannelTitle() {
		/** @var AppChannelManagement $appChannelService */
		$appChannelService = $this->get('lychee.module.app_channel');
		return $this->response('推广标题', [
			'titles' => $appChannelService->listAllTitles(),
		]);
	}

	/**
	 * @Route("/app_channel_title/create")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function createAppChannelTitle(Request $request) {
		$code = $request->request->get('code');
		$title = $request->request->get('title');
		/** @var AppChannelManagement $appChannelService */
		$appChannelService = $this->get('lychee.module.app_channel');
		try {
			$appChannelService->createTitle($code, $title);
		} catch (UniqueConstraintViolationException $e) {
			return $this->redirectErrorPage('代码不能重复.', $request);
		}

		return $this->redirect($this->generateUrl('lychee_admin_channel_appchanneltitle'));
	}

	/**
	 * @Route("/app_channel_title/delete")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function deleteAppChannelTitle(Request $request) {
		$id = $request->request->get('id');
		/** @var AppChannelManagement $appChannelService */
		$appChannelService = $this->get('lychee.module.app_channel');
		$appChannelService->removeTitle($id);

		return $this->redirect($this->generateUrl('lychee_admin_channel_appchanneltitle'));
	}

	/**
	 * @Route("/app_channel/package/create")
	 * @Template
	 * @param Request $request
	 *
	 * @return array
	 */
	public function createAppChannelPackage(Request $request) {
		$code = $request->query->get('code');

		Qiniu_SetKeys($this->getParameter('qiniu_access_key'), $this->getParameter('qiniu_secret_key'));
		$bucket = $this->getParameter('qiniu_bucket');
		$prefix = 'android/';
		$putPolicy = new \Qiniu_RS_PutPolicy($bucket);
		$putPolicy->SaveKey = $prefix . '$(etag)';
		$putPolicy->FsizeLimit = 30 * 1024 * 1024;
//	    $putPolicy->CallbackUrl = 'http://www.ciyo.cn/qiniu/app_channel_package/callback';
//	    $putPolicy->CallbackBody = 'name=$(fname)&hash=$(etag)&code=$(x:code)';
		$token = $putPolicy->Token(null);

		return $this->response('创建/替换渠道包', [
			'code' => $code,
			'token' => $token,
			'domain' => $this->getParameter('qiniu_upload_host'),
			'prefix' => $prefix,
		]);
	}

	/**
	 * @Route("/app_channel/package/docreate")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function doCreateAppChannelPackage(Request $request) {
		$code = $request->request->get('code');
		$link = $request->request->get('link');
		/** @var AppChannelManagement $appChannelService */
		$appChannelService = $this->get('lychee.module.app_channel');
		$appChannelService->createPackage($code, $link);

		return $this->redirect($this->generateUrl('lychee_admin_channel_appchannelpackage'));
	}

	/**
	 * @Route("/app_channel/package/delete")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function deleteAppChannelPackage(Request $request) {
		$packageId = $request->request->get('id');
		/** @var AppChannelManagement $appChannelService */
		$appChannelService = $this->get('lychee.module.app_channel');
		$appChannelService->deletePackage($packageId);

		return $this->redirect($this->generateUrl('lychee_admin_channel_appchannelpackage'));
	}
}