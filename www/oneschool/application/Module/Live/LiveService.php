<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 18/11/2016
 * Time: 1:06 PM
 */

namespace Lychee\Module\Live;


use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use Lychee\Bundle\ApiBundle\Error\CommonError;
use Lychee\Bundle\ApiBundle\Error\ErrorsException;
use Lychee\Bundle\ApiBundle\Error\LiveError;
use Lychee\Module\Live\Entity\JingXuanInkeLive;
use Lychee\Module\Live\Entity\LiveGiftsPurchased;
use Lychee\Module\Live\Entity\LiveInkeInfo;
use Lychee\Module\Live\Entity\LivePizusUser;
use Lychee\Module\Live\Entity\LiveRecord;
use Lychee\Module\Live\Entity\PizusLiveInfo;
use Symfony\Component\Validator\Constraints\DateTime;

class LiveService {

	/**
	 * @var \PDO
	 */
	private $conn;

	/**
	 * @var EntityManager
	 */
	private $em;

	public function __construct(Registry $registry, $entityName) {
		$this->conn = $registry->getConnection($entityName);
		$this->em = $registry->getManager($entityName);
	}

	public function bindPizusId($userId, $pid) {
		$repo = $this->em->getRepository(LivePizusUser::class);
		$result = $repo->findOneBy([
			'pizusId' => $pid
		]);
		if ($result) {
			throw new ErrorsException(LiveError::PizusAccountHasBeenBound());
		}
		$sql = 'INSERT INTO ciyo_live.live_pizus_user(user_id, pizus_id) VALUES(:userId, :pizusId) ON DUPLICATE KEY UPDATE pizus_id=:pizusId';
		$stmt = $this->conn->prepare($sql);
		$stmt->execute([
			':userId' => $userId,
			':pizusId' => $pid
		]);
	}

	public function fetchPizusId($userId) {
		$stmt = $this->conn->prepare('SELECT * FROM ciyo_live.live_pizus_user WHERE user_id=:userId');
		$stmt->bindValue(':userId', $userId);
		$stmt->execute();
		$result = $stmt->fetch();
		if (!$result) {
			return '0';
		} else {
			return $result['pizus_id'];
		}
	}

	public function fetchUserIdByPid($pid) {
		/** @var LivePizusUser|null $userLive */
		$userLive = $this->em->getRepository(LivePizusUser::class)->findOneBy([
			'pizusId' => $pid
		]);
		if (!$userLive) {
			throw new ErrorsException(LiveError::UserNotExist());
		}

		return $userLive->userId;
	}

	public function giftPurchasedRecord($tid, $userId, $pid, $gifts, $unitPrice, $count, $price) {
		$giftPurchased = new LiveGiftsPurchased();
		$giftPurchased->transactionId = $tid;
		$giftPurchased->userId = $userId;
		$giftPurchased->pizusId = $pid;
		$giftPurchased->giftJson = $gifts;
		$giftPurchased->unitPrice = $unitPrice;
		$giftPurchased->giftCount = $count;
		$giftPurchased->price = $price;
		$giftPurchased->purchasedTime = new \DateTime();
		$this->em->persist($giftPurchased);
		$this->em->flush();
	}

	public function deductCoin($userId, $price) {
		$statement = $this->conn->query('SELECT ciyo_coin FROM user WHERE id='.$this->conn->quote($userId));
		if ($statement) {
			$result = $statement->fetch();
			$coin = $result['ciyo_coin'];
			if ($coin < $price) {
				throw new ErrorsException(LiveError::InsufficientBalance());
			}
		}
		$stmt = $this->conn->prepare('UPDATE user SET ciyo_coin=ciyo_coin-:price WHERE id=:userId');
		$stmt->bindValue(':price', $price, \PDO::PARAM_INT);
		$stmt->bindValue(':userId', $userId);
		if (false === $stmt->execute()) {
			throw new ErrorsException(CommonError::SystemError());
		}
	}

	public function isTransactionExist($tid) {
		$livePurchasedRepo = $this->em->getRepository(LiveGiftsPurchased::class);
		$transaction = $livePurchasedRepo->findOneBy([
			'transactionId' => $tid
		]);

		if ($transaction) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param $pid
	 *
	 * @return null|\Psr\Http\Message\StreamInterface
	 */
	public function getPizusLiveInfo($pid) {
//		$pizusHost = 'apptest.pizus.com';
		$pizusHost = 'app.pizus.com';
		$client = new Client([
			'base_uri' => 'http://'.$pizusHost,
			'timeout' => 10,
			'allow_redirects' => true
		]);
		$uri = '/pizus/getOnlineSingleLive';
		$params = [
			'pid' => $pid
		];
		try {
			$res = $client->request('GET', $uri.'?'.http_build_query($params));
		} catch (\Exception $e) {
			return null;
		}
		$statusCode = $res->getStatusCode();
		if ($statusCode == '200') {
			$responseBody = $res->getBody();

			return json_decode($responseBody->getContents(), true);
		}

		return null;
	}

	public function saveLiveInfo($pid, $response) {
		$liveInfo = new PizusLiveInfo();
		$liveInfo->pizusId = $pid;
		$liveInfo->liveId = $response['id'];
		$liveInfo->anchorName = $response['name'];
		$liveInfo->anchorAvatar = $response['userPhotoPath'];
		$liveInfo->liveCover = $response['liveImagePath'];
		$liveInfo->recommendedTime = new \DateTime();
		$this->em->persist($liveInfo);
		$this->em->flush();
		return $liveInfo;
	}

	public function fetchLiveHistoryByPage($page, $count = 20) {
		$offset = ($page - 1) * $count;
		$repo = $this->em->getRepository(PizusLiveInfo::class);
		$query = $repo->createQueryBuilder('l')
			->orderBy('l.id', 'DESC')
			->setMaxResults($count)
			->setFirstResult($offset)
			->getQuery();

		return $query->getResult();
	}

	/**
	 * @param int $countPerPage
	 *
	 * @return float
	 */
	public function getLiveHistoryPages($countPerPage = 20) {
		$query = $this->em->createQuery('SELECT COUNT(l.id) total FROM '.PizusLiveInfo::class.' l');
		$result = $query->getResult();
		return ceil($result[0]['total'] / $countPerPage);
	}

	public function getLiveHistoryByDate(\DateTime $date, $page, $count, &$total) {
		$offset = ($page - 1) * $count;
		$tomorrow = clone $date;
		$tomorrow = $tomorrow -> modify('tomorrow');
		$repo = $this->em->getRepository(PizusLiveInfo::class);
		$query = $repo->createQueryBuilder('l')
			->orderBy('l.id', 'DESC')
			->where('l.recommendedTime >= :tody')
			->andWhere('l.recommendedTime < :tomorrow')
			->setParameters(array('tody' => $date, 'tomorrow' => $tomorrow));
		$total = count($query->getQuery()->getResult());
		$result = $query->setMaxResults($count)
			->setFirstResult($offset)
			->getQuery()
			->getResult();
		return $result;
	}

	/**
	 * @return null|PizusLiveInfo
	 */
	public function getRecommendedLive() {
		$liveInfo = $this->em->getRepository(PizusLiveInfo::class)
			->findOneBy([], [
				'id' => 'DESC'
			]);

		return $liveInfo;
	}

	public function deleteLiveRecommendationById($id) {
		$liveInfo = $this->em->getRepository(PizusLiveInfo::class)
			->find($id);
		if ($liveInfo) {
			$this->em->remove($liveInfo);
			$this->em->flush();
		}
	}
	public function findOneInkeById($id) {
		return $this->em->getRepository(LiveInkeInfo::class)->find($id);
	}
	public function createInkeLiveRecommendation($uid) {
		$historyInke = $this->findOneInkeById($uid);
		if ($historyInke) {
			return 0;
		}
		/** @var LiveInkeInfo $inke */
		$inke = new LiveInkeInfo();
		$inke->inkeUid = $uid;
		$inke->createTime = new \DateTime();
		$inke->top = 0;
		$this->em->persist($inke);
		$this->em->flush();
		return 1;
	}

	public function fetchInkeHistoryByPage($page, $count = 20, &$total) {
		$offset = ($page - 1) * $count;
		$repo = $this->em->getRepository(LiveInkeInfo::class);
		$query = $repo->createQueryBuilder('li')
			->orderBy('li.top', 'DESC')
			->addOrderBy('li.updateTime', 'DESC')
			->addOrderBy('li.inkeUid', 'DESC');
		$total = count($query->getQuery()->getResult());
		$result =
			$query->setFirstResult($offset)
			->setMaxResults($count)
			->getQuery()
			->getResult();
		return $result;
	}

	public function deleteInkeLiveRecommendationById($id) {
		$inke = $this->findOneInkeById($id);
		if ($inke) {
			$this->em->remove($inke);
			$this->em->flush();
		}
	}

	/**
	 * @return array
	 */
	public function fetchAllInkeUid() {
		$result = $this->em->getRepository(LiveInkeInfo::class)->findAll();
		return array_map(function($info) {
			return $info->inkeUid;
		}, $result);
	}

	public function stickInkeLiveRecommendationById($id, $top) {
		/** @var LiveInkeInfo $inke */
		$inke = $this->findOneInkeById($id);
		if ($inke) {
			$inke->top = $top;
			if ($top) {
				$updateTime = new \DateTime();
			}
			else {
				$updateTime = null;
			}
			$inke->updateTime = $updateTime;
			$this->em->flush();
		}
	}

	/**
	 * @return array
	 */
	public function fetchAllStickyInkeLiveREcommendation() {
		return $this->em->getRepository(LiveInkeInfo::class)->findBy([
			'top' => 1
		], [
			'updateTime' => 'DESC'
		]);
	}
	public function findOneJingxuanById($id) {
		$item = $this->em->getRepository(JingXuanInkeLive::class)->find($id);

		return $item;
	}
	public function addJingxuanLive($uid, $nikename, $avatarUrl, $coverUrl, $description, \DateTime $start, \DateTime $end) {

		if ($this->findOneJingxuanById($uid)) {
			return false;
		}
		$new_item = new JingXuanInkeLive();
		$new_item->inkeUid = $uid;
		$new_item->avatar = $avatarUrl;
		$new_item->nikename = $nikename;
		$new_item->cover = $coverUrl;
		$new_item->description = $description;
		$new_item->startTime = $start;
		$new_item->endTime = $end;
		$this->em->persist($new_item);
		$this->em->flush();
		return true;
	}

	public function getJingxuanLive($count, $page, &$total) {
		$cursor = $count * ($page - 1);
		$query = $this->em->getRepository(JingXuanInkeLive::class)->createQueryBuilder('jx')
			->orderBy('jx.startTime', 'ASC');
		$total = count($query->getQuery()->getResult());
		$result = $query->setFirstResult($cursor)
			->setMaxResults($count)
			->getQuery()
			->getResult();
		if (!$result) {
			return array();
		}
		return $result;
	}

	public function deleteJingxuanLive($id) {
		$item = $this->findOneJingxuanById($id);
		if ($item) {
			$this->em->remove($item);
			$this->em->flush();
		}
	}

	public function modifyJingxuanLive($id, $nikename, $avatar, $cover, $description, \DateTime $start, \DateTime $end) {
		/** @var JingXuanInkeLive $item */
		$item = $this->findOneJingxuanById($id);
		if ($item) {
			$item->inkeUid = $id;
			$item->nikename = $nikename;
			$item->description = $description;
			$item->startTime = $start;
			$item->endTime = $end;
			if ($avatar) {
				$item->avatar = $avatar;
			}
			if ($cover) {
				$item->cover = $cover;
			}
			$this->em->flush();
		}
	}

	public function getOneJingxuanLiveByDate(\DateTime $now) {
		$now = $now->format('Y-m-d H:i:s');
		$result = $this->em->getRepository(JingXuanInkeLive::class)->createQueryBuilder('jx')
			->where(':now > jx.startTime')
			->andWhere(':now < jx.endTime')
			->orderBy('jx.startTime', 'ASC')
			->setParameter(':now', $now)
			->getQuery()
			->getResult();
		if (!$result) {
			return null;
		}
		return $result[0];
	}

	public function addLiveRecord($userId, $startTime, $duration) {
		$liveRecord = new LiveRecord();
		$liveRecord->userId = $userId;
		$liveRecord->startTime = $startTime;
		$liveRecord->duration = $duration;

		$this->em->persist($liveRecord);
		$this->em->flush();
	}
}