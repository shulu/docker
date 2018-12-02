<?php

namespace Lychee\Bundle\AdminBundle\Controller;

use Doctrine\ORM\EntityManager;
use Lychee\Bundle\AdminBundle\Components\Foundation\Paginator;
use Lychee\Bundle\AdminBundle\Service\ManagerLog\OperationType;
use Lychee\Bundle\CoreBundle\Entity\User;
use Lychee\Module\Account\Exception\EmailDuplicateException;
use Lychee\Module\Account\Exception\NicknameDuplicateException;
use Lychee\Module\Account\Exception\PhoneDuplicateException;
use Lychee\Module\Authentication\Entity\AuthToken;
use Lychee\Module\ExtraMessage\EMGrantType;
use Lychee\Module\ExtraMessage\Entity\EMDictionary;
use Lychee\Module\ExtraMessage\Entity\EMPromotionCode;
use Lychee\Module\ExtraMessage\Entity\EMPromotionCodeVendorRecord;
use Lychee\Module\ExtraMessage\Entity\EMUser;
use Lychee\Module\Payment\Entity\PaymentProductPurchased;
use Lychee\Module\Payment\Entity\PaymentProduct;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\ContentAudit\ContentAuditService;
use Lychee\Module\IM\Message;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Lychee\Module\ExtraMessage\Entity\EMPictureRecord;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class ExtramessageController
 * @package Lychee\Bundle\AdminBundle\Controller
 * @Route("/extramessage")
 */
class ExtramessageController extends BaseController
{

    public function getTitle()
    {
        return '';
    }

    /**
     * @var Logger
     */
    private $logger;

    public function getLogger(){

        if($this->logger === null) {
            // Create the logger
            $this->logger = new Logger('[lychee server]');
            // Now add some handlers
            $logPath = $this->container()->get('kernel')->getLogDir();
            $this->logger->pushHandler(new StreamHandler($logPath . '/emUser.log', Logger::DEBUG));
        }

        return $this->logger;
    }

    public function sucessResponse() {
        return new JsonResponse(array('result' => true));
    }

    public function failureResponse($code = 0, $displayMessage = '') {
        return new JsonResponse(array('code' => $code, 'display_message' => $displayMessage));
    }

    public function getTheUser($login,$v){
        $theuser='';
        if($login=='weibo'){
            $theuser=$this->emAuthenticationService()->getUserByWeiBoUserId($v);
        }elseif ($login=='QQ'){
            $theuser=$this->emAuthenticationService()->getUserByQQUserId($v);
        }elseif($login=='wechat'){
            $theuser=$this->emAuthenticationService()->getUserByWechatUserId($v);
        }elseif ($login=='bili'){
            $theuser=$this->emAuthenticationService()->getUserByBiliUserId($v);
        }elseif ($login=='dmzj'){
            $theuser=$this->emAuthenticationService()->getUserByDmzjUserId($v);
        }
        return $theuser;
    }

    /**
     * @Route("/")
     * @Template()
     * @return array
     */
    public function indexAction(){
        return array();
    }

    /**
     * @Route("/user")
     * @Template()
     * @return array
     */
    public function userAction()
    {
        $request = $this->get('Request');
        $query = $request->query->get('query');
        $paginator = null;
        $nextCursor = null;
        $cursor = $request->query->get('cursor', PHP_INT_MAX);
        if (0 >= $cursor) {
            $cursor = PHP_INT_MAX;
        }
        if (null !== $query) {
//            var_dump($query);exit;
            $iterator = $this->emUerService()->iterateTopic('DESC',$query);
            $paginator = new Paginator($iterator);
            $paginator->setCursor($cursor)
                ->setPage($request->query->get('page', 1))
                ->setStep(30)
                ->setStartPageNum($request->query->get('start_page', 1));
            $users = $paginator->getResult();
            return $this->response($this->getTitle(), array(
                'paginator' => $paginator,
                'users' => $users,
                'query'=>$query
            ));
        }else{
            $iterator = $this->emUerService()->iterateUser('DESC');
            $paginator = new Paginator($iterator);
            $paginator->setCursor($cursor)
                ->setPage($request->query->get('page', 1))
                ->setStep(30)
                ->setStartPageNum($request->query->get('start_page', 1));
            $users = $paginator->getResult();
//            var_dump($users);
//            var_dump($paginator);
//            exit;
            //$users = $this->emUerService()->getUser($query);
            //$this->getLogger()->addInfo('user',array('user'=>$users));

        }
        return $this->response($this->getTitle(), array(
            'paginator' => $paginator,
            'users' => $users,
            'query'=>$query
        ));
    }

    /**
     * @Route("/user/touWei")
     * @Template()
     * @return array
     */
    public function touWeiAction(){
        $request = $this->get('Request');
        $query = $request->query->get('query');
        if(null !== $query){
            $pays= $this->purchaseRecorder()->getRecordByPayer($query);
            $product=$this->productManager()->listProducts();
           // $this->getLogger()->addInfo('$pays',array('$pays'=>$pays));
            foreach ($pays as $k=>$v){
                foreach ($product as $i=>$item){
//                    $this->getLogger()->addInfo('productId',array('productId'=>($v->productId)));
//                    $this->getLogger()->addInfo('if',array('$item->id'=>($item->id)));
//                    $this->getLogger()->addInfo('if',array('if'=>($v->productId==$item->id)));
                    if($v->productId==$item->id){
                        $v->productId=$item->name;
                        $v->appStoreTransactionId=$item->desc;
                    }
                }
            }
        }else{
            return $this->response('投喂查询',array('pays'=>''));
        }

        return $this->response('投喂查询',array('pays'=>$pays));
    }

    /**
     * @Route("/user/tuJian")
     * @Template()
     * @return array
     */
    public function tuJianAction(){
        $request = $this->get('Request');
        $query = $request->query->get('query');
        if(null !== $query){
            $tu=$this->emPictureRecord()->getRecord($query);
            $this->getLogger()->addInfo('tujian',array('tujian'=>$tu));
        }else{
            return $this->response('图鉴查询',array('tu'=>''));
        }

        return $this->response('图鉴查询',array('tu'=>$tu));
    }

    /**
 * @Route("/user/daily")
 * @Template()
 * @return array
 */
    public function dailyAction(){
        $request = $this->get('Request');
        $query = $request->query->get('query');
        if(null !== $query){
            $daily=$this->extraMessageService()->getDiaryRecordByUserId($query);

            //var_dump(explode('},',$daily->record));exit;
//            $data=preg_replace('!s:(\d+):"(.*?)";!se',"'s:'.strlen('$2').':\"$2\";'",$daily->record);
//            $record=unserialize($data);
            //$this->getLogger()->addInfo('daily',array('daily'=>$daily));
        }else{
            return $this->response('日志查询',array('daily'=>''));
        }

        return $this->response('日志查询',array('daily'=>$daily));
    }

    /**
     * @Route("/user/dictionary")
     * @Template()
     * @return array
     */
    public function dictionaryAction(){
        $request = $this->get('Request');
        $query = $request->query->get('query');
        if(null !== $query){
            $daily=$this->extraMessageService()->getDictionaryRecordByUserId($query);
            if(!empty($daily)){
                $record=explode(',',$daily->record);
                return $this->response('词典查询',array(
                    'id'=>$daily->userId,
                    'record'=>$record
                ));
            }else{
                return $this->response('词典查询',array(
                    'id'=>'',
                    'record'=>''
                ));
            }
        }else{
            return $this->response('词典查询',array(
                'id'=>'',
                'record'=>''
            ));
        }
    }

    /**
     * @Route("/user/hebing")
     * @Template()
     * @return array
     */
    public function userHeBingAction(){
        return array();
    }

    /**
     * @Route("/user/hebing/get", name="user_hebing_get")
     * @Method("POST")
     */
    public function heBingGet(){
        $record='';
        $pays='';
        $request = $this->get('Request');
        $query = $request->request->get('query');
        $user=$this->emUerService()->getUserById($query);
        $tuJian=$this->emPictureRecord()->getRecord($query);//图鉴

        $dictionary=$this->extraMessageService()->getDictionaryRecordByUserId($query);//词典
        if(!empty($dictionary)){
            $record=$dictionary->record;
        }
        $touWei= $this->purchaseRecorder()->getRecordByPayer($query);;//投喂记录
        if(!empty($touWei)){
            $pays=$touWei;
        }
//            var_dump($user[0]->id);exit;
        $this->getLogger()->addInfo('$user',array('$user'=>$tuJian));
        return new JsonResponse(array(
            'userId'=>$user[0]->id,
            'userName'=>$user[0]->nickname,
            'dictionary'=>$record,
            'touWei'=>$pays,
            'tuJian'=>$tuJian
        ));
    }

    /**
     * @Route("/user/hebing/done", name="user_hebing_done")
     * @Method("POST")
     */
    public function heBingDone(Request $request){
        $userMainID=$request->request->get('userMain');
        $userIds=$request->request->get('users');
        $login=$request->request->get('login');
        $mainTuJian=$this->emPictureRecord()->getRecord($userMainID);//图鉴
        $mainDiction=$this->extraMessageService()->getDictionaryRecordByUserId($userMainID);//词典
        $users=array();
        if(!empty($mainDiction->record)){
            $diction=json_decode($mainDiction->record,true);
        }else{
            $diction=array();
        }
        if(!empty($mainTuJian->record)){
            $tujian=json_decode($mainTuJian->record,true);
        }else{
            $tujian=array();
        }

	    $mainUser=$this->getTheUser($login,$userMainID);
	    if(!empty($mainUser)){
		    $users[]=$mainUser;
	    }
	    else {
		    return $this->failureResponse(0,'请核对玩家登录类型');
	    }

        //检查user是否冲突
        foreach ($userIds as $k=>$userId){
            $theuser=$this->getTheUser($login,$userId);
            if(!empty($theuser) && !in_array($theuser, $users)){
                $users[]=$theuser;
            }
        }

        if(count($users)>1){
            return $this->failureResponse(0,'请核对玩家ID');
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        try {
            $em->beginTransaction();

            //整合图鉴、词典，置换投喂记录，清除token
            foreach ($userIds as $userId){
                if($userId==$userMainID){
                    continue;
                }
                /** @var AuthToken $token */
                $token = $this->emAuthenticationService()->getTokenByUserId($userId);
                if ($token) {
	                $this->authentication()->revokeToken($token->accessToken);
                }
                /** @var EMDictionary $userDiary */
                $userDiary=$this->extraMessageService()->getDictionaryRecordByUserId($userId);
                if(!empty($userDiary->record)){
                    $mordic=json_decode($userDiary->record,true);
                    $diction = array_merge($diction, $mordic);
                    $diction = array_unique($diction);
                }

                $mortuk=$this->emPictureRecord()->getRecord($userId);
                if(!empty($mortuk->record)){
                    $mortu=json_decode($mortuk->record,true);
                    $tujian = array_merge($tujian, $mortu);
                    $tujian = array_unique($tujian);
                }
                $morTouwei=$this->purchaseRecorder()->getRecordByPayer($userId);//投喂置换
                foreach ($morTouwei as $record){
                    $record->payer=$userMainID;
                    $this->purchaseRecorder()->saveEntity($record);
                }
            }

            $users[0]->userId=$userMainID;//换userID
            $this->emAuthenticationService()->saveEntity($users[0]);
            $diction=json_encode(array_values(array_unique($diction)));
            $tujian=json_encode(array_values(array_unique($tujian)));
            $this->extraMessageService()->addDictionaryRecord($userMainID, $diction);
            $this->extraMessageService()->addPictureRecord($userMainID, $tujian);

            $em->commit();
        } catch (\Exception $exception) {
            $em->rollback();
            throw  $exception;
            return $this->failureResponse(0, '系统错误');
        }

        return $this->sucessResponse();
    }


	/**
	 * @Route("/promotioncode/vendor_records")
	 * @Template()
	 * @return array
	 */
	public function promotionCodeVendorRecordsAction(){
		$records = $this->extraMessageService()->listPromotionCodeVendorRecords();
		$grantTypes = [
			'taptap' => 'TapTap',
			EMGrantType::BILIBILI => 'B站',
			EMGrantType::DMZJ => '动漫之家'
		];
		$productIds = ArrayUtility::columns($records, 'productId');
		$productIds = array_unique($productIds);
		$products = $this->productManager()->fetch($productIds);

		/** @var EntityManager $entityManager */
//		$entityManager = $this->getDoctrine()->getManager();
//		$qb = $entityManager->createQueryBuilder();
//		$qb->select('count(p.id)')
//			->from(EMPromotionCode::class,'p')
//			->where('p.preGen = 1');
//		$preGenCount = $qb->getQuery()->getSingleScalarResult();


		return $this->response('图鉴查询', array(
			'records'=> $records,
			'grant_types' => $grantTypes,
			'products' => $products,
//			'pre_gen_code_count' => $preGenCount,
		));
	}


	/**
	 * @Route("/promotioncode/vendor_record/submit")
	 * @Method("POST")
	 * @Template()
	 * @return array
	 */
	public function promotionCodeVendorRecordSubmitAction( Request $request ) {

		$vendor = $request->request->get('vendor');
		$productId = $request->request->get('product_id');
		$count = $request->request->get('count');
		$expireTime = $request->request->get('expire_time');

		$count = is_numeric($count) ? $count : 0;

		if (!$vendor || !$productId || !$count) {
			return $this->redirectToRoute('lychee_admin_extramessage_promotioncodevendorrecords');
		}


		if (!$expireTime) {
			$expireTime = null;
		}
		else {
			$expireTime = new \DateTime($expireTime);
		}

		/** @var EntityManager $entityManager */
		$entityManager = $this->getDoctrine()->getManager();
		$qb = $entityManager->createQueryBuilder();
		$qb->select('count(p.id)')
		   ->from(EMPromotionCode::class,'p')
		   ->where('p.preGen = 1');
		$preGenCount = $qb->getQuery()->getSingleScalarResult();

		if ($count > $preGenCount) {
			set_time_limit(0);
			$this->extraMessageService()->preGeneratePromotionCode($count * 2);
		}

		try {
			$entityManager->beginTransaction();

			$record = new EMPromotionCodeVendorRecord();
			$record->productId = $productId;
			$record->vendor = $vendor;
			$record->count = $count;
			$record->codeExpireTime = $expireTime;
			$record->createTime = new \DateTime();
			$entityManager->persist($record);
			$entityManager->flush($record);

			$codes = $entityManager->getRepository(EMPromotionCode::class)
				->findBy(['preGen' => 1], ['id' => 'ASC'], $count);
			foreach ($codes as $promotionCode) {
				/** @var EMPromotionCode  $promotionCode */
				$promotionCode->productId = $productId;
				$promotionCode->appStoreTransationId = null;
				$promotionCode->createTime = $record->createTime;
				$promotionCode->vendor = $vendor;
				$promotionCode->vendorRecordId = $record->id;
				$promotionCode->codeExpireTime = $record->codeExpireTime;
				$promotionCode->preGen = false;
			}
			$entityManager->flush();

			$entityManager->commit();
		} catch (\Exception $exception) {
			$entityManager->rollback();
			var_dump($exception->getMessage());
			exit;
		}

		return $this->redirectToRoute('lychee_admin_extramessage_promotioncodevendorrecords');
	}


	/**
	 * @Route("/promotioncode")
	 * @Template()
	 * @return array
	 */
	public function promotionCodeAction( Request $request){

		$recordId = $request->query->get('record_id');
		$query = $request->query->get('query');
		$paginator = null;
		$nextCursor = null;
		$cursor = $request->query->get('cursor', PHP_INT_MAX);
		if (0 >= $cursor) {
			$cursor = PHP_INT_MAX;
		}

		if ($query) {
			$iterator = $this->extraMessageService()->iteratePromotionCodeByKeyword($query);
		}
		else {
			$iterator = $this->extraMessageService()->iteratePromotionCode(null, $recordId);
		}
		$paginator = new Paginator($iterator);
		$paginator->setCursor($cursor)
		          ->setPage($request->query->get('page', 1))
		          ->setStep(20)
		          ->setStartPageNum($request->query->get('start_page', 1));
		$codes = $paginator->getResult();

		$userIds = ArrayUtility::columns($codes, 'receiverId');
		$users = $this->emUerService()->fetch($userIds);

		$productIds = ArrayUtility::columns($codes, 'productId');
		$productIds = array_unique($productIds);
		$products = $this->productManager()->fetch($productIds);

		return $this->response($this->getTitle(), array(
			'paginator' => $paginator,
			'codes' => $codes,
			'users' => $users,
			'query' => $query,
			'record_id' => $recordId,
			'products' => $products
		));
	}

	/**
	 * @Route("/promotioncode/export")
	 * @Template()
	 * @return array
	 */
	public function promotionCodeExportAction( Request $request ) {

		$record_id = $request->query->get('record_id');
		if (!$record_id) {
			return $this->redirectToRoute('lychee_admin_extramessage_promotioncodevendorrecords');
		}

		$promotionCodes = $this->extraMessageService()->listAllPromotionCode($record_id);
		$productIds = ArrayUtility::columns($promotionCodes, 'productId');
		$productIds = array_unique($productIds);

		$products = [];
		foreach ($productIds as $productId) {
			$products[$productId] = $this->productManager()->getProductById($productId);
		}

		$codes = [
			[
//				'product' => '章节',
				'code' => '礼品码'
			]
		];
		foreach ($promotionCodes as $promotionCode) {
			$code = [
//				'product' => $products[$promotionCode->productId]->name,
				'code' => $promotionCode->code
			];
			$codes[] = $code;
		}

		$response = new StreamedResponse(function() use ($codes){
			$handle = fopen('php://output', 'w');
			foreach ( $codes as $code ) {
				fputcsv($handle, $code);
			}

			fclose($handle);
		});

		$filename = "promotion_code" . date("Y-m-d") . ".csv";
		$response->headers->set('Content-Type', 'application/force-download');
		$response->headers->set('Content-Disposition','attachment; filename="' . $filename . '"');

		return $response;

	}

	/**
	 * @Route("/promotioncode/pregen")
	 * @Template()
	 * @return array
	 */
	public function promotionCodePreGen( Request $request ) {
		$count = $request->query->get('count');
		$count = $count > 0 ? $count : 1000;
		$count = $count < 10000 ? $count : 10000;
		set_time_limit(0);
		$this->extraMessageService()->preGeneratePromotionCode($count);

		return $this->redirectToRoute('lychee_admin_extramessage_promotioncodevendorrecords');
	}

}
