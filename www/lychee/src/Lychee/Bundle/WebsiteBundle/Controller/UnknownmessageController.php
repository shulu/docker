<?php
/**
 * Created by PhpStorm.
 * User: ys160726
 * Date: 2017/1/6
 * Time: 下午4:47
 */

namespace Lychee\Bundle\WebsiteBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class UnknownmessageController extends Controller
{
	/**
 * @Route("/", host="unknownmessage.test")
 * @Route("/", host="rekallstudio.com", name="unknownmessage2")
 * @Route("/", host="192.168.9.90")
 * @Template
 */

	public function indexAction(Request $request) {
		$userAgent = $request->headers->get('USER_AGENT');
		return array(
			'userAgent' => $userAgent
		);
	}

	/**
	 * @Route("/qa", name="qa_page")
	 * @Method("GET")
	 */

	public function qaAction(Request $request) {
		return $this->render('@LycheeWebsite/Unknownmessage/Q&A.html.twig');
	}
}