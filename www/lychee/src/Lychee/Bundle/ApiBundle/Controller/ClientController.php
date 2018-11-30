<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 02/11/2016
 * Time: 3:17 PM
 */

namespace Lychee\Bundle\ApiBundle\Controller;
use Lychee\Module\ContentManagement\Entity\AndroidAutoUpdate;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;


/**
 * Class ClientController
 * @package Lychee\Bundle\ApiBundle\Controller
 */
class ClientController extends Controller {

	/**
	 * @Route("/client/update")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="client",
	 *   parameters={
	 *     {"name"="platform", "dataType"="integer", "required"=true, "description"="平台: Android: 1, ios: 2。"}
	 *   }
	 * )
	 *
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function updateAction(Request $request) {
		$platform = $request->query->get('platform', '1'); // 1: android; 2: ios
		/** @var AndroidAutoUpdate $updateInfo */
		$updateInfo = $this->androidAutoUpdate()->getLatestUpdate();
		if ($updateInfo) {
			return $this->dataResponse([
				'platform' => (int)$platform,
				'downloadUrl' => $updateInfo->link,
				'desc' => $updateInfo->log,
				'version' => $updateInfo->version,
				'versionCode' => $updateInfo->versionCode,
			]);
		}

		return $this->dataResponse(null);
	}

	/**
	 * @Route("/apple-app-site-association")
	 * @return Response
	 */
	public function getAppleAppSiteAssociationAction() {
		$content = file_get_contents(__DIR__ . '/../Resources/apple-app-site-association');
		$response = new Response($content);
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}
}