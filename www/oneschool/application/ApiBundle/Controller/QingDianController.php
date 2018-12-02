<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 2017/9/4
 * Time: 上午9:38
 */

namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Bundle\ApiBundle\Error\AccountError;
use Lychee\Bundle\ApiBundle\Error\AuthenticationError;
use Lychee\Bundle\ApiBundle\Error\CommonError;
use Lychee\Bundle\ApiBundle\Error\ErrorsException;
use Lychee\Bundle\CoreBundle\Entity\User;
use Lychee\Bundle\CoreBundle\Entity\UserProfile;
use Lychee\Bundle\CoreBundle\Validator\Constraints\Nickname;
use Lychee\Bundle\CoreBundle\Validator\Constraints\NotSensitiveWord;
use Lychee\Bundle\CoreBundle\Validator\Constraints\Password;
use Lychee\Bundle\CoreBundle\Validator\Constraints\ReservedWord;
use Lychee\Module\Account\Exception\NicknameDuplicateException;
use Lychee\Module\Account\Mission\MissionResult;
use Lychee\Module\Account\Mission\MissionType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Lychee\Module\Authentication\PhoneVerifier;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\ConstraintViolation;
use Monolog\Logger;

class QingDianController extends Controller{

	/**
	 * @Route("/qingdian/userinfo")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="qingdian",
	 *   parameters={
	 *     {"name"="uid", "dataType"="string", "required"=true, "description"="次元号"},
	 *     {"name"="nonce", "dataType"="string", "required"=true, "description"="随机字符串"},
	 *     {"name"="sig", "dataType"="string", "required"=true,
	 *     "description"="请求签名,算法同微信见附https://pay.weixin.qq.com/wiki/doc/api/app/app.php?chapter=4_3"},
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function fetchUserInfoAction(Request $request){

		$this->verifySignature($request->request, $this->getParameter('qingdian_app_key'), 'sig', 'md5');
		/** @var Logger $logger */
		$logger = $this->get('monolog.logger.thirdparty_invoke');
		$logger->info(sprintf("[%s] %s", $request->getPathInfo(), $request->getQueryString()));

		$uid = $this->requireParam($request, 'uid');

		$user = $this->account()->fetchOne($uid);

		$requestTime = new \DateTime();

		$conn = $this->getDoctrine()->getConnection();
		$conn->beginTransaction();
		$insertSql = 'INSERT INTO qingdian_user(user_id, request_time) VALUES (?, ?) ON DUPLICATE KEY UPDATE request_time = request_time';
		$conn->executeUpdate($insertSql, array($uid, $requestTime->format('Y-m-d H:i:s')), array(\PDO::PARAM_INT, \PDO::PARAM_STR));
		$conn->commit();

		return $this->dataResponse([
			'msg' => 'success',
			'status' => '1',
			'data' => array(
				'nickname' => $user->nickname,
				'gender' => $user->gender,
				'avatar' => $user->avatarUrl
			)
		]);
	}
}