<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use Lychee\Bundle\ApiBundle\Error\CommonError;
use Lychee\Bundle\ApiBundle\Error\ErrorsException;
use Lychee\Bundle\ApiBundle\Error\PaymentError;
use Lychee\Bundle\CoreBundle\Entity\User;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Foundation\HttpUtility;
use Lychee\Component\Foundation\StringUtility;
use Lychee\Module\ExtraMessage\Entity\EMPaymentComment;
use Lychee\Module\Payment\Entity\PaymentCiyoCoinPurchased;
use Lychee\Module\Payment\Entity\PaymentProduct;
use Lychee\Module\Payment\Entity\PaymentTransaction;
use Lychee\Module\Payment\PayerType;
use Lychee\Module\Payment\PayType;
use Lychee\Module\Payment\ProductManager;
use Lychee\Module\Payment\PurchaseRecorder;
use Lychee\Module\Payment\ThridParty\Alipay\AlipayRequester;
use Lychee\Module\Payment\ThridParty\Qpay\QpayRequester;
use Lychee\Module\Payment\ThridParty\Wechat\WechatRequester;
use Lychee\Module\Payment\TransactionService;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGenerator;

class PaymentController extends Controller {

	/**
	 * @return TransactionService
	 */
    private function transactionService() {
        return $this->get('lychee.module.payment.transaction');
    }

	/**
	 * @param ParameterBag $params
	 *
	 * @return mixed
	 */
    private function requirePayerType(ParameterBag $params) {
    	$payerType = $params->get('payer_type', '1');

	    return $payerType;
    }

	/**
	 * @param Request $request
	 *
	 * @return array
	 */
    private function requirePayer(Request $request) {
    	$method = $request->getMethod();
	    if ($method === 'GET') {
	    	$params = $request->query;
	    } elseif ($method === 'POST') {
	    	$params = $request->request;
	    } else {
	    	$params = $request;
	    }
	    $payerType = $params->get('payer_type', PayerType::DEVICE);
	    if ($payerType == PayerType::USER) {
	    	$user = $this->requireAuth($request);
		    $payer = $user->id;
	    } else if ($payerType == PayerType::EMUSER) {
		    $user = $this->requireEMAuth($request);
		    $payer = $user->id;
	    } else {
	    	$payer = $this->requireDeviceId($params);
	    }

	    return [$payerType, $payer, isset($user)? $user:null];
    }

    /**
     * @param ParameterBag $params
     * @return string
     * @throws ErrorsException
     */
    private function requireDeviceId($params) {
        $deviceId = $this->requireParam($params, 'device_id');
        if (strlen($deviceId) > 64 || strlen($deviceId) == 0) {
            throw new ErrorsException(CommonError::ParameterInvalid('device_id', $deviceId));
        }
        return $deviceId;
    }

    /**
     * @Route("/payment/purchase_records")
     * @Method("POST")
     * @ApiDoc(
     *   section="payment",
     *   parameters={
     *     {"name"="device_id", "dataType"="string", "required"=true}
     *   }
     * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
    public function listPurchaseRecordsAction(Request $request) {
	    list( , $payer) = $this->requirePayer($request);

	    /** @var PurchaseRecorder $recorder */

        $recorder = $this->get('lychee.module.payment.purchase_recorder');
        $records = $recorder->listUserRecords($payer, PaymentProduct::EXTRAMESSAGE_APP_ID);
        $result = array_map(function($item) {
        	return [
        		'product_id' => $item['app_store_id'],
		        'purchase_time' => strtotime($item['purchase_time'])
	        ];
        }, $records);

        return $this->dataResponse($result);
    }

    /**
     * @Route("/payment/alipay/sign_pay")
     * @Method("POST")
     * @ApiDoc(
     *   section="payment",
     *   parameters={
     *     {"name"="payer_type", "dataType"="integer", "required"=false, "description"="付款者类型,设备:1;用户:2;异次元用户:3. 默认值:1"},
     *     {"name"="device_id", "dataType"="string", "required"=false, "description"="当payer_type为1时,必须提交此参数"},
     *     {"name"="access_token", "dataType"="string", "required"=false, "description"="当payer_type为2时,必须提交此参数"},
     *     {"name"="product_id", "dataType"="integer", "required"=true},
     *   }
     * )
     */
    public function alipaySignPayAction(Request $request) {
    	list($payerType, $payer) = $this->requirePayer($request);
    	$alipay = $this->getAlipayRequester($payerType);
	    if (2 == $payerType) {
		    $productId = $this->requireInt($request->request, 'product_id');
		    $product = $this->productManager()->getProductById($productId);
		    if ($product == null) {
			    return $this->errorsResponse(CommonError::ParameterInvalid('product_id', $productId));
		    }
	    } else {
		    $appStoreId = $this->requireParam($request->request, 'product_id');
		    $product = $this->productManager()->getProductByAppStoreId($appStoreId);
		    if (null == $product) {
		    	return $this->errorsResponse(CommonError::ObjectNonExist($appStoreId));
		    }
		    $productId = $product->id;
	    }

        $clientIp = $request->getClientIp();
        $transaction = $this->transactionService()->createTransaction(
            $payerType, $payer, $productId, $product->price, $clientIp, PayType::ALIPAY);

        $notifyUrl = $this->generateUrl('payment_alipay_notify', array(), UrlGenerator::ABSOLUTE_URL);
        $requestParams = $alipay->signPayRequest(
        	$product->name,
	        $product->desc,
	        $transaction->id,
	        $product->price,
	        $transaction->startTime,
	        $transaction->endTime,
	        $notifyUrl
        );
        $this->transactionService()->logTransactionRequest($transaction->id, $requestParams);

        return new Response($requestParams);
    }

    /**
     * @Route("/payment/alipay/query_order")
     * @Method("GET")
     * @ApiDoc(
     *   section="payment",
     *   parameters={
     *     {"name"="payer_type", "dataType"="integer", "required"=false, "description"="付款者类型,设备:1;用户:2;异次元用户:3. 默认值:1"},
     *     {"name"="device_id", "dataType"="string", "required"=false, "description"="当payer_type为1时,必须提交此参数"},
     *     {"name"="access_token", "dataType"="string", "required"=false, "description"="当payer_type为2时,必须提交此参数"},
     *     {"name"="trade_no", "dataType"="string", "required"=true},
     *   }
     * )
	 * @param Request $request
	 *
	 * @return JsonResponse|Response
	 */
    public function alipayQueryTradeAction(Request $request) {
    	list($payerType, $payer) = $this->requirePayer($request);

        $tradeNo = $this->requireParam($request->query, 'trade_no');
        $transactionId = intval($tradeNo);
        if ($transactionId <= 0) {
            return $this->errorsResponse(CommonError::ParameterInvalid('trade_no', $tradeNo));
        }
        $transaction = $this->transactionService()->getTransactionById($transactionId);
        if ($transaction == null || $transaction->payer != $payer) {
            return $this->errorsResponse(CommonError::ParameterInvalid('trade_no', $tradeNo));
        }

	    $alipay = $this->getAlipayRequester($payerType);
        $res = $alipay->queryOrderInfo($transaction->id);
        if ($res == null) {
            return $this->errorsResponse(CommonError::SystemBusy());
        } else {
	        if (
	        	isset($res['trade_status']) &&
	            ('TRADE_SUCCESS' === $res['trade_status'] || 'TRADE_FINISHED' === $res['trade_status'])
	        ) {
		        /**
		         * 必须先判断订单是否已完成，未完成才进行充值操作，最后再log下订单的finish状态
		         */
		        if (false === $this->transactionService()->isTransactionFinished($transaction->id)) {

		        	/*
			        $product = $this->productManager()->getProductById($transaction->productId);
			        if (PayerType::USER == $transaction->payerType) {
				        // 多次查询必须确保只到账一次
				        $user = $this->account()->fetchOne($transaction->payer);
				        $this->rechargeCiyoCoin($user, $product->ciyoCoin);
			        }
		        	*/

			        $product = $this->productManager()->getProductById($transaction->productId);
			        $this->purchaseRecorder()->record($transaction->payer, $transactionId, $transaction->productId, $product->price, $transaction->payType);
			        $this->actionAfterPay($transaction);
			        $this->transactionService()->logTransactionFinish($transaction->id, new \DateTime($res['send_pay_date']), 0);
		        }
	        }
        }
        return new JsonResponse($res);
    }

    /**
     * @Route("/payment/alipay/notify", name="payment_alipay_notify")
     * @Method("POST")
     */
    public function alipayNotifyAction(Request $request) {
        $notifyId = $this->transactionService()->logThridPartyNotify(PayType::ALIPAY, $request->getContent());
        $params = $request->request->all();
        do {
	        if (isset($params['app_id'])) {
		        $appId = $params['app_id'];
		        if ($appId == $this->getParameter('ciyocon_alipay_app_id')) {
		        	$payerType = 2;
		        	$alipay = $this->getAlipayRequester($payerType);
			        break;
		        } elseif ($appId == $this->getParameter('yiciyuan_alipay_app_id')) {
		        	$payerType = 1;
			        $alipay = $this->getAlipayRequester($payerType);
			        break;
		        }
	        }
	        throw $this->createNotFoundException();
        } while(0);

        $valid = $alipay->verifyNotify($params);
        if (!$valid) {
            throw $this->createNotFoundException();
        }

        $tradeNo = $params['out_trade_no'];
        $transactionId = intval($tradeNo);
        if ($transactionId != $tradeNo) {
            throw $this->createNotFoundException();
        }
        $transaction = $this->transactionService()->getTransactionById($transactionId);
        if ($transaction == null || $transaction->totalFee != $params['total_amount']) {
            throw $this->createNotFoundException();
        }

        if ($params['trade_status'] == 'TRADE_FINISHED' || $params['trade_status'] == 'TRADE_SUCCESS') {
	        $this->purchaseRecorder()->record($transaction->payer, $transaction->id, $transaction->productId, $transaction->totalFee, $transaction->payType);

            $payTimeStr = $params['gmt_payment'];
            $payTime = new \DateTime($payTimeStr);

	        if (false === $this->transactionService()->isTransactionFinished($transaction->id)) {
		        $this->actionAfterPay( $transaction );
	        }

	        $this->transactionService()->logTransactionFinish($transaction->id, $payTime, $notifyId);
        }

        return new Response('success');
    }

    /**
     * @Route("/payment/wechat/sign_pay")
     * @Method("POST")
     * @ApiDoc(
     *   section="payment",
     *   parameters={
     *     {"name"="payer_type", "dataType"="integer", "required"=false, "description"="付款者类型,设备:1;用户:2;异次元用户:3. 默认值:1"},
     *     {"name"="device_id", "dataType"="string", "required"=false, "description"="当payer_type为1时,必须提交此参数"},
     *     {"name"="access_token", "dataType"="string", "required"=false, "description"="当payer_type为2时,必须提交此参数"},
     *     {"name"="product_id", "dataType"="integer", "required"=true},
     *   }
     * )
     */
    public function wechatSignPayAction(Request $request) {
    	list($payerType, $payer) = $this->requirePayer($request);

    	if (2 == $payerType) {
		    $productId = $this->requireInt($request->request, 'product_id');
		    $product = $this->productManager()->getProductById($productId);
		    if ($product == null) {
			    return $this->errorsResponse(CommonError::ParameterInvalid('product_id', $productId));
		    }
	    } else {
		    $appStoreId = $this->requireParam($request->request, 'product_id');
		    $product = $this->productManager()->getProductByAppStoreId($appStoreId);
		    if (null == $product) {
			    return $this->errorsResponse(CommonError::ObjectNonExist($appStoreId));
		    }
		    $productId = $product->id;
	    }

        $clientIp = $request->getClientIp();
        $transaction = $this->transactionService()->createTransaction(
            $payerType, $payer, $productId, $product->price, $clientIp, PayType::WECHAT);
	    /** @var WechatRequester $wechat */
        $wechat = $this->getWechatRequester($payerType);
	    $notifyUrl = $this->generateUrl('payment_wechat_notify', array(), UrlGenerator::ABSOLUTE_URL);
        $requestParams = $wechat->signPayRequest($product->name, $product->desc, $transaction->id, $product->price, $transaction->startTime, $transaction->endTime, $notifyUrl, $transaction->clientIp);
        if ($requestParams == null) {
            return $this->errorsResponse(CommonError::SystemBusy());
        }
        $this->transactionService()->logTransactionRequest($transaction->id, http_build_query($requestParams));

        $requestParams['trade_no'] = $transaction->id;
        return new JsonResponse($requestParams);
    }

    /**
     * @Route("/payment/wechat/query_order")
     * @Method("GET")
     * @ApiDoc(
     *   section="payment",
     *   parameters={
     *     {"name"="payer_type", "dataType"="integer", "required"=false, "description"="付款者类型,设备:1;用户:2;异次元用户:3. 默认值:1"},
     *     {"name"="device_id", "dataType"="string", "required"=false, "description"="当payer_type为1时,必须提交此参数"},
     *     {"name"="access_token", "dataType"="string", "required"=false, "description"="当payer_type为2时,必须提交此参数"},
     *     {"name"="trade_no", "dataType"="string", "required"=true},
     *   }
     * )
     */
    public function wechatQueryOrderAction(Request $request) {
	    list($payerType, $payer) = $this->requirePayer($request);

        $tradeNo = $this->requireParam($request->query, 'trade_no');
        $transactionId = intval($tradeNo);
        if ($transactionId <= 0) {
            return $this->errorsResponse(CommonError::ParameterInvalid('trade_no', $tradeNo));
        }
        $transaction = $this->transactionService()->getTransactionById($transactionId);
        if ($transaction == null || $transaction->payer != $payer) {
            return $this->errorsResponse(CommonError::ParameterInvalid('trade_no', $tradeNo));
        }

        /** @var WechatRequester $wechat */
        $wechat = $this->getWechatRequester($payerType);
        $res = $wechat->orderquery($transaction->id);
        if ($res == null) {
            return $this->errorsResponse(CommonError::SystemBusy());
        } else {
	        if (isset($res['trade_state']) && 'SUCCESS' === $res['trade_state']) {
	        	// 查询结果是支付成功，则马上把商品到账
		        if (false === $this->transactionService()->isTransactionFinished($transactionId)) {
			        /**
			         * 必须先判断订单是否已完成，未完成才进行充值操作，最后再log下订单的finish状态
			         */
			        /*
			        $product = $this->productManager()->getProductById($transaction->productId);
		            if (PayerType::USER == $transaction->payerType) {
				        // 多次查询必须确保只到账一次
				        $user = $this->account()->fetchOne($transaction->payer);
				        $this->rechargeCiyoCoin($user, $product->ciyoCoin);
			        }
					*/
			        $product = $this->productManager()->getProductById($transaction->productId);

			        $this->purchaseRecorder()->record($transaction->payer, $transactionId, $transaction->productId, $product->price, $transaction->payType);
			        $this->actionAfterPay($transaction);
			        $this->transactionService()->logTransactionFinish($transactionId, new \DateTime($res['time_end']), 0);
		        }
	        }
        }
        return new JsonResponse($res);
    }

    /**
     * @Route("/payment/wechat/notify", name="payment_wechat_notify")
     * @Method("POST")
	 * @param Request $request
	 *
	 * @return Response
	 */
    public function wechatNotifyAction(Request $request) {
        $body = $request->getContent();
        $notifyId = $this->transactionService()->logThridPartyNotify(PayType::WECHAT, $body);

	    /** @var WechatRequester $wechat */
        $wechat = $this->get('lychee.module.payment.wechat');
        $params = $wechat->toArray($body);
        do {
	        if (is_array($params) && isset($params['appid'])) {
		        $appId = $params['appid'];
		        if ($appId == $this->getParameter('ciyocon_wechat_app_id')) {
		        	$payerType = 2;
		        } else {
		        	$payerType = 1;
		        }
		        $wechat = $this->setWechatAccount($wechat, $payerType);
		        $params = $wechat->verifyNotify($params);
		        if ($params) {
			        break;
		        }
	        }
	        throw $this->createNotFoundException();
        } while (0);

	    $tradeNo = $params['out_trade_no'];
        $transactionId = intval($tradeNo);
        if ($transactionId != $tradeNo) {
            throw $this->createNotFoundException();
        }
        $transaction = $this->transactionService()->getTransactionById($transactionId);
        if ($transaction == null) {
            throw $this->createNotFoundException();
        }

        $payTimeStr = $params['time_end'];
        $payTime = \DateTime::createFromFormat('YmdHis', $payTimeStr);
        $this->purchaseRecorder()->record($transaction->payer, $transaction->id, $transaction->productId, $transaction->totalFee, $transaction->payType);

	    if (false === $this->transactionService()->isTransactionFinished($transaction->id)) {
		    $this->actionAfterPay( $transaction );
	    }
//        if ($transaction->payerType == PayerType::USER) {
//	        /**
//	         * 必须先判断订单是否已完成，未完成才进行充值操作，最后再log下订单的finish状态
//	         */
//	        if (false === $this->transactionService()->isTransactionFinished($transaction->id)) {
//		        $user = $this->account()->fetchOne($transaction->payer);
//		        $product = $this->productManager()->getProductById($transaction->productId);
//		        $this->rechargeCiyoCoin($user, $product->ciyoCoin);
//	        }
//        }

	    $this->transactionService()->logTransactionFinish($transaction->id, $payTime, $notifyId);

        return new Response('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>');
    }

	/**
	 * @Route("/payment/bilibili/sign_pay")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="payment",
	 *   parameters={
	 *     {"name"="payer_type", "dataType"="integer", "required"=false, "description"="付款者类型,设备:1;用户:2;异次元用户:3. 默认值:1"},
	 *     {"name"="device_id", "dataType"="string", "required"=false, "description"="当payer_type为1时,必须提交此参数"},
	 *     {"name"="access_token", "dataType"="string", "required"=false, "description"="当payer_type为2时,必须提交此参数"},
	 *     {"name"="product_id", "dataType"="integer", "required"=true},
	 *   }
	 * )
	 */
	public function bilibiliSignPayAction(Request $request) {
		list($payerType, $payer) = $this->requirePayer($request);

		if (2 == $payerType) {
			$productId = $this->requireInt($request->request, 'product_id');
			$product = $this->productManager()->getProductById($productId);
			if ($product == null) {
				return $this->errorsResponse(CommonError::ParameterInvalid('product_id', $productId));
			}
		} else {
			$appStoreId = $this->requireParam($request->request, 'product_id');
			$product = $this->productManager()->getProductByAppStoreId($appStoreId);
			if (null == $product) {
				return $this->errorsResponse(CommonError::ObjectNonExist($appStoreId));
			}
			$productId = $product->id;
		}

		$clientIp = $request->getClientIp();
		$transaction = $this->transactionService()->createTransaction(
			$payerType, $payer, $productId, $product->price, $clientIp, PayType::BILIBILI);

		$requestParams = 'Bilibili request product '.$transaction->productId;
		$this->transactionService()->logTransactionRequest($transaction->id, $requestParams);
		return new JsonResponse([
			'trade_no' => $transaction->id
		]);
	}

	/**
	 * @param string $accessKey
	 * @param string $uid
	 *
	 * @return array|null
	 */
	private function bilibiliQueryOrder($orderNo, $uid) {
		$params = array(
			'order_no' => $orderNo,
			'uid' => $uid
		);

		$params = $this->buildAndSignBilibiliParams($params);

		$url = 'http://pnew.biligame.net/api/server/query.pay.order';
		$json = HttpUtility::postBilibiliJson($url, $params);
		if ($json !== null && $json['code'] == 0) {
			return $json;
		} else {
			$this->getLogger()->critical('extra message bilibili query order fail.', array(
				'param' => $params));
			return null;
		}
	}

	/**
	 * @Route("/payment/bilibili/query_order")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="payment",
	 *   parameters={
	 *     {"name"="payer_type", "dataType"="integer", "required"=false, "description"="付款者类型,设备:1;用户:2;异次元用户:3. 默认值:1"},
	 *     {"name"="device_id", "dataType"="string", "required"=false, "description"="当payer_type为1时,必须提交此参数"},
	 *     {"name"="access_token", "dataType"="string", "required"=false, "description"="当payer_type为2时,必须提交此参数"},
	 *     {"name"="trade_no", "dataType"="string", "required"=true},
	 *   }
	 * )
	 */
	public function bilibiliQueryOrderAction(Request $request) {
		list($payerType, $payer) = $this->requirePayer($request);

		$tradeNo = $this->requireParam($request->query, 'trade_no');
		$uid = $this->requireParam($request->query, 'uid');
		$transactionId = intval($tradeNo);
		if ($transactionId <= 0) {
			return $this->errorsResponse(CommonError::ParameterInvalid('trade_no', $tradeNo));
		}
		$transaction = $this->transactionService()->getTransactionById($transactionId);
		if ($transaction == null || $transaction->payer != $payer) {
			return $this->errorsResponse(CommonError::ParameterInvalid('trade_no', $tradeNo));
		}

		$res = $this->bilibiliQueryOrder($tradeNo, $uid);

		if ($res == null) {
			return $this->errorsResponse(CommonError::SystemBusy());
		} else {
			if (isset($res['order_state']) && 1 == $res['order_state']) {
				// 查询结果是支付成功，则马上把商品到账
				if (false === $this->transactionService()->isTransactionFinished($transactionId)) {
					/**
					 * 必须先判断订单是否已完成，未完成才进行充值操作，最后再log下订单的finish状态
					 */
					$product = $this->productManager()->getProductById($transaction->productId);

					$this->purchaseRecorder()->record($transaction->payer, $transactionId, $transaction->productId, $product->price, $transaction->payType);
					$this->actionAfterPay($transaction);
					$this->transactionService()->logTransactionFinish($transactionId, new \DateTime($res['time_end']), 0);
				}
			}
		}
		return new JsonResponse($res);
	}

	/**
	 * @Route("/payment/bilibili/notify", name="payment_bilibili_notify")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return Response
	 */
	public function bilibiliNotifyAction(Request $request) {
		$body = $request->request->get('data');
		$notifyId = $this->transactionService()->logThridPartyNotify(PayType::BILIBILI, $body);

		$this->getLogger()->error('Bilibili payment notify ', array(
			'body' => $body
		));

		$params = json_decode($body, true);

		$this->verifyBilibiliSignature($params);

		$tradeNo = $params['out_trade_no'];
		$status = $params['order_status'];
		$fee = $params['pay_money'];

		if(intval($status) == 1) {

			$transactionId = intval( $tradeNo );
			if ( $transactionId != $tradeNo ) {
				throw $this->createNotFoundException();
			}

			$transaction = $this->transactionService()->getTransactionById( $transactionId );
			if ( $transaction == null ) {
				throw $this->createNotFoundException();
			}

			if($fee == ($transaction->totalFee * 100)) {

				$payTimestamp = $params['pay_time'];
				$payTime = (new \DateTime())->setTimestamp($payTimestamp);
				$this->purchaseRecorder()->record($transaction->payer, $transaction->id, $transaction->productId, $transaction->totalFee, $transaction->payType );

				if ( false === $this->transactionService()->isTransactionFinished( $transaction->id ) ) {
					$this->actionAfterPay( $transaction );
				}

				$this->transactionService()->logTransactionFinish( $transaction->id, $payTime, $notifyId );
			}
		}

		return new Response('success');
	}

	/**
	 * @Route("/payment/dmzj/sign_pay")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="payment",
	 *   parameters={
	 *     {"name"="payer_type", "dataType"="integer", "required"=false, "description"="付款者类型,设备:1;用户:2;异次元用户:3. 默认值:1"},
	 *     {"name"="device_id", "dataType"="string", "required"=false, "description"="当payer_type为1时,必须提交此参数"},
	 *     {"name"="access_token", "dataType"="string", "required"=false, "description"="当payer_type为2时,必须提交此参数"},
	 *     {"name"="product_id", "dataType"="integer", "required"=true},
	 *   }
	 * )
	 */
	public function dmzjSignPayAction(Request $request) {
		list($payerType, $payer) = $this->requirePayer($request);

		if (2 == $payerType) {
			$productId = $this->requireInt($request->request, 'product_id');
			$product = $this->productManager()->getProductById($productId);
			if ($product == null) {
				return $this->errorsResponse(CommonError::ParameterInvalid('product_id', $productId));
			}
		} else {
			$appStoreId = $this->requireParam($request->request, 'product_id');
			$product = $this->productManager()->getProductByAppStoreId($appStoreId);
			if (null == $product) {
				return $this->errorsResponse(CommonError::ObjectNonExist($appStoreId));
			}
			$productId = $product->id;
		}

		$clientIp = $request->getClientIp();
		$transaction = $this->transactionService()->createTransaction(
			$payerType, $payer, $productId, $product->price, $clientIp, PayType::DMZJ);

		$requestParams = 'Dmzj request product '.$transaction->productId;
		$this->transactionService()->logTransactionRequest($transaction->id, $requestParams);
		return new JsonResponse([
			'trade_no' => $transaction->id,
			'product' => $product->name
		]);
	}


	/**
	 * @param string $orderNo
	 *
	 * @return array|null
	 */
	private function dmzjQueryOrder($orderNo) {

		$secretKey = $this->getParameter('dmzj_merchant_key');
		$params = array(
			'merc_id' => $this->getParameter('dmzj_merchant_id'),
			'order_id' => $orderNo,
			'time' => time()
		);

		$sign = $this->requestDmzjSignature($params, $secretKey);
		$params['sign'] = $sign;

		$url = 'http://fee.uebilling.com:23000/sdkfee/order/query';
		$json = HttpUtility::postJson($url, $params);
		if ($json !== null && $json['status'] == 0) {
			return $json;
		} else {
			$this->getLogger()->critical('extra message dmzj query order fail.', array( 'param' => $params));
			return null;
		}
	}

	/**
	 * @Route("/payment/dmzj/query_order")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="payment",
	 *   parameters={
	 *     {"name"="payer_type", "dataType"="integer", "required"=false, "description"="付款者类型,设备:1;用户:2;异次元用户:3. 默认值:1"},
	 *     {"name"="device_id", "dataType"="string", "required"=false, "description"="当payer_type为1时,必须提交此参数"},
	 *     {"name"="access_token", "dataType"="string", "required"=false, "description"="当payer_type为2时,必须提交此参数"},
	 *     {"name"="trade_no", "dataType"="string", "required"=true},
	 *   }
	 * )
	 */
	public function dmzjQueryOrderAction(Request $request) {

		list($payerType, $payer) = $this->requirePayer($request);

		$tradeNo = $this->requireParam($request->query, 'trade_no');

		$record = $this->purchaseRecorder()->getRecordByTransactionId($tradeNo, PayType::DMZJ);
		if (!$record || $record->payer != $payer) {
			return $this->failureResponse();
		}
		return $this->sucessResponse();


//		$transactionId = intval($tradeNo);
//		if ($transactionId <= 0) {
//			return $this->errorsResponse(CommonError::ParameterInvalid('trade_no', $tradeNo));
//		}
//		$transaction = $this->transactionService()->getTransactionById($transactionId);
//		if ($transaction == null || $transaction->payer != $payer) {
//			return $this->errorsResponse(CommonError::ParameterInvalid('trade_no', $tradeNo));
//		}
//
//		$response = $this->dmzjQueryOrder($tradeNo);
//
//		if ($response == null) {
//			return $this->errorsResponse(CommonError::SystemBusy());
//		}
//
//		$res = $response['res'];
//		if (isset($res['status']) && 1 == $res['status']) {
//			// 查询结果是支付成功，则马上把商品到账
//			if (false === $this->transactionService()->isTransactionFinished($transactionId)) {
//				/**
//				 * 必须先判断订单是否已完成，未完成才进行充值操作，最后再log下订单的finish状态
//				 */
//				$product = $this->productManager()->getProductById($transaction->productId);
//
//				$this->purchaseRecorder()->record($transaction->payer, $transactionId,
//					$transaction->productId, $product->price, $transaction->payType);
//				$this->actionAfterPay($transaction);
//				$this->transactionService()->logTransactionFinish($transactionId, new \DateTime($res['finishTime']), 0);
//			}
//		}
//		return new JsonResponse($response);
	}

	/**
	 * @Route("/payment/dmzj/notify", name="payment_dmzj_notify")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return Response
	 */
	public function dmzjNotifyAction(Request $request) {

		$body = $request->request->all();
		$this->getLogger()->error('Dmzj payment notify ', array( 'body' => $body ));
		$notifyId = $this->transactionService()->logThridPartyNotify(PayType::DMZJ, http_build_query($body));

		$this->verifyDmzjSignature($body);

		$tradeNo = $request->request->get('app_orderid');
		$status = $request->request->get('status');
		$recAmount = $request->request->get('rec_amount');
		$payAmount = $request->request->get('pay_amount');
		$payTimeStr = $request->request->get('pay_time');


		if(intval($status) == 1) {

			$transactionId = intval( $tradeNo );
			if ( $transactionId != $tradeNo ) {
				throw $this->createNotFoundException();
			}

			$transaction = $this->transactionService()->getTransactionById( $transactionId );
			if ( $transaction == null ) {

				throw $this->createNotFoundException();
			}

			if($recAmount == ($transaction->totalFee * 100)) {

				$payTime = new \DateTime($payTimeStr);
				$this->purchaseRecorder()->record($transaction->payer, $transaction->id, $transaction->productId, $transaction->totalFee, $transaction->payType );

				if ( false === $this->transactionService()->isTransactionFinished( $transaction->id ) ) {
					$this->actionAfterPay( $transaction );
				}

				$this->transactionService()->logTransactionFinish( $transaction->id, $payTime, $notifyId );
			}
		}

		return new Response('success');
	}


    /**
     * @Route("/payment/google/sign_pay")
     * @Method("POST")
     * @ApiDoc(
     *   section="payment",
     *   parameters={
     *     {"name"="payer_type", "dataType"="integer", "required"=false, "description"="付款者类型,设备:1;用户:2;异次元用户:3. 默认值:1"},
     *     {"name"="device_id", "dataType"="string", "required"=false, "description"="当payer_type为1时,必须提交此参数"},
     *     {"name"="access_token", "dataType"="string", "required"=false, "description"="当payer_type为2时,必须提交此参数"},
     *     {"name"="product_id", "dataType"="integer", "required"=true},
     *   }
     * )
     */
    public function googleSignPayAction(Request $request)
    {
        $user = $this->requireEMAuth($request);

	    $appStoreId = $this->requireParam($request->request, 'product_id');
	    $product = $this->productManager()->getProductByAppStoreId($appStoreId);
	    if (null == $product) {
		    return $this->errorsResponse(CommonError::ObjectNonExist($appStoreId));
	    }
	    $productId = $product->id;

        $clientIp = $request->getClientIp();
        $transaction = $this->transactionService()->createTransaction(
            PayerType::EMUSER, $user->id, $productId, $product->price, $clientIp, PayType::GOOGLE);

        $requestParams = 'Google request product '.$transaction->productId;
        $this->transactionService()->logTransactionRequest($transaction->id, $requestParams);
        return new JsonResponse([
            'trade_no' => $transaction->id,
            'product' => $product->name
        ]);
	}

    /**
     * @Route("/payment/google/validate")
     * @Method("POST")
     * @ApiDoc(
     *   section="payment",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false, "description"="当payer_type为2时,必须提交此参数"},
     *     {"name"="inapp_purchase_data", "dataType"="string", "required"=true, "description"=""},
     *     {"name"="inapp_data_signature", "dataType"="string", "required"=true, "description"=""},
     *     {"name"="trade_no", "dataType"="integer", "required"=true},
     *   }
     * )
     */
    public function googlePayValidateAction(Request $request)
    {
	    $user = $this->requireEMAuth($request);
	    $env = $request->request->get('env', 'Production');

	    $body = $request->request->all();
	    if (isset($body['access_token'])) {
	    	unset($body['access_token']);
	    }

        $inappPurchaseData = $this->requireParam($request, 'inapp_purchase_data');
        $inappDataSignature = $this->requireParam($request, 'inapp_data_signature');
        $tradeNo = $this->requireParam($request, 'trade_no');
        $googlePublicKey = $this->getParameter('google_public_key');
        $publicKey = "-----BEGIN PUBLIC KEY-----\n"
                      . chunk_split($googlePublicKey, 64, "\n")
                      . "-----END PUBLIC KEY-----";

        $publicKeyHandle = openssl_get_publickey($publicKey);

        $result = openssl_verify($inappPurchaseData, base64_decode($inappDataSignature), $publicKeyHandle, OPENSSL_ALGO_SHA1);

        if ($result !== 1) {
            return $this->failureResponse();
        }


	    $data = json_decode($inappPurchaseData, true);
	    if (json_last_error() !== JSON_ERROR_NONE) {
		    return $this->failureResponse();
	    }

	    //判断订单号，订单情况，自行解决
	    if ($data['developerPayload'] != $tradeNo) {
		    return $this->failureResponse();
	    }

	    //判断订单完成情况
	    if ($data['purchaseState'] != 0) {
		    return $this->failureResponse();
	    }

	    $transactionId = intval( $tradeNo );
	    if ( $transactionId != $tradeNo ) {
		    throw $this->createNotFoundException();
	    }

	    $transaction = $this->transactionService()->getTransactionById( $transactionId );
	    if ( $transaction == null || $transaction->payer != $user->id) {
		    throw $this->createNotFoundException();
	    }

	    if ($this->transactionService()->isTransactionFinished( $transaction->id )) {
		    return $this->failureResponse();
	    }

	    $this->getLogger()->error('Google payment validate ', array( 'body' => $body ));
	    $notifyId = $this->transactionService()->logThridPartyNotify(PayType::GOOGLE, http_build_query($body));

	    $orderId = $data['orderId'];
	    $purchaseTime = intval($data['purchaseTime'] / 1000);
	    $payTime = new \DateTime();
	    $payTime->setTimestamp($purchaseTime);

	    $this->purchaseRecorder()->record($transaction->payer, $transaction->id, $transaction->productId,
		    $transaction->totalFee, $transaction->payType, $orderId, $payTime );

	    if ( false === $this->transactionService()->isTransactionFinished( $transaction->id ) ) {
		    $this->actionAfterPay( $transaction );
	    }

	    $this->transactionService()->logTransactionFinish( $transaction->id, $payTime, $notifyId );

        return $this->sucessResponse();
	}

	/**
	 * @Route("/payment/app_store/validate_nonconsume_receipt")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="payment",
	 *   description="验证AppStore不可消耗产品的收据信息，验证成功后添加购买记录",
	 *   parameters={
	 *     {"name"="payer_type", "dataType"="integer", "required"=true, "description"="付款者类型,设备:1  用户:2  异次元用户:3  默认值:1"},
	 *     {"name"="access_token", "dataType"="string", "required"=true, "description"="access_token"},
	 *     {"name"="receipt", "dataType"="string", "required"=true, "description"="收据文件，需要base64编码后上传"}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse|Response
	 */
	public function validateNonConsumeAppStoreReceipt(Request $request) {

		list($payerType, $payer, $user) = $this->requirePayer($request);
		$receipt = $this->requireParam($request->request, 'receipt');

		/** @var PurchaseRecorder $purchaseRecorder */
		$purchaseRecorder = $this->get('lychee.module.payment.purchase_recorder');

		$responseArr = $this->sendValidateReceiptRequest($receipt);

		if(!is_array($responseArr)){
			return $this->errorsResponse($responseArr);
		}

		if (isset($responseArr['receipt']['in_app'])) {
			$inApp = $responseArr['receipt']['in_app'];
			if (is_array($inApp) && !empty($inApp)) {

				$env = $responseArr['environment'];
				/** @var LoggerInterface $logger */
				$logger = $this->get('logger');
				foreach ($inApp as $inAppReceipt){

					if (isset($inAppReceipt['transaction_id']) && isset($inAppReceipt['product_id'])) {

						$receiptTransactionId = $inAppReceipt['transaction_id'];
						$receiptProductId = $inAppReceipt['product_id'];

						$existRecord = $purchaseRecorder->getRecordByAppStoreTransactionId($receiptTransactionId);

						if(!isset($existRecord)){

							$purchaseDateStr = $inAppReceipt['original_purchase_date'];
							$purchaseDate = new \DateTime($purchaseDateStr);
							$purchaseDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));

							if (StringUtility::endsWith($receiptProductId, '_ios')) {
								$product = $this->productManager()->getProductByAppStoreId(substr($receiptProductId, 0, -4));
							}
							else {
								$product = $this->productManager()->getProductByAppStoreId($receiptProductId);
							}

							if ($product) {

								$price = $product->price;

								$purchaseRecorder->record($payer, 0, $product->id, $price, '', $receiptTransactionId, $purchaseDate);
                                $appStoreReceipt = $purchaseRecorder->recordAppStoreReceipt(
                                	$payer,
	                                $receiptTransactionId,
	                                $receiptProductId,
	                                $purchaseDateStr,
	                                base64_encode(json_encode($inAppReceipt)),
	                                $product->price,
	                                $env,
	                                true
                                );
                                if (!$appStoreReceipt->valid) {
                                	$appStoreReceipt->env = $env;
                                	$appStoreReceipt->valid = true;
                                	$appStoreReceipt->time = $purchaseDate;
                                	$purchaseRecorder->updateAppStoreReceipt($appStoreReceipt);
                                }


							}else {


								$logger->info("App Store In App Purchase: Product Not Found for product Id : ".$receiptProductId);

								//return $this->errorsResponse(PaymentError::ProductNotFound($receiptProductId));
							}

							/*
							if ($payerType == PayerType::EMUSER) {

								$config = $this->extraMessageService()->getPromotionConfig($product->id);
								$totalFee = $this->purchaseRecorder()->getSumTotalFeeRecord( $payer, $config->storyId);
								$this->extraMessageService()->updatePaymentRanking( $payer, $config->storyId, $totalFee );
							}
							*/
						}
					}
				}

				return $this->sucessResponse();
			}
		}

		return $this->errorsResponse(PaymentError::ReceiptNotAuthenticated());
	}

    /**
     * @Route("/payment/app_store/upload_receipt")
     * @Method("POST")
     * @ApiDoc(
     *   section="payment",
     *   parameters={
     *     {"name"="payer_type", "dataType"="integer", "required"=false, "description"="付款者类型,设备:1;用户:2;异次元用户:3. 默认值:1"},
     *     {"name"="device_id", "dataType"="string", "required"=false, "description"="当payer_type为1时,必须提交此参数"},
     *     {"name"="access_token", "dataType"="string", "required"=false, "description"="当payer_type为2时,必须提交此参数"},
     *     {"name"="transaction_id", "dataType"="string", "required"=true},
     *     {"name"="product_id", "dataType"="string", "required"=true},
     *     {"name"="time", "dataType"="string", "required"=false},
     *     {"name"="receipt", "dataType"="string", "required"=true},
     *     {"name"="env", "dataType"="string", "required"=false},
     *   }
     * )
	 * @param Request $request
	 *
	 * @return JsonResponse|Response
	 */
    public function uploadAppStoreReceipt(Request $request) {
    	list($payerType , $payer, $user) = $this->requirePayer($request);
        $transactionId = $this->requireParam($request->request, 'transaction_id');
        $appStoreProductId = $this->requireParam($request->request, 'product_id');
        $time = $request->request->get('time');
        $receipt = $this->requireParam($request->request, 'receipt');
	    $env = $request->request->get('env');
	    $needRecordPurchase = $request->request->get('need_record_purchase');

	    if(empty($needRecordPurchase)){
	    	$needRecordPurchase = 0;
	    }

        $this->getLogger()->info('product id: '. $appStoreProductId);
        if (StringUtility::endsWith($appStoreProductId, '_ios')) {
            /** @var PaymentProduct|null $product */
            $product = $this->productManager()->getProductByAppStoreId(substr($appStoreProductId, 0, -4));
        }
        else {
            /** @var PaymentProduct|null $product */
            $product = $this->productManager()->getProductByAppStoreId($appStoreProductId);
        }
        if ($product) {
            $productId = $product->id;
            $price = $product->price;
        } else {
            $productId = 0;
            $price = 0;
        }

	    /** @var PurchaseRecorder $purchaseRecorder */
	    $purchaseRecorder = $this->get('lychee.module.payment.purchase_recorder');
	    if ($payerType == PayerType::DEVICE && $needRecordPurchase == 0) {
		    /**
		     * *********************************************
		     * 异次元通讯 iOS 1&2
		     * *********************************************
		     */
		    $purchaseRecorder->recordAppStoreReceipt($payer, $transactionId, $appStoreProductId, $time, $receipt, $price, $env, true);
		    return $this->sucessResponse();

	    } else {
		    /**
		     * *********************************************
		     * 次元币充值
		     * *********************************************
		     */

		    $appStoreReceipt = $purchaseRecorder->recordAppStoreReceipt(
		    	$payer,
			    $transactionId,
			    $appStoreProductId,
			    $time? $time : date('Y-m-d H:i:s'),
			    $receipt,
			    $price,
			    ''
		    );

		    if (!$appStoreReceipt->valid) {
			    $verifiedReceiptResult = $this->receiptValidate($transactionId, $appStoreProductId, $receipt);
//			    var_dump($verifiedReceiptResult);exit;
			    if (is_array($verifiedReceiptResult)) {
				    list($createDate, $env) = $verifiedReceiptResult;
				    $purchaseRecorder->makeAppStoreReceiptValid($appStoreReceipt, $productId, $price, $createDate, $env);

				    if($payerType == PayerType::EMUSER || $payerType == PayerType::DEVICE) {

					    /* iOS异次元通讯投喂后不生成兑换码
					    if ($productId == 40006) {

					        //异次元通讯第三章购买兑换码
					        $existRecord = $this->extraMessageService()->getPromotionCodeAppStoreTransationId( $transactionId );

					        if ( isset( $existRecord ) ) {
						        return $this->errorsResponse( PaymentError::TransactionAlreadyFinished() );
					        }

						    $this->extraMessageService()->generatePromotionCode( $user->id, $productId, null, $transactionId );
				        }
				    	*/


					    if ($payerType == PayerType::EMUSER) {

						    $config = $this->extraMessageService()->getPromotionConfig($product->id);
						    $totalFee = $this->purchaseRecorder()->getSumTotalFeeRecord( $payer, $config->storyId);
						    $this->extraMessageService()->updatePaymentRanking( $payer, $config->storyId, $totalFee );
				        }

					    return $this->sucessResponse();

				    } else {

				    	//次元社充值次元币
					    $this->rechargeCiyoCoin($user, $product->ciyoCoin);

					    return $this->dataResponse([
						    'ciyo_coin' => $user->ciyoCoin,
					    ]);
				    }

			    } else {
				    return $this->errorsResponse($verifiedReceiptResult);
			    }
		    } else {
			    return $this->errorsResponse(PaymentError::ReceiptAlreadyVerified());
		    }
	    }
    }

    private function sendValidateReceiptRequest($receipt, $env = 'production'){

	    /** @var Logger $logger */
	    $logger = $this->get('logger');
	    $logger->info("Send data : ".$env);

	    if ($env == 'sandbox') {
		    $url = 'https://sandbox.itunes.apple.com/verifyReceipt';
	    } else {
		    $url = 'https://buy.itunes.apple.com/verifyReceipt';
	    }


	    $ch = curl_init($url);
	    $dataJson = json_encode([
		    'receipt-data' => $receipt
	    ]);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($dataJson)));
	    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $dataJson);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $response  = curl_exec($ch);

	    $logger->info("Receipt Data: ".$dataJson);

	    if (false === $response) {
		    $errMsg = curl_error($ch);
		    $logger->error(sprintf("Send validation request Error: %s", $errMsg ));
		    return CommonError::ServiceUnavailable();
	    }
	    curl_close($ch);
	    $logger->info(sprintf("Send validation request Response Raw: %s", $response));
	    $responseArr = json_decode($response, true);

	    if (!is_array($responseArr) || empty($responseArr)) {
		    $logger->error(sprintf("Send validation Unable to Parse Response: %s", $response));
		    return CommonError::SystemError();
	    }

	    if ($responseArr['status'] == 0) {

		    return $responseArr;

	    } else {
		    switch ($responseArr['status']) {
			    case '21000':
				    $error = PaymentError::AppStoreCouldNotReadJson();
				    $logger->error(sprintf("[{$error->getMessage()}]: %s", $dataJson));
				    break;
			    case '21002':
				    $error = PaymentError::ReceiptDataWasMalformedOrMissing();
				    $logger->error(sprintf("[{$error->getMessage()}]: %s", $receipt));
				    break;
			    case '21003':
				    $error = PaymentError::ReceiptNotAuthenticated();
				    $logger->info($error->getMessage());
				    break;
			    case '21004':
				    $error = PaymentError::SharedSecretNotMatch();
				    $logger->error($error->getMessage());
				    break;
			    case '21005':
				    $error = PaymentError::ReceiptServerNotCurrentlyAvailable();
				    $logger->error($error->getMessage());
				    break;
			    case '21006':
				    $error = PaymentError::SubscriptionHasExpired();
				    $logger->error($error->getMessage());
				    break;
			    case '21007':
				    return $this->sendValidateReceiptRequest($receipt, 'sandbox');
				    break;
			    case '21008':
				    $error = PaymentError::ReceiptIsFromTheProdEnv();
				    $logger->info($error->getMessage());
				    break;
			    default:
				    $logger->error(sprintf("Unknown AppStore Receipt Response Status Code: %s", $responseArr['status']));
				    $error = CommonError::UnknownError($responseArr['status']);
				    break;
		    }
		    return $error;
	    }
    }

    /**
     * @param $transactionId
     * @param $productId
     * @param $receipt
     * @return \Lychee\Bundle\ApiBundle\Error\Error|array
     */
    private function receiptValidate($transactionId, $productId, $receipt) {

	    $logger = $this->get('logger');
    	$responseArr = $this->sendValidateReceiptRequest($receipt);

    	//发生错误
    	if(!is_array($responseArr)){
			 return $responseArr;
	    }

	    $env = $responseArr['environment'];
	    $createDate = $responseArr['receipt']['receipt_creation_date'];
	    if (isset($responseArr['receipt']['in_app'])) {
		    $inApp = $responseArr['receipt']['in_app'];
		    if (is_array($inApp) && !empty($inApp)) {

			    foreach ($inApp as $inAppReceipt){

				    if (isset($inAppReceipt['transaction_id'])) {
					    $receiptTransactionId = $inAppReceipt['transaction_id'];
					    $receiptProductId = $inAppReceipt['product_id'];
					    $logger->info(sprintf(
						    "TransactionId Param: %s\tTransactionId in receipt: %s\n",
						    $transactionId,
						    $receiptTransactionId
					    ));

					    if ($receiptTransactionId == $transactionId && $receiptProductId == $productId) {
						    return [$createDate, $env];
					    }
				    }
			    }
		    }
	    }

	    return PaymentError::ReceiptNotAuthenticated();
    }

	/**
	 * @Route("/payment/products/ciyo_coin")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="payment",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true, "description"=""},
	 *     {"name"="client", "dataType"="string", "required"=false}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse|Response
	 */
    public function listCiyoCoinProducts(Request $request) {
	    $this->requireAuth($request);
	    $client = $request->query->get(self::CLIENT_PLATFORM_KEY, 'android');
	    /** @var ProductManager $productManager */
	    $productManager = $this->get('lychee.module.payment.product_manager');
	    $products = $productManager->listCiyoCoinProducts($client);

	    return $this->dataResponse($products);
    }

	/**
	 * @param User $user
	 * @param $coin
	 */
    private function rechargeCiyoCoin(User $user, $coin) {
    	$user->ciyoCoin += $coin;
	    $this->getDoctrine()->getManager()->flush($user);
    }

    private function actionAfterPay(PaymentTransaction $transaction){

	    if ($transaction->payerType == PayerType::USER) {

		    $user = $this->account()->fetchOne($transaction->payer);
		    $product = $this->productManager()->getProductById($transaction->productId);
		    $this->rechargeCiyoCoin( $user, $product->ciyoCoin );

	    }else if($transaction->payerType == PayerType::EMUSER){

	    	/*
		    //只有异次元通讯第三章才产生兑换码
		    if ($transaction->productId == 40006) {

			    $user    = $this->emAuthenticationService()->fetchAccount( $transaction->payer );
			    $product = $this->productManager()->getProductById( $transaction->productId );
			    $this->generatePromotionCode( $user, $product->id, $transaction->id);
		    }
			*/

		    $config = $this->extraMessageService()->getPromotionConfig($transaction->productId);

		    //$logger = $this->get('monolog.logger.thirdparty_invoke');
		    //$logger->info('[Promotion Code Config]: ' . $config->codeCount. ' product id : '. $transaction->productId);

	    	//根据配置来生成兑换码
		    if ($config->codeCount > 0) {
			    $user    = $this->emAuthenticationService()->fetchAccount( $transaction->payer );
			    $product = $this->productManager()->getProductById( $transaction->productId );

			    //$logger->info('[Start To Generate Promotion Code]: ' . $product->id);
			    $this->extraMessageService()->deletePromotionCode($transaction->id);
			    for($i=0;$i<$config->codeCount;$i++) {
				    $this->generatePromotionCode( $user, $product->id, $transaction->id );
			    }
		    }

//		    $totalFee = $this->purchaseRecorder()->getSumTotalFeeRecord($transaction->payer, $config->storyId);
		    // TODO:: 需要重新处理，避免重复计算Ranking 的 totalFee
		    $totalFee = $transaction->totalFee;
		    $ranking = $this->extraMessageService()->getUserPaymentRanking($transaction->payer, $config->storyId);
		    if ($ranking) {
		    	$totalFee += $ranking->totalFee;
		    }
		    $this->extraMessageService()->updatePaymentRanking($transaction->payer, $config->storyId, $totalFee );

	    }
    }

	/**
	 * @param User $user
	 * @param $productId
	 * @param $transactionId
	 */
    private function generatePromotionCode($user, $productId, $transactionId){
	    //异次元通讯购买兑换码
	    //$existRecord = $this->extraMessageService()->getPromotionCodeTransationId($transactionId);

	    //if(!isset($existRecord)) {
	        //$this->extraMessageService()->generatePromotionCode( $user->id, $productId, $transactionId, null);
	    //}

	    $this->extraMessageService()->generatePromotionCode( $user->id, $productId, $transactionId, null);
    }

	/**
	 * @Route("/payment/recharge_records")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="payment",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true},
	 *     {"name"="cursor", "dataType"="integer", "required"=false},
	 *     {"name"="count", "dataType"="integer", "required"=false, "description"="每次返回的数据，默认20，最多不超过50"}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function listRechargeRecordsAction(Request $request) {
    	$user = $this->requireAuth($request);
	    list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);
	    /** @var PurchaseRecorder $recorder */
	    $recorder = $this->get('lychee.module.payment.purchase_recorder');
	    $records = $recorder->listSuccessfulPurchasedRecords($user->id, $cursor, $count, $nextCursor);
		$result = array_reduce($records, function($result, $item) {
			$result[] = [
				'id' => $item['id'],
				'ciyo_coin' => $item['ciyo_coin'],
				'total_fee' => $item['total_fee'],
				'pay_time' => strtotime($item['pay_time']),
			];

			return $result;
		});
		null == $result && $result = [];

	    return $this->arrayResponse('records', $result, $nextCursor);
    }

	/**
	 * @Route("/payment/inke_transaction")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="payment",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true},
	 *     {"name"="order_id", "dataType"="string", "required"=true},
	 *     {"name"="inke_currency", "dataType"="string", "required"=true},
	 *     {"name"="nonce", "dataType"="string", "required"=true},
	 *     {"name"="signature", "dataType"="string", "required"=true}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse|Response
	 */
	public function createInkeTransactionAction(Request $request) {
		$sigKey = $this->getParameter('request_signature_key');
		$this->verifySignature($request->request, $sigKey);

		$user = $this->requireAuth($request);
		$orderId = $this->requireParam($request->request, 'order_id');
		$inkeCurrency = $this->requireParam($request->request, 'inke_currency');

		$transaction = $this->ciyoCoinPurchaseRecorder()->fetchTransactionByOutTradeNo($orderId);
		if ($transaction) {
			return $this->errorsResponse(PaymentError::TransactionAlreadyExisted());
		}

		if ($this->container()->getParameter('kernel.environment') == 'prod') {
			$url = 'http://open.inke.com/sdk/query';
		} else {
			$url = 'http://debug.open.ingkee.com/sdk/query';
		}
		$nonce = $this->genNonceStr();
		$appId = $this->getParameter('inke_app_id');
		$appKey = $this->getParameter('inke_app_key');
		$client = new Client();
		$signature = md5(md5($orderId . $nonce) . $appId . $appKey . $nonce);
		$response = $client->request('POST', $url, [
			'form_params' => [
				'orderId' => $orderId,
				'appid' => $appId,
				'nonce_str' => $nonce,
				'sign' => $signature,
			]
		]);
		if (200 == $response->getStatusCode()) {
			$data = $response->getBody();
			$logger = $this->get('monolog.logger.thirdparty_invoke');
			$logger->info('[Inke]: ' . $data);
			$data = json_decode($data, true);
			if ($data && isset($data['error'])) {
				$errInfo = $data['error'];
				if ($errInfo['errno'] == 0) {
					$dataInfo = $data['data'];
					// 人民币
					$totalFee = $dataInfo['price'];
					$ciyoTotalFee = $totalFee * 10;
					// 操作成功
					$transaction = $this->ciyoCoinPurchaseRecorder()->createCiyoCoinTransaction(
						$orderId,
						$user->id,
						$ciyoTotalFee,
						$totalFee,
						PaymentCiyoCoinPurchased::ITEM_INKE_COIN,
						$dataInfo['name'],
						$inkeCurrency
					);

					return $this->dataResponse([
						'orderId' => $orderId,
						'transactionId' => $transaction->id,
						'inke_currency' => $inkeCurrency,
						'totalFee' => $ciyoTotalFee,
						'createTime' => $transaction->createTime->getTimestamp(),
					]);
				} elseif ($errInfo['errno'] == 3) {
					// 订单不存在
					return $this->errorsResponse(PaymentError::OrderNotFound());
				} else {
					$errMsg = $errInfo['errmsg'];
					$this->getLogger()->error($errMsg);

					return $this->errorsResponse(CommonError::SystemError());
				}
			} else {
				$errMsg = json_encode($data);
			}
		} else {
			$errMsg = 'Response not OK';
		}
		$this->getLogger()->error($errMsg);

		return $this->errorsResponse(CommonError::ServiceUnavailable());
	}

	/**
	 * @Route("/payment/ciyocoin/consume")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="payment",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=true},
	 *     {"name"="transaction_id", "dataType"="string", "required"=true},
	 *     {"name"="openid", "dataType"="string", "required"=true},
	 *     {"name"="nonce", "dataType"="string", "required"=true},
	 *     {"name"="signature", "dataType"="string", "required"=true}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse|Response
	 */
	public function consumeCiyoCoinAction(Request $request) {
		$sigKey = $this->getParameter('request_signature_key');
		$this->verifySignature($request->request, $sigKey);

		$user = $this->requireAuth($request);
		$transactionId = $this->requireParam($request->request, 'transaction_id');
		$openId = $this->requireParam($request->request, 'openid');
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		/** @var PaymentCiyoCoinPurchased|null $transaction */
		$transaction = $this->ciyoCoinPurchaseRecorder()->getTransactionById($transactionId);
		if (!$transaction) {
			return $this->errorsResponse(PaymentError::OrderNotFound());
		}
		if (!$this->ciyoCoinPurchaseRecorder()->isTransactionFinished($transaction)) {
			$em->beginTransaction();
			try {
				$this->ciyoCoinPurchaseRecorder()->finishCiyoCoinTransaction( $transaction );
				$user = $this->ciyoCoinPurchaseRecorder()->deductCiyoCoin( $user, $transaction->ciyoTotalFee );
				$em->commit();
			} catch (ErrorsException $e) {
				$em->rollback();
				return $this->errorsResponse($e->getErrors());
			} catch (\Exception $e) {
				$em->rollback();
				return $this->errorsResponse(CommonError::SystemError());
			}
		} else {
			return $this->errorsResponse(PaymentError::TransactionAlreadyFinished());
		}

		// 支付通知
		$this->inkeNotify($transaction, $openId);

		return $this->dataResponse([
			'ciyocoin' => (string)$user->ciyoCoin,
		]);
	}

	private function inkeNotify(PaymentCiyoCoinPurchased $transaction, $openId, $recursion = 0) {
		if ($this->container()->getParameter('kernel.environment') == 'prod') {
			$url = 'http://open.inke.com/sdk/notify';
		} else {
			$url = 'http://debug.open.ingkee.com/sdk/notify';
		}
		$nonce = $this->genNonceStr();
		$appId = $this->getParameter('inke_app_id');
		$appKey = $this->getParameter('inke_app_key');
		$client = new Client();
		$formParams = [
			'transaction_id' => $transaction->id,
			'nonce_str' => $nonce,
			'out_trade_no' => $transaction->outTradeNo,
			'openid' => $openId,
			'appid' => $appId,
			'total_fee' => $transaction->ciyoTotalFee,
			'stime' => $transaction->createTime->getTimestamp(),
			'etime' => $transaction->finishTime->getTimestamp(),
			'result_code' => 0,
			'attach' => '',
			'body' => $transaction->itemName
		];
		$formParams['sign'] = $this->requestSignature($formParams, $appKey);
		$response = $client->request('POST', $url, [
			'form_params' => $formParams
		]);
		do {
			if ($response) {
				if (200 == $response->getStatusCode()) {
					$body = $response->getBody();
					$data = json_decode($body, true);
					if ($data && isset($data['return_code'])) {
						if ('SUCCESS' === $data['return_code']) {
							return true;
						}
					} else {
						if ($recursion > 0) {
							$this->getLogger()->error(sprintf("[inke notify] %s", $body));
							break;
						}
					}
				}
			}
			$this->getLogger()->error('[inke notify] Response Error.');
		} while(0);

		if ($recursion < 1) {
			$recursion += 1;
			sleep(3);
			return $this->inkeNotify($transaction, $openId, $recursion);
		}

		return false;
	}

	/**
	 * @Route("/pay/paysearch")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="payment",
	 *   parameters={
	 *     {"name"="orderId", "dataType"="string", "required"=true},
	 *     {"name"="appid", "dataType"="string", "required"=true},
	 *     {"name"="nonce_str", "dataType"="string", "required"=true},
	 *     {"name"="sign", "dataType"="string", "required"=true}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function inkePaySearchAction(Request $request) {
		try {
			$orderId = $this->requireParam($request->request, 'orderId');
			$appId = $this->requireParam($request->request, 'appid');
			$nonceStr = $this->requireParam($request->request, 'nonce_str');
			$sign = $this->requireParam($request->request, 'sign');
		} catch (ErrorsException $e) {
			return $this->dataResponse($this->paySearchResponse(3, '缺少请求参数'));
		}

		$appKey = $this->getParameter('inke_app_key');
		$signature = md5(md5($orderId . $nonceStr) . $appId . $appKey . $nonceStr);
		if (0 === strcmp($sign, $signature)) {
			/** @var PaymentCiyoCoinPurchased|null $transaction */
			$transaction = $this->ciyoCoinPurchaseRecorder()->getTransactionByOutTradeNo($orderId);
			if ($transaction) {
				if ($transaction->finishTime) {
					return $this->dataResponse($this->paySearchResponse(
						0,
						'',
						'10',
						$transaction->id,
						$transaction->outTradeNo,
						$transaction->totalFee,
						$transaction->finishTime->getTimestamp()
					));
				} else {
					// 订单未支付
					return $this->dataResponse($this->paySearchResponse(
						0,
						'',
						'11',
						$transaction->id,
						$transaction->outTradeNo,
						$transaction->totalFee,
						''
					));
				}

			} else {
				return $this->dataResponse($this->paySearchResponse(1, '订单不存在'));
			}
		} else {
			return $this->dataResponse($this->paySearchResponse(2, '签名验证失败'));
		}
	}

	/**
	 * @param $errNo
	 * @param $msg
	 * @param $status
	 * @param $transactionId
	 * @param $orderId
	 * @param $payMoney
	 * @param $payTime
	 *
	 * @return array
	 */
	private function paySearchResponse(
		$errNo,
		$msg,
		$status = null,
		$transactionId = null,
		$orderId = null,
		$payMoney = null,
		$payTime = null
	) {
		if ($errNo != 0) {
			return [
				'errno' => $errNo,
				'msg' => $msg,
			];
		} else {
			return [
				'errno' => $errNo,
				'msg' => $msg,
				'data' => [
					'status' => (string)$status,
					'transaction_id' => (string)$transactionId,
					'orderId' => $orderId,
					'payMoney' => $payMoney,
					'payTime' => (string)$payTime,
				]
			];
		}
	}

	/**
	 * @param $payerType
	 *
	 * @return AlipayRequester
	 */
	private function getAlipayRequester($payerType) {
		if (2 == $payerType) {
			return $this->get('lychee.module.payment.alipay');
		} else {
			return $this->get('lychee.module.payment.alipay.yiciyuan');
		}
	}

	/**
	 * @param $payerType
	 *
	 * @return WechatRequester
	 */
	private function getWechatRequester($payerType) {
		/** @var WechatRequester $wechat */
		$wechat = $this->get('lychee.module.payment.wechat');
		$wechat = $this->setWechatAccount($wechat, $payerType);

		return $wechat;
	}

	/**
	 * @param WechatRequester $wechat
	 * @param $payerType
	 *
	 * @return mixed
	 */
	private function setWechatAccount($wechat, $payerType) {
		if (2 == $payerType) { // 次元社
			$wechat->setAccount(
				$this->getParameter('ciyocon_wechat_key'),
				$this->getParameter('ciyocon_wechat_mch_id'),
				$this->getParameter('ciyocon_wechat_app_id')
			);
		} else { // 异次元通讯
			$wechat->setAccount(
				$this->getParameter('yiciyuan_wechat_key'),
				$this->getParameter('yiciyuan_wechat_mch_id'),
				$this->getParameter('yiciyuan_wechat_app_id')
			);
		}

		return $wechat;
	}

	/**
	 * @Route("/payment/qpay/sign_pay")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="payment",
	 *   parameters={
	 *     {"name"="payer_type", "dataType"="integer", "required"=false, "description"="付款者类型,设备:1;用户:2;异次元用户:3. 默认值:1"},
	 *     {"name"="device_id", "dataType"="string", "required"=false, "description"="当payer_type为1时,必须提交此参数"},
	 *     {"name"="access_token", "dataType"="string", "required"=false, "description"="当payer_type为2时,必须提交此参数"},
	 *     {"name"="product_id", "dataType"="integer", "required"=true},
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse|Response
	 */
	public function qpaySignPayAction(Request $request) {
		list($payerType, $payer) = $this->requirePayer($request);
		if ($payerType == PayerType::USER) {
			$productId = $this->requireInt($request->request, 'product_id');
			$product = $this->productManager()->getProductById($productId);
			if (!$productId) {
				return $this->errorsResponse(CommonError::ParameterInvalid('product_id', $productId));
			}
		} else {
			$appStoreId = $this->requireParam($request->request, 'product_id');
			$product = $this->productManager()->getProductByAppStoreId($appStoreId);
			if (!$product) {
				return $this->errorsResponse(CommonError::ObjectNonExist($appStoreId));
			}
			$productId = $product->id;
		}

		$clientIp = $request->getClientIp();
		$transaction = $this->transactionService()->createTransaction(
			$payerType, $payer, $productId, $product->price, $clientIp, PayType::QPAY
		);
		/** @var QpayRequester $qpay */
		$qpay = $this->getQpayRequester($payerType);
		$notifyUrl = $this->generateUrl('payment_qpay_notify', array(), UrlGenerator::ABSOLUTE_URL);

		$requestParams = $qpay->signPayRequest($product->name, $transaction->id, $product->price, $transaction->startTime, $transaction->endTime, $notifyUrl, $transaction->clientIp);
		if ($requestParams == null) {
			return $this->errorsResponse(CommonError::SystemBusy());
		}
		$this->transactionService()->logTransactionRequest($transaction->id, http_build_query($requestParams));

		$requestParams['trade_no'] = $transaction->id;
		return new JsonResponse($requestParams);
	}

	/**
	 * @Route("/payment/qpay/query_order")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="payment",
	 *   parameters={
	 *     {"name"="payer_type", "dataType"="integer", "required"=false, "description"="付款者类型,设备:1;用户:2;异次元用户:3. 默认值:1"},
	 *     {"name"="device_id", "dataType"="string", "required"=false, "description"="当payer_type为1时,必须提交此参数"},
	 *     {"name"="access_token", "dataType"="string", "required"=false, "description"="当payer_type为2时,必须提交此参数"},
	 *     {"name"="trade_no", "dataType"="string", "required"=true},
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse|Response
	 */
	public function qpayQueryOrderAction(Request $request) {
		list($payerType, $payer) = $this->requirePayer($request);

		$tradeNo = $this->requireParam($request->query, 'trade_no');
		$transactionId = intval($tradeNo);
		if ($transactionId <= 0) {
			return $this->errorsResponse(CommonError::ParameterInvalid('trade_no', $tradeNo));
		}
		$transaction = $this->transactionService()->getTransactionById($transactionId);
		if ($transaction == null || $transaction->payer != $payer) {
			return $this->errorsResponse(CommonError::ParameterInvalid('trade_no', $tradeNo));
		}

		/** @var QpayRequester $qpay */
		$qpay = $this->getQpayRequester($payerType);
		$res = $qpay->orderquery($transaction->id);
		if ($res == null) {
			return $this->errorsResponse(CommonError::SystemBusy());
		} else {
			if (isset($res['trade_state']) && 'SUCCESS' === $res['trade_state']) {
				// 查询结果是支付成功，则马上把商品到账
				if (false === $this->transactionService()->isTransactionFinished($transactionId)) {
					/**
					 * 必须先判断订单是否已完成，未完成才进行充值操作，最后再log下订单的finish状态
					 */
					/*
					$product = $this->productManager()->getProductById($transaction->productId);
					if (PayerType::USER == $transaction->payerType) {
						// 多次查询必须确保只到账一次
						$user = $this->account()->fetchOne($transaction->payer);
						$this->rechargeCiyoCoin($user, $product->ciyoCoin);
					}
					*/
					$product = $this->productManager()->getProductById($transaction->productId);

					$this->purchaseRecorder()->record($transaction->payer, $transactionId, $transaction->productId, $product->price, $transaction->payType);
					$this->actionAfterPay($transaction);
					$this->transactionService()->logTransactionFinish($transactionId, new \DateTime($res['time_end']), 0);
				}
			}
		}
		return new JsonResponse($res);
	}

	/**
	 * @Route("/payment/qpay/notify", name="payment_qpay_notify")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return Response
	 */
	public function qpayNotifyAction(Request $request) {
		$body = $request->getContent();
		$notifyId = $this->transactionService()->logThridPartyNotify(PayType::QPAY, $body);

		/** @var QpayRequester $qpay */
		$qpay = $this->get('lychee.module.payment.qpay');
		$params = $qpay->toArray($body);
		do {
			if (is_array($params) && isset($params['appid'])) {
				$appId = $params['appid'];
				if ($appId == $this->getParameter('ciyocon_qpay_app_id')) {
					$payerType = 2;
				} else {
					$payerType = 1;
				}
				$qpay = $this->setQpayAccount($qpay, $payerType);
				$params = $qpay->verifyNotify($params);
				if ($params) {
					break;
				}
			}
			throw $this->createNotFoundException();
		} while (0);

		$tradeNo = $params['out_trade_no'];
		$transactionId = intval($tradeNo);
		if ($transactionId != $tradeNo) {
			throw $this->createNotFoundException();
		}
		$transaction = $this->transactionService()->getTransactionById($transactionId);
		if ($transaction == null) {
			throw $this->createNotFoundException();
		}

		$payTimeStr = $params['time_end'];
		$payTime = \DateTime::createFromFormat('YmdHis', $payTimeStr);
		$this->purchaseRecorder()->record($transaction->payer, $transaction->id, $transaction->productId, $transaction->totalFee, $transaction->payType);

		if (false === $this->transactionService()->isTransactionFinished($transaction->id)) {
			$this->actionAfterPay( $transaction );
		}

		$this->transactionService()->logTransactionFinish($transaction->id, $payTime, $notifyId);

		return new Response('<xml><return_code>SUCCESS</return_code></xml>');
	}

	/**
	 * @param QpayRequester $qpay
	 * @param $payerType
	 *
	 * @return QpayRequester
	 */
	private function setQpayAccount(QpayRequester $qpay, $payerType) {
		if (2 == $payerType) { // 次元社
			$qpay->setAccount(
				$this->getParameter('ciyocon_qpay_key'),
				$this->getParameter('ciyocon_qpay_mch_id'),
				$this->getParameter('ciyocon_qpay_app_id'),
				$this->getParameter('ciyocon_qpay_app_key')
			);
		} else { // 异次元通讯
			$qpay->setAccount(
				$this->getParameter('yiciyuan_qpay_key'),
				$this->getParameter('yiciyuan_qpay_mch_id'),
				$this->getParameter('yiciyuan_qpay_app_id'),
				$this->getParameter('yiciyuan_qpay_app_key')
			);
		}

		return $qpay;
	}

	/**
	 * @param $payerType
	 *
	 * @return QpayRequester
	 */
	private function getQpayRequester($payerType) {
		/** @var QpayRequester $qpay */
		$qpay = $this->get('lychee.module.payment.qpay');
		$qpay = $this->setQpayAccount($qpay, $payerType);

		return $qpay;
	}
}