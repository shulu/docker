<?php
/**
 * Created by PhpStorm. 彩图电信合作API接口
 * User: benson
 * Date: 2017/8/22
 * Time: 下午1:39
 */

namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Bundle\ApiBundle\Error\AuthenticationError;
use Lychee\Module\Caitu\Entity\CaituRecord;
use Lychee\Module\ExtraMessage\Entity\EMClientVersion;
use Lychee\Module\ExtraMessage\Entity\EMPromotionCode;
use Lychee\Module\ExtraMessage\Entity\EMUser;
use Lychee\Module\Payment\Entity\PaymentProduct;
use Lychee\Module\Payment\Entity\PaymentProductPurchased;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Lychee\Bundle\ApiBundle\Error\PromotionCodeError;
use Symfony\Component\HttpFoundation\Response;
use Lychee\Module\ExtraMessage\EMAuthenticationService;
use Lychee\Bundle\ApiBundle\Error\ErrorsException;
use Lychee\Module\Payment\PurchaseRecorder;
use Lychee\Bundle\ApiBundle\Error\PaymentError;


class CaiTuController extends Controller {

	/**
	 * @Route("/caitu/status")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="caitu",
	 *   parameters={
	 *     {"name"="phone", "dataType"="string", "required"=true},
	 *     {"name"="state", "dataType"="integer", "required"=true},
	 *     {"name"="ddate", "dataType"="string", "required"=true},
	 *     {"name"="tddate", "dataType"="string", "required"=true},
	 *     {"name"="fee", "dataType"="string", "required"=true},
	 *     {"name"="type", "dataType"="string", "required"=true},
	 *     {"name"="extra", "dataType"="string", "required"=false}
	 *   }
	 * )
	 */
	public function caituAction(Request $request){

		$phone = $this->requireParam($request, 'phone');
		$state = $this->requireParam($request, 'state');
		$ddate = $this->requireParam($request, 'ddate');
		$tdate = $this->requireParam($request, 'tddate');
		$fee = $this->requireParam($request, 'fee');
		$type = $this->requireParam($request, 'type');
		$extra = $request->request->get('extra');

		$this->caituservice()->addRecord($phone, $state, $ddate, $tdate, $extra, $fee, $type);

		return new Response(
			'success'
		);
	}
}