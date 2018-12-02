<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 16/01/2017
 * Time: 8:02 PM
 */

namespace Lychee\Module\ExtraMessage;


use Doctrine\ORM\EntityManagerInterface;
use Lsw\MemcacheBundle\Cache\MemcacheInterface;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Foundation\IteratorTrait;
use Lychee\Component\Foundation\StringUtility;
use Lychee\Module\ExtraMessage\Entity\Contacts;
use Lychee\Module\ExtraMessage\Entity\EMClientVersion;
use Lychee\Module\ExtraMessage\Entity\EMDiary;
use Lychee\Module\ExtraMessage\Entity\EMDictionary;
use Lychee\Module\ExtraMessage\Entity\EMPaymentComment;
use Lychee\Module\ExtraMessage\Entity\EMPaymentRanking;
use Lychee\Module\ExtraMessage\Entity\EMPictureRecord;
use Lychee\Module\ExtraMessage\Entity\EMPromotionCode;
use Lychee\Module\ExtraMessage\Entity\EMPromotionCodeVendorRecord;
use Lychee\Module\ExtraMessage\Entity\Options;
use Lychee\Module\ExtraMessage\Entity\Plays;
use Lychee\Module\ExtraMessage\Entity\EMGameRecord;
use Lychee\Module\ExtraMessage\Entity\ProductPromotionConfig;
use Lychee\Module\Payment\Entity\PaymentProductPurchased;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Lychee\Component\Foundation\CursorableIterator\QueryCursorableIterator;

/**
 * Class ExtraMessageService
 * @package Lychee\Module\ExtraMessage
 */
class ExtraMessageService {

	use IteratorTrait;

	/** @var  RegistryInterface */
	private $doctrine;

	/**
	 * @var EntityManagerInterface
	 */
	private $em;

    /**
     * @var MemcacheInterface
     */
	private $memcache;

	private $rankingMutedUserIds = [391039, 178022, 9, 10, 59701, 198444, 389835, 392165, 12, 199241, 396526, 696132, 696181, 732191, 743183, 348025];


	/**
	 * ExtraMessageService constructor.
	 *
	 * @param RegistryInterface $doctrine
	 *
	 */
	public function __construct(RegistryInterface $doctrine, MemcacheInterface $memcache) {
		$this->doctrine = $doctrine;
		$this->em = $doctrine->getManager();
		$this->memcache = $memcache;
	}

	/**
	 * @param $deviceId
	 * @param $qqOrEmail
	 *
	 * @return null
	 */
	public function appendContacts($deviceId, $qqOrEmail) {
		$contacts = new Contacts();
		$contacts->deviceId = $deviceId;
		if (strpos($qqOrEmail, '@') > 0) {
			$contacts->qq = '';
			$contacts->email = $qqOrEmail;
		} elseif (is_numeric($qqOrEmail)) {
			$contacts->qq = $qqOrEmail;
			$contacts->email = '';
		} else {
			return null;
		}
		$contacts->createTime = new \DateTime();
		$this->em->persist($contacts);
		$this->em->flush();
	}

	public function getPlayById($id) {
		/** @var Plays $item */
		$item = $this->em->getRepository(Plays::class)->find($id);

		return $item;
	}

	public function getOptionById($id) {
		/** @var Options $item */
		$item = $this->em->getRepository(Options::class)->find($id);
		return $item;
	}

	public function getPlayByNextId($id) {
		/** @var Plays $item */
		$item = $this->em->getRepository(Plays::class)->findOneBy(array('next' => $id));
		return $item;
	}
	public function getoptionsByOptionId($id) {
		$items = $this->em->getRepository(Options::class)->findBy(array('optionId' => $id));

		return $items;
	}

	public function getOptionsByNextId($id) {
		$items = $this->em->getRepository(Options::class)->findBy(array('next' => $id));

		return $items;
	}

	public function findFirstPlay() {
		$repository = $this->em->getRepository(Plays::class);
		$query = $repository->createQueryBuilder('p')
			->where('p.type = 0')
			->orderBy('p.id', 'ASC')
			->setMaxResults(1)
			->getQuery();
		$result = $query->getOneOrNullResult();

		return $result;
	}

	public function findCurrentPlay($id) {
		do {
			$prevItem = $this->getPlayByNextId($id);
			if (!$prevItem) {
				$optionsItem = $this->getOptionsByNextId($id);
				if ($optionsItem) {
					$prevItem = $this->getPlayById($optionsItem[0]->optionId);
				}
			}
			if ($prevItem) {
				$id = $prevItem->id;
			}
		} while($prevItem && $prevItem->type);

		return $prevItem;
	}

	public function getAllPlays() {
		$repository = $this->em->getRepository(Plays::class);
		$query = $repository->createQueryBuilder('p')
			->where('p.type = 0')
			->orderBy('p.id', 'ASC')
			->getQuery();
		$result = $query->getResult();
		return $result;

	}

	public function setANewPlay($name) {
		$play = new Plays();
		$play->next = null;
		$play->subtitleline = $name;
		$play->type = 0;
		$this->em->persist($play);
		$this->em->flush();
	}

	public function addOneSystemMessage($message) {
		$item = new Plays();
		$item->subtitleline = $message;
		$item->type = 1;
		$item->next = null;
		$this->em->persist($item);
		$this->em->flush();
	}

	public function addOneSomebodyMessage($message) {
		$item = new Plays();
		$item->subtitleline = $message;
		$item->type = 2;
		$item->next = null;
		$this->em->persist($item);
		$this->em->flush();
	}

	public function addOneOptionToPlay() {
		$item = new Plays();
		$item->subtitleline = null;
		$item->type = 3;
		$item->next = null;
		$this->em->persist($item);
		$this->em->flush();
	}

	public function getLatestPlayItem() {
		$repository = $this->em->getRepository(Plays::class);
		$query = $repository->createQueryBuilder('p')
			->orderBy('p.id', 'DESC')
			->setMaxResults(1)
			->getQuery();
		$result = $query->getOneOrNullResult();
		return $result;
	}

	public function setNext($id, $next) {
		var_dump($id, $next);
		$item = $this->getPlayById($id);
		if ($item) {
			$item->next = $next;
			$this->em->flush();
		}
	}

	public function addOptionsToOptions($id, $optionStrs) {
		if (is_array($optionStrs)) {
			foreach ($optionStrs as $option) {
				$item = new Options();
				$item->optionId = $id;
				$item->next = null;
				$item->optionStr = $option;
				$this->em->persist($item);
				$this->em->flush();
			}
		}
	}

	public function setNextOnOption($id, $nextId) {
		/** @var Options $item */
		$item = $this->getOptionById($id);
		if ($item) {
			$item->next = $nextId;
			$this->em->flush();
		}
	}

	public function editOption($id, $nextId, $string) {
		$item = $this->getOptionById($id);
		if ($item) {
			if ($nextId) {
				$item->next = $nextId;
			}
			$item->optionStr = $string;
			$this->em->flush();
		}
	}

	public function editMessage($id, $content, $nextId) {
		$item = $this->getPlayById($id);
		if ($item) {
			$item->subtitleline = $content;
			if ($nextId) {
				$item->next = $nextId;
			}
			$this->em->flush();
		}
	}

	public function removePlayById($id) {
		$item = $this->getPlayById($id);
		if ($item) {
			$this->em->remove($item);
			$this->em->flush();
		}
	}

	public function removeOptions($id) {
		$this->removePlayById($id);
		$options = $this->getoptionsByOptionId($id);
		if ($options) {
			foreach ($options as $option) {
				$this->em->remove($option);
				$this->em->flush();
			}
		}
	}

	public function getDiaryRecordByUserId($userId){
		$result = $this->em->getRepository(EMDiary::class)->findOneBy(array('userId' => $userId));
		return $result;
	}

	public function addDiaryRecord($userId, $record){

		$item = $this->getDiaryRecordByUserId($userId);
		if(isset($item)){
			$record = $this->prepareDiaryRecords($item->record, $record);
			$item->record = $record;
		} else {

			$item = new EMDiary();
			$item->userId = $userId;
			$record = $this->prepareDiaryRecords(null, $record);;
			$item->record = $record;
		}

		$this->em->persist($item);
		$this->em->flush();
	}

	private function prepareDiaryRecords( $records, $newRecords ) {

		$allRecords = array();
		$records =  empty($records) ? array() : json_decode($records, true);
		$newRecords =  empty($newRecords) ? array() : json_decode($newRecords, true);
		$records = array_merge($newRecords, $records);

		foreach ($records as $index => $record) {
			if (!isset($record['dlsID']) || !$record['dlsID']) {
				if (isset($record['createTimeStamp']) && $record['createTimeStamp']) {
					$record['dlsID'] = md5($record['dlsChapterNo'] . '_' . $record['createTimeStamp']);
				}
				else {
					$record['dlsID'] = md5($record['dlsChapterNo'] . '_' . $record['dlsTime']);
				}
			}

			if (isset($allRecords[$record['dlsID']])) {
				if (count($record['dlsDatas']) > count($allRecords[$record['dlsID']]['dlsDatas'])) {
					$allRecords[$record['dlsID']] = $record;
				}
			}
			else {
				$allRecords[$record['dlsID']] = $record;
			}
		}

		$allRecords = ArrayUtility::mapByColumn($allRecords, 'dlsTime');
		krsort($allRecords);
		$allRecords = array_values($allRecords);
		if (count($allRecords) > 99) {
			$allRecords = array_slice($allRecords, 0, 99);
		}

		return json_encode($allRecords, JSON_UNESCAPED_UNICODE);

	}

	public function getDictionaryRecordByUserId($userId){
		$result = $this->em->getRepository(EMDictionary::class)->findOneBy(array('userId' => $userId));
		return $result;
	}

	public function addDictionaryRecord($userId, $record){

		/** @var EMDictionary $item */
		$item = $this->getDictionaryRecordByUserId($userId);

		if(isset($item)){
			$records = json_decode($record, true);
			$records = empty($records) ? array() : $records;
			$existRecords = json_decode($item->record, true);
			$existRecords = empty($existRecords) ? array() : $existRecords;
			$newRecords = array_unique(array_merge($existRecords, $records));
			$record = json_encode(array_values($newRecords));
			$item->record = $record;
		} else {

			$item = new EMDictionary();
			$item->userId = $userId;
			$item->record = $record;
		}

		$this->em->persist($item);
		$this->em->flush();
	}

	public function addPaymentComment($userId, $purchasedId, $productId, $storyId, $comment){

		$item = $this->getPaymentComment($purchasedId);

		if(empty($item)){

			$item = new EMPaymentComment();
			$item->userId = $userId;
			$item->productPurchaseId = $purchasedId;
			$item->productId = $productId;
			$item->storyId = $storyId;
		}

		$item->commentTime = new \DateTime('now');
		$item->comment = $comment;

		$this->em->persist($item);
		$this->em->flush();
	}

	public function getLatestPaymentComment($userId, $storyId){
		$result = $this->em->getRepository(EMPaymentComment::class)->findOneBy(
			array('userId' => $userId, 'storyId' => $storyId), array('commentTime' => 'DESC')
		);
		return $result;
	}

	public function getPaymentComment($purchasedId){
		$result = $this->em->getRepository(EMPaymentComment::class)->findOneBy(array('productPurchaseId' => $purchasedId));
		return $result;
	}

	public function getPaymentCommentByPurchasedIds( $ids = array()) {
		$results = $this->em->createQueryBuilder()
			->select('c')
			->from(EMPaymentComment::class, 'c')
			->where('c.productPurchaseId IN (:ids)')
			->setParameter('ids', $ids)
			->getQuery()->getResult();

		$comments = ArrayUtility::mapByColumn($results, 'productPurchaseId');

		return $comments;
	}

	public function getPictureRecordByUserId($userId){
		$result = $this->em->getRepository(EMPictureRecord::class)->findOneBy(array('userId' => $userId));
		return $result;
	}

	public function addPictureRecord($userId, $record){

		/** @var EMPictureRecord $item */
		$item = $this->getPictureRecordByUserId($userId);

		if(isset($item)){
			$records = json_decode($record, true);
			$records = empty($records) ? array() : $records;
			$existRecords = json_decode($item->record, true);
			$existRecords = empty($existRecords) ? array() : $existRecords;
			$newRecords = array_unique(array_merge($existRecords, $records));
			$record = json_encode(array_values($newRecords));
			$item->record = $record;
		} else {

			$item = new EMPictureRecord();
			$item->userId = $userId;
			$item->record = $record;
		}

		$this->em->persist($item);
		$this->em->flush();
	}

	public function getGameRecordByUserId($userId){
		$result = $this->em->getRepository(EMGameRecord::class)->findOneBy(array('userId' => $userId));
		return $result;
	}

	public function addGameRecord($userId, $path){

		$item = new EMGameRecord();
		$item->userId = $userId;
		$item->path = $path;

		$this->em->persist($item);
		$this->em->flush();
	}


	/**
	 * @param $userId
	 * @param $storyId
	 *
	 * @return array
	 */
	public function getPromotionCodeListForStory($userId, $storyId){

		$sql = 'SELECT *
				FROM ciyo_extramessage.em_promotion_code
				WHERE product_id IN (SELECT product_id FROM ciyo_extramessage.product_promotion_config WHERE story_id='.$storyId.') 
				AND user_id='.$userId.'
				ORDER BY create_time DESC';
		$stat = $this->em->getConnection()->executeQuery($sql);
		return $stat;
	}

	/**
	 * @param $userId
	 * @param $productId
	 *
	 * @return array
	 */
	public function getPromotionCodeList($userId, $productId){

		$results = $this->em->getRepository(EMPromotionCode::class)->findBy(
			array(
				'userId' => $userId,
				'productId' => $productId
			)
		);

		return $results;
	}

	public function getPromotionCodeRecordForUser($userId, $productId){

		$result = $this->em->getRepository(EMPromotionCode::class)->findOneBy(
			array(
				'userId' => $userId,
				'productId' => $productId
			)
		);

		return $result;
	}

	public function getPromotionCodeRecord($code){

		$result = $this->em->getRepository(EMPromotionCode::class)->findOneBy(
			array(
				'code' => $code
			)
		);

		return $result;
	}

	public function getPromotionCodeAppStoreTransationId($transationId){

		$result = $this->em->getRepository(EMPromotionCode::class)->findOneBy(
			array(
				'appStoreTransationId' => $transationId
			)
		);

		return $result;
	}

	public function getPromotionCodeTransationId($transationId){

		$result = $this->em->getRepository(EMPromotionCode::class)->findOneBy(
			array(
				'transationId' => $transationId
			)
		);

		return $result;
	}

	public function deletePromotionCode($transationId){

		$conn = $this->em->getConnection();
		try {
			$conn->beginTransaction();
			$deleteSql = 'DELETE FROM ciyo_extramessage.em_promotion_code WHERE transation_id = ?';
			$conn->executeUpdate($deleteSql, array($transationId), array(\PDO::PARAM_INT));
			$conn->commit();
		} catch (\Exception $e) {
			$conn->rollBack();
			throw $e;
		}
	}

	public function generatePromotionCode($userId, $productId, $transationId, $appStoreTransationId)
	{
		do {
			$promotionCodes = $this->em->getRepository(EMPromotionCode::class)
				->findBy(['preGen' => 1], ['id' => 'ASC'], 1);
			/** @var EMPromotionCode $promotionCode */
			$promotionCode = !empty($promotionCodes) ? $promotionCodes[0] : null;
			if (!$promotionCode) {
				$this->preGeneratePromotionCode(10);
			}
			else {
				$query = $this->em->createQueryBuilder()
				                  ->update(EMPromotionCode::class, 'p')
				                  ->where('p.id = :id')
				                  ->andWhere('p.preGen = 1')
				                  ->set('p.preGen', 0)->getQuery();
				$result = $query->execute(['id' => $promotionCode->id]);
				if (!$result) {
					$promotionCode = null;
				}
			}
		} while ($promotionCode == null);

		$promotionCode->userId = $userId;
		$promotionCode->productId = $productId;
		$promotionCode->transationId = $transationId;
		$promotionCode->appStoreTransationId = $appStoreTransationId;
		$promotionCode->createTime = new \DateTime('now');
		$this->em->flush($promotionCode);

		return $promotionCode->code;

//		$code = $this->generateRandomString2();
//
//		$promotionCode = new EMPromotionCode();
//		$promotionCode->userId = $userId;
//		$promotionCode->productId = $productId;
//		$promotionCode->transationId = $transationId;
//		$promotionCode->appStoreTransationId = $appStoreTransationId;
//		$promotionCode->code = $code;
//		$promotionCode->createTime = new \DateTime('now');
//		$promotionCode->vendor = '';
//		$promotionCode->vendorRecordId = 0;
//
//		$this->em->persist($promotionCode);
//		$this->em->flush();
//
//		return $code;
	}

	public function preGeneratePromotionCode( $count ) {

		if (!$count) {
			return;
		}

		$total = 0;
		while ($count) {

			if (!$this->em->isOpen()) {
				$this->doctrine->resetEntityManager();
				$this->em = $this->doctrine->getManager();
			}

			$count--;
			try {

				$code = StringUtility::generateRandomString(10);
				$promotionCode = new EMPromotionCode();
				$promotionCode->userId = 0;
				$promotionCode->productId = 0;
				$promotionCode->transationId = 0;
				$promotionCode->appStoreTransationId = 0;
				$promotionCode->code = $code;
				$promotionCode->createTime = new \DateTime('now');
				$promotionCode->preGen = true;
				$this->em->persist($promotionCode);

				if ($total % 1000 == 0) {
					$this->em->flush();
					$this->em->clear();
				}

//				$sql = 'INSERT INTO
//						ciyo_extramessage.em_promotion_code (user_id, product_id, transation_id, app_store_transation_id, code, create_time, pre_gen)
//						VALUES (?, ?, ?, ?, ?, ?, ?)';
//				$this->em->getConnection()->executeUpdate(
//					$sql,
//					[0, 0, 0, 0, $code, (new \DateTime('now'))->format('Y-m-d H:i:s'), 1],
//					[\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_INT]
//				);

			} catch (\Exception $e) {
				continue;
			}


			$total++;
		}

		$this->em->flush();
		$this->em->clear();

		return $total;
	}

	public function generateRandomString2()
	{
		$code = '';
		while (true) {
			$code = date('Ymd') .substr(time(), -5) . substr(microtime(), 2, 5);
			$exist = $this->memcache->get('em_promotion_code_' . $code);
			if (!$exist) {
				break;
			}
		}
		$this->memcache->set('em_promotion_code_' . $code, true, 0, 1);
		$code = base_convert($code, 10, 36);

		return $code;
	}

	private function generateRandomString($length) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	public function makePromotionCodeReceive($promotionCodeRecord, $userId){

		try {
			$this->em->beginTransaction();

			$this->purchaseRecord($userId, 0, $promotionCodeRecord->productId, '0.00', '', $promotionCodeRecord->id);

			$promotionCodeRecord->receiverId = $userId;
			$promotionCodeRecord->receiveTime = new \DateTime('now');

			$this->em->persist($promotionCodeRecord);
			$this->em->flush($promotionCodeRecord);

			$this->em->commit();

		} catch (\Exception $e) {
			$this->em->rollback();
			throw $e;
		}
	}

	private function purchaseRecord($payer, $transactionId, $productId, $totalFee, $appstoreTransactionId = '', $promotionCodeId = 0, \DateTime $purchaseTime = null) {
		if (!$purchaseTime) {
			$purchaseTime = new \DateTime();
		}
		$sql = 'INSERT INTO 
				ciyo_payment.payment_product_purchased(payer, transaction_id, product_id, appstore_transaction_id, promotion_code_id, total_fee, purchase_time)
				VALUES(?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE product_id = product_id';
		$this->em->getConnection()->executeUpdate(
			$sql,
			array($payer, $transactionId, $productId, $appstoreTransactionId, $promotionCodeId, $totalFee, $purchaseTime->format('Y-m-d H:i:s')),
			array(\PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR)
		);
	}

	public function findClientVersion($type){

		$result = $this->em->getRepository(EMClientVersion::class)->findOneBy(array( 'type' => $type ));

		return $result;
	}

	/**
	 * @param $userId
	 * @param $storyId
	 *
	 * @return null|EMPaymentRanking
	 */
	public function getUserPaymentRanking( $userId, $storyId ) {
		return $this->em->getRepository(EMPaymentRanking::class)
			->findOneBy(['userId' => $userId, 'storyId' => $storyId]);
	}

	public function updatePaymentRanking($userId, $storyId, $totalFee){

		if($totalFee > 0){

			$sql = 'INSERT INTO 
				ciyo_extramessage.em_payment_ranking(user_id, story_id, total_fee)
				VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE total_fee = ?';
			$this->em->getConnection()->executeUpdate(
				$sql,
				array($userId , $storyId, $totalFee, $totalFee),
				array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR)
			);
		}
	}


	/**
	 * @param $storyId
	 *
	 * @return array
	 */
	public function getPaymentRanking($storyId, $count){

//		$results = $this->em->getRepository(EMPaymentRanking::class)->findBy(
//			array('storyId' => $storyId), array('totalFee' => 'DESC'), $count, 0
//		);
//
//		return $results;

		$mutedUserIdStr = implode(',', $this->rankingMutedUserIds);

		$sql = "SELECT epr.user_id AS user_id, eu.nickname AS nickname, eu.avatar_url AS avatar_url, 
					   eu.gender AS gender, eu.age AS age, epr.total_fee AS total_fee
				FROM ciyo_extramessage.em_payment_ranking AS epr 
				INNER JOIN ciyo_extramessage.em_user AS eu
				ON epr.user_id = eu.id
				WHERE epr.story_id = {$storyId} AND eu.id NOT IN ({$mutedUserIdStr}) 
				ORDER BY epr.total_fee DESC LIMIT 0, {$count}";
		$stat = $this->em->getConnection()->executeQuery($sql);
		return $stat;
	}

	/**
	 * @param $storyId
	 *
	 * @return array
	 */
	public function getRecentPaymentRanking($storyId, $count)
	{

		$mutedUserIdStr = implode(',', $this->rankingMutedUserIds);
		$sql = "SELECT ppp.id AS purchase_id, ppp.payer AS payer, eu.nickname AS nickname, eu.avatar_url AS avatar_url, 
					   eu.gender AS gender, eu.age AS age, ppp.total_fee AS total_fee
				FROM ciyo_payment.payment_product_purchased AS ppp 
				INNER JOIN ciyo_extramessage.em_user AS eu
				ON ppp.payer = eu.id
				WHERE eu.id NOT IN ({$mutedUserIdStr}) AND ppp.total_fee > 0 AND ppp.product_id IN (SELECT product_id FROM ciyo_extramessage.product_promotion_config WHERE story_id='.$storyId.')
				ORDER BY ppp.purchase_time DESC LIMIT 0, {$count}";
		$stat = $this->em->getConnection()->executeQuery($sql);
		return $stat;
	}

	/**
	 * @param $productId
	 *
	 * @return null|ProductPromotionConfig
	 */
	public function getPromotionConfig($productId){

		/** @var ProductPromotionConfig | null $result */
		$result = $this->em->getRepository(ProductPromotionConfig::class)->findOneBy(
			array(
				'productId' => $productId
			)
		);

		return $result;
	}
    public function saveEntity($item){
        $this->em->persist($item);
        $this->em->flush();
    }

	public function listPromotionCodeVendorRecords() {
		return $this->em->getRepository(EMPromotionCodeVendorRecord::class)
			->findBy([], ['createTime' => 'DESC']);
    }

	public function listAllPromotionCode( $vendorRecordId ) {
    	return $this->em->getRepository(EMPromotionCode::class)
		    ->findBy(['vendorRecordId' => $vendorRecordId], ['id' => 'ASC']);
    }

	public function iteratePromotionCode($vendor = null, $vendorRecordId = null, $order = 'DESC' )
	{
		$repo = $this->em->getRepository(EMPromotionCode::class);
		$qb = $repo->createQueryBuilder('repo');
		if ('ASC' === $order) {
			$qb->where("repo.id > :cursor");
		} else {
			$qb->where("repo.id < :cursor");
			$order = 'DESC';
		}

		if (!is_null($vendor)) {
			$qb->andWhere('repo.vendor = :vendor')->setParameter('vendor', $vendor);
		}

		if (!is_null($vendorRecordId)) {
			$qb->andWhere('repo.vendorRecordId = :recordId')->setParameter('recordId', $vendorRecordId);
		}
		$qb->andWhere('repo.preGen = 0');

		$query = $qb->orderBy("repo.id", $order)->getQuery();

		return new QueryCursorableIterator($query, 'id');
	}

	public function iteratePromotionCodeByKeyword( $keyword ) {

		$repo = $this->em->getRepository(EMPromotionCode::class);
		$qb = $repo->createQueryBuilder('repo');
		$qb->where("repo.code LIKE :keyword")
		   ->setParameter('keyword', '%'.$keyword.'%');
		$qb->andWhere("repo.id < :cursor");
		$qb->andWhere("repo.preGen = 0");
		$query = $qb->orderBy("repo.id", 'DESC')->getQuery();

		return new QueryCursorableIterator($query, 'id');

//		return $this->iterateEntityByKeyword($this->em, EMPromotionCode::class, $keyword, 'code', 'id', 'DESC');
	}
}