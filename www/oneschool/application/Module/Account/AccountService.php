<?php
namespace app\module\account;

use app\module\account\exception\NicknameGenerationException;
use app\module\account\exception\PhoneDuplicateException;
use app\common\model\User;
use app\common\model\UserCounting;
use think\Cache;
use think\Exception;
use app\module\account\NicknameGenerator;

class AccountService
{
	
	/**
	 * @var Cache
	 */
	private $cacheStorage;
	
	/**
	 * @var CachedDoctrineStorage
	 */
	private $storage;
	
	/**
	 * @var Cache
	 */
	private $countingStorage;
	
	/**
	 * @var MemcacheStorage
	 */
	private $countingCache;
	
	private $profileStorage;
	
	private $searcher;
	private $levelCalculator;
	
	/**
	 * @var \app\common\event\NicknameGenerator
	 */
	private $nicknameGenerator;
	
	/**
	 * @param ManagerRegistry       $registry
	 * @param MemcacheInterface     $memcache
	 * @param EventDispatcherInterface $eventDispatcher
	 * @param Searcher $searcher
	 * @param LevelCalculator $levelCalculator
	 */
	public function __construct()
	{
		#$this->cacheStorage = Cache(['type'  =>  'redis', 'host'	=>	'127.0.0.1', 'port' => 6379,'expire'=>  14400, 'prefix'=>  'user:']);
		$this->cacheStorage = Cache(['type'  =>  'redis', 'host'	=>	'192.168.99.100', 'port' => 6379,'expire'=>  14400, 'prefix'=>  'user:']);
		#$this->countingStorage = Cache(['type'  =>  'redis', 'host'	=>	'127.0.0.1', 'port' => 6379,'expire'=>  0, 'prefix'=>  'user_counting:']);
		$this->countingStorage = Cache(['type'  =>  'redis', 'host'	=>	'192.168.99.100', 'port' => 6379,'expire'=>  0, 'prefix'=>  'user_counting:']);
		$this->nicknameGenerator = new NicknameGenerator();
	}
	
	/**
	 * @param string|null $email
	 * @param string|null $nickname
	 *
	 * @return User
	 * @throws Exception\EmailAndNicknameDuplicateException
	 * @throws Exception\EmailDuplicateException
	 * @throws Exception\NicknameDuplicateException
	 * @throws \Exception
	 */
	public function createWithEmail($email = null, $nickname = null) {
		$user = new User();
		$user->createTime = new \DateTime('now');
		$user->email = $email;
		$user->nickname = $nickname;
		
		try {
			$this->entityManager->beginTransaction();
			$this->entityManager->persist($user);
			$this->entityManager->flush($user);
			
			$counting = new UserCounting();
			$counting->userId = $user->id;
			$this->countingStorage->set($user->id, $counting);
			
			$this->entityManager->commit();
			$this->eventDispatcher->dispatch(AccountEvent::CREATE, new AccountEvent($user->id));
		} catch (\Exception $e) {
			$this->entityManager->rollback();
			
			$emailIsUsed = $this->fetchOneByEmail($email) !== null;
			$nikenameIsUsed = $this->fetchOneByNickname($nickname) !== null;
			if ($emailIsUsed && $nikenameIsUsed) {
				throw new EmailAndNicknameDuplicateException();
			} else if ($emailIsUsed) {
				throw new EmailDuplicateException();
			} else if ($nikenameIsUsed) {
				throw new NicknameDuplicateException();
			} else {
				throw $e;
			}
		}
		
		return $user;
	}
	
	/**
	 * @param string|null $phone
	 * @param string|null $areaCode
	 * @param string|null $nickname
	 *
	 * @return User|mixed
	 * @throws \Exception
	 */
	public function createWithPhone($phone = null, $areaCode = null, $nickname = null)
	{
		$result = User::where (['phone'=>$phone])->find ();
		if (!empty($result)){ throw new PhoneDuplicateException();}
		$needGenerateNickname = false;
		if ($nickname == null) { $needGenerateNickname = true; }
		if ($needGenerateNickname) { $nickname = $this->trySaveWithRandomNickname(3); }
		$user =  User::create ([
			'nickname' => $nickname,
			'create_time' => date('Y-m-d H:i:s'),
			'phone' => $phone,
			'area_code' => $areaCode
		]);
		UserCounting::create ([
			'user_id'=>$user->id,
			'post_count' => 0,
			'image_comment_count'=>0
		]);
		$this->countingStorage->set($user->id, serialize (['user_id'=>$user->id, 'post_count'=>0, 'image_comment_count'=>0]));
		#$this->eventDispatcher->dispatch(AccountEvent::CREATE, new AccountEvent($user->id));
		return $user;
	}
	
	private function getConstraintName(Exception $e) {
		$msg = $e->getPrevious()->getMessage();
		if (preg_match('/for key \'([^\']+)\'/', $msg, $matches) == false) {
			return '';
		} else {
			return $matches[1];
		}
	}
	
	/**
	 * @param User $user
	 * @param int $retryCount
	 * @throws \Exception
	 */
	private function trySaveWithRandomNickname($attempt = 3) {
		$attemptCount = 1;
		#$sql = 'INSERT INTO user(email, phone, area_code, create_time, nickname) VALUES(?, ?, ?, ?, ?)';
		
		while ($attemptCount < $attempt)
		{
			$nickname = $this->nicknameGenerator->generate();
			$result = User::where (['nickname'=>$nickname])->find ();
			if (!$result)
			{
				return $nickname;
			}else{
				$attemptCount += 1;
			}
		}
		throw new NicknameGenerationException('exceed max attempt count.');
	}
	
	
	
	public function formatUserFields($user) {
		if (empty($user)) {
			return null;
		}
		$event = controller('common/ImageUtility', 'event');
		$user->avatarUrl = $event->formatUrl($user->avatarUrl);
		return $user;
	}
	
	/**
	 * @param array $ids
	 *
	 * @return User[]
	 */
	public function fetch($ids) {
		if (count($ids) === 0) {
			return array();
		}
		
		$list =  $this->storage->getMulti($ids);
		foreach ($list as $item) {
			$this->formatUserFields($item);
		}
		return $list;
	}
	
	/**
	 * @param int $id
	 *
	 * @return User|null
	 */
	public function fetchOne($id) {
		//因为缓存会出现跟数据库不一致的情况，这里获取单个账户信息的时候采取不读缓存，直接读数据库的方式
		$return = User::get($id);
		$this->formatUserFields($return);
		return $return;
	}
	
	/**
	 * @param string $email
	 *
	 * @return User|null
	 */
	public function fetchOneByEmail($email) {
		/** @var User $user */
		$user = $this->entityManager
			->getRepository('LycheeCoreBundle:User')
			->findOneBy(array('email' => $email));
		if ($user === null) {
			return null;
		} else {
			$this->cacheStorage->set($user->id, $user);
			return $user;
		}
	}
	
	/**
	 * @param string $areaCode
	 * @param string $phone
	 *
	 * @return User|null
	 */
	public function fetchOneByPhone($areaCode, $phone) {
		/** @var User $user */
		$user = User::where(['area_code' => $areaCode, 'phone' => $phone])->find();
		return $user;
		if ($user === null) {
			return null;
		} else {
			$this->cacheStorage->set($user->id, $user);
			return $user;
		}
	}
	
	/**
	 * @param string $nickname
	 *
	 * @return User|null
	 */
	public function fetchOneByNickname($nickname) {
		/** @var User $user */
		$user = $this->entityManager
			->getRepository('LycheeCoreBundle:User')
			->findOneBy(array('nickname' => $nickname));
		if ($user == null) {
			return null;
		} else {
			$this->cacheStorage->set($user->id, $user);
			return $user;
		}
	}
	
	/**
	 * @param string[] $nicknames
	 *
	 * @return array
	 */
	public function fetchIdsByNicknames($nicknames) {
		$query = $this->entityManager->createQuery('
            SELECT t.id, t.nickname FROM LycheeCoreBundle:User t
            WHERE t.nickname IN (:nicknames)
        ');
		$query->setParameters(array('nicknames' => $nicknames));
		$result = $query->getArrayResult();
		return ArrayUtility::columns($result, 'id');
	}
	
	/**
	 * @param string[] $phones
	 *
	 * @return array
	 */
	public function fetchIdsByPhones($phones) {
		if (count($phones) == 0) {
			return array();
		}
		
		$query = $this->entityManager->createQuery(
			'SELECT t.id FROM LycheeCoreBundle:User t WHERE t.areaCode = \'86\' AND t.phone IN (:phones)'
		);
		$query->setParameters(array('phones' => $phones));
		$result = $query->getArrayResult();
		return ArrayUtility::columns($result, 'id');
	}
	
	public function search($keyword, $cursor, $count, &$nextCursor = null) {
		$userIds = $this->searcher->search($keyword, $cursor, $count);
		if (count($userIds) < $count) {
			$nextCursor = 0;
		} else {
			$nextCursor = $cursor + $count;
		}
		$users = $this->fetch($userIds);
		
		return ArrayUtility::mapByColumn($users, 'id');
	}
	
	public function fetchByKeyword($keyword, $cursor, $count, &$nextCursor = null) {
		if ($count === 0) {
			return array();
		}
		
		$query = $this->entityManager->createQuery('
            SELECT u
            FROM LycheeCoreBundle:User u
            WHERE u.nickname LIKE :keyword
            ORDER BY u.nickname DESC
        ')->setFirstResult($cursor)->setMaxResults($count);
		$users = $query->execute(array('keyword' => '%'. $keyword . '%'));
		
		if (count($users) < $count) {
			$nextCursor = 0;
		} else {
			$nextCursor = $cursor + $count;
		}
		
		return ArrayUtility::mapByColumn($users, 'id');
	}
	
	public function fetchPublicIds($cursor, $count, &$nextCursor = null) {
		if ($count === 0) {
			return array();
		}
		
		$query = $this->entityManager->createQuery('
            SELECT t
            FROM LycheeCoreBundle:PublicUser t
            WHERE t.id > :cursor
            ORDER BY t.id ASC
        ')->setMaxResults($count);
		$users = $query->execute(array('cursor' => $cursor));
		
		if (count($users) < $count) {
			$nextCursor = 0;
		} else {
			$nextCursor = $users[count($users) - 1]->id;
		}
		
		return ArrayUtility::columns($users, 'userId');
	}
	
	/**
	 * @param int $userId
	 * @param string|null $areaCode
	 * @param string|null $phone
	 * @throws PhoneDuplicateException
	 */
	public function updatePhone($userId, $areaCode, $phone) {
		$conn = $this->entityManager->getConnection();
		$sql = 'UPDATE user SET area_code = ?, phone = ? WHERE id = ?';
		try {
			$conn->executeUpdate($sql, array($areaCode, $phone, $userId),
				array(\PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_INT));
		} catch (UniqueConstraintViolationException $e) {
			$constraintName = $this->getConstraintName($e);
			if ($constraintName == 'area_code_phone_udx') {
				throw new PhoneDuplicateException();
			}
		}
	}
	
	/**
	 * @param int $userId
	 * @param int $gender
	 * @param string|null $avatarUrl
	 */
	public function updateInfo($userId, $gender, $avatarUrl, $signature) {
		$conn = $this->entityManager->getConnection();
		$sql = 'UPDATE user SET gender = ?, avatar_url = ?, signature = ? WHERE id = ?';
		$conn->executeUpdate($sql, array($gender, $avatarUrl, $signature, $userId),
			array(\PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_INT));
		$this->cacheStorage->delete($userId);
	}
	
	/**
	 * @param int $userId
	 * @param string $nickname
	 * @throws NicknameDuplicateException
	 */
	public function updateNickname($userId, $nickname) {
		$conn = $this->entityManager->getConnection();
		$sql = 'UPDATE user SET nickname = ? WHERE id = ?';
		try {
			$conn->executeUpdate($sql, array($nickname, $userId),
				array(\PDO::PARAM_STR, \PDO::PARAM_INT));
		} catch (UniqueConstraintViolationException $e) {
			$constraintName = $this->getConstraintName($e);
			if ($constraintName == 'nickname_udx') {
				throw new NicknameDuplicateException();
			}
		}
		$this->eventDispatcher->dispatch(AccountEvent::UPDATE_NICKNAME, new AccountEvent($userId));
		$this->cacheStorage->delete($userId);
	}
	
	/**
	 * @param User $user
	 * @throws NicknameDuplicateException|\Exception
	 */
	private function update($user) {
		try {
			$this->storage->set($user->id, $user);
			//以下两行是为了确保memcache中的缓存更改为新的值
			//$this->entityManager->clear(User::class);
//            $this->entityManager->detach($user);
//            $a = $this->storage->get($user->id);
			$this->eventDispatcher->dispatch(AccountEvent::UPDATE, new AccountEvent($user->id));
		} catch (\Exception $e) {
			$nicknameIsUsed = $this->fetchOneByNickname($user->nickname) !== null;
			if ($nicknameIsUsed) {
				throw new NicknameDuplicateException();
			} else {
				throw $e;
			}
		}
	}
	
	/**
	 * @param int $userId
	 * @param string|null $reason
	 * @return boolean
	 */
	public function freeze($userId, $reason = null) {
		$user = $this->fetchOne($userId);
		if ($user->frozen === true) {
			return true;
		}
		
		try {
			$this->entityManager->beginTransaction();
			
			$frozenUser = $this->entityManager->getRepository(
				'Lychee\Module\Account\FrozenUser'
			)->findOneBy(array('userId' => $userId));
			if ($frozenUser === null) {
				$frozenUser = new FrozenUser();
				$frozenUser->userId = $user->id;
			}
			$frozenUser->time = new \DateTime();
			$frozenUser->reason = $reason;
			
			$this->entityManager->persist($frozenUser);
			$this->entityManager->flush($frozenUser);
			
			$user->frozen = true;
			$this->storage->set($user->id, $user);
			
			$this->entityManager->commit();
			$this->eventDispatcher->dispatch(AccountEvent::FREEZE, new AccountEvent($user->id));
			return true;
		} catch (\Exception $e) {
			$this->entityManager->rollback();
			return false;
		}
	}
	
	/**
	 * @param int $userId
	 * @return boolean
	 */
	public function unfreeze($userId) {
		$user = $this->fetchOne($userId);
		if ($user->frozen === false) {
			return true;
		}
		
		$frozenUser = $this->entityManager->getRepository(
			'Lychee\Module\Account\FrozenUser'
		)->findOneBy(array('userId' => $userId));
		if ($frozenUser === null) {
			return true;
		}
		
		try {
			$this->entityManager->beginTransaction();
			
			$this->entityManager->remove($frozenUser);
			$this->entityManager->flush($frozenUser);
			
			$user->frozen = false;
			$this->storage->set($user->id, $user);
			
			$this->entityManager->commit();
			$this->eventDispatcher->dispatch(AccountEvent::UNFREEZE, new AccountEvent($user->id));
			return true;
		} catch (\Exception $e) {
			$this->entityManager->rollback();
			return false;
		}
	}
	
	/**
	 * @param string $order
	 * @return QueryCursorableIterator
	 */
	public function frozenUserIterator($order = 'ASC') {
		$queryBuilder = $this->entityManager->getRepository('Lychee\Module\Account\FrozenUser')
			->createQueryBuilder('t');
		
		if ('ASC' === $order) {
			$queryBuilder->where('t.id > :cursor')->orderBy('t.id');
		} else {
			$queryBuilder->where('t.id < :cursor')->orderBy('t.id', 'DESC');
		}
		$query = $queryBuilder->getQuery();
		$cursorableIterator = new QueryCursorableIterator($query, 'id');
		
		return $cursorableIterator;
	}
	
	public function increasePostCounter($userId, $delta) {
		$this->increaseCounter($userId, 'postCount', $delta);
	}
	
	public function increaseImageCommentCounter($userId, $delta) {
		$this->increaseCounter($userId, 'imageCommentCount', $delta);
	}
	
	private function increaseCounter($userId, $counterField, $delta) {
		$counting = $this->fetchOneCounting($userId);
		$query = $this->entityManager->createQuery('
            UPDATE LycheeCoreBundle:UserCounting t
            SET t.'.$counterField.' = t.'.$counterField.' + :delta
            WHERE t.userId = :userId
        ')->setParameters(array('userId' => $userId, 'delta' => $delta));
		$query->execute();
		
		$counting->$counterField += $delta;
		$this->entityManager->detach($counting);
		$this->countingCache->set($userId, $counting);
		$this->countingCache->delete($userId);
	}
	
	/**
	 * @param int $id
	 *
	 * @return UserCounting|null
	 */
	public function fetchOneCounting($id) {
		return $this->countingStorage->get($id);
	}
	
	/**
	 * @param array $ids
	 *
	 * @return array
	 */
	public function fetchCountings($ids) {
		return $this->countingStorage->getMulti($ids);
	}
	
	/**
	 * @param UserProfile $userProfile
	 */
	public function updateUserProfile($userProfile) {
		$this->profileStorage->set($userProfile->userId, $userProfile);
	}
	
	/**
	 * @param int $userId
	 *
	 * @return UserProfile|null
	 */
	public function fetchOneUserProfile($userId) {
		$profile = $this->profileStorage->get($userId);
		if ($profile === null) {
			$profile = new UserProfile();
			$profile->userId = $userId;
		}
		return $profile;
	}
	
	/**
	 * @param $userIds
	 * @return UserProfile[]
	 */
	public function fetchUserProfiles($userIds) {
		$profiles = $this->profileStorage->getMulti($userIds);
		$idsWithoutProfile = ArrayUtility::diffValue($userIds, array_keys($profiles));
		foreach ($idsWithoutProfile as $idWithoutProfile) {
			$profile = new UserProfile();
			$profile->userId = $idWithoutProfile;
			$profiles[$idWithoutProfile] = $profile;
		}
		return $profiles;
	}
	
	public function fetchAllUserLocations(){
		
		$query = $this->entityManager->createQuery(
			'SELECT u.location FROM LycheeCoreBundle:UserProfile u WHERE u.location IS NOT NULL'
		);
		
		$result = $query->getArrayResult();
		return ArrayUtility::columns($result, 'location');
	}
	
	/**
	 * @param string $order
	 * @return QueryCursorableIterator
	 */
	public function iterateAccount($order = 'ASC')
	{
		return $this->iterateEntity($this->entityManager, 'LycheeCoreBundle:User', 'id', $order);
	}
	
	/**
	 * @param \DateTime $createTime
	 * @return QueryCursorableIterator
	 */
	public function iterateAccountByCreateTime(\DateTime $createTime)
	{
		return $this->iterateEntityByCreateTime($this->entityManager, User::class, 'id', 'createTime', $createTime);
	}
	
	/**
	 * @param $userId
	 * @return array
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function signInMethod($userId) {
		$signInMethods = [];
		$qqRepo = $this->entityManager->getRepository(QQAuth::class);
		$query = $qqRepo->createQueryBuilder('q')
			->where('q.userId = :userId')
			->setParameter('userId', $userId)
			->setMaxResults(1)
			->getQuery();
		$result = $query->getOneOrNullResult();
		if (null !== $result) {
			return array('QQ');
		}
		
		$weiboRepo = $this->entityManager->getRepository(WeiboAuth::class);
		$query = $weiboRepo->createQueryBuilder('w')
			->where('w.userId = :userId')
			->setParameter('userId', $userId)
			->setMaxResults(1)
			->getQuery();
		$result = $query->getOneOrNullResult();
		if (null !== $result) {
			return array('新浪微博');
		}
		
		$wechatRepo = $this->entityManager->getRepository(WechatAuth::class);
		$query = $wechatRepo->createQueryBuilder('w')
			->where('w.userId = :userId')
			->setParameter('userId', $userId)
			->setMaxResults(1)
			->getQuery();
		$result = $query->getOneOrNullResult();
		
		if (null !== $result) {
			return array('微信');
		}
		
		$passwordRepo = $this->entityManager->getRepository(PasswordAuth::class);
		$query = $passwordRepo->createQueryBuilder('w')
			->where('w.userId = :userId')
			->setParameter('userId', $userId)
			->setMaxResults(1)
			->getQuery();
		$result = $query->getOneOrNullResult();
		
		if (null !== $result) {
			return array('手机号');
		}
		
		return $signInMethods;
	}
	
	/**
	 * @param int $userId
	 * @param int $experience
	 * @param bool $levelup
	 * @return int level
	 */
	public function userGainExperience($userId, $experience, &$levelup = null) {
		/** @var User $user */
		$user = $this->entityManager->find('LycheeCoreBundle:User', $userId, LockMode::PESSIMISTIC_WRITE);
		$this->updateUserExperience($userId, $user->experience + $experience, $levelup);
		return $user->level;
	}
	
	public function updateUserExperience($userId, $experience, &$levelup = null) {
		/** @var User $user */
		$user = $this->entityManager->find('LycheeCoreBundle:User', $userId, LockMode::PESSIMISTIC_WRITE);
		$oldLevel = $user->level;
		$user->experience = $experience;
		$level = $this->levelCalculator->calculate($user->experience);
		if ($level > $user->level) {
			$user->level = $level;
			$this->storage->set($userId, $user);
			$levelup = true;
			$this->eventDispatcher->dispatch(
				LevelUpEvent::NAME, new LevelUpEvent($userId, $oldLevel, $level));
		} else {
			//如果没有升级，则不刷新缓存
			$this->entityManager->flush($user);
			$levelup = false;
		}
		return $user->level;
	}
	
	/**
	 * @param int $userId
	 *
	 * @return int
	 */
	public function getUserExperience($userId) {
		$dql = 'SELECT u.experience FROM LycheeCoreBundle:User u WHERE u.id = ?0';
		$query = $this->entityManager->createQuery($dql);
		$query->setParameter(0, $userId, \PDO::PARAM_INT);
		return $query->getSingleScalarResult();
	}
	
	/**
	 * @param $userId
	 * @return bool
	 */
	public function isAdmin($userId) {
		return in_array($userId, [31721]);
	}
	
	/**
	 * @return int
	 */
	public function getCiyuanjiangID() {
		return 31721;
	}
	
	/**
	 * @param int $page
	 * @param int $count
	 *
	 * @return array
	 */
	public function fetchIdsByPage($page = 1, $count = 20) {
		$query = $this->entityManager->createQuery(
			'SELECT u.id
    		FROM LycheeCoreBundle:User u
    		ORDER BY u.id DESC'
		)->setFirstResult(($page - 1) * $count)->setMaxResults($count);
		
		$result = $query->getArrayResult();
		
		return ArrayUtility::columns($result, 'id');
	}
	
	/**
	 * @return int
	 */
	public function getUserCount() {
		$query = $this->entityManager->createQuery(
			'SELECT COUNT(u.id) user_count
    		FROM LycheeCoreBundle:User u'
		);
		$result = $query->getOneOrNullResult();
		if ($result) {
			return $result['user_count'];
		} else {
			return 0;
		}
	}
	/**
	 * @return int
	 */
	public function getVipCount() {
		$query = $this->entityManager->createQuery(
			'SELECT COUNT(v.id) vip_count
            FROM '.UserVip::class.' v'
		);
		
		$result = $query->getOneOrNullResult();
		if ($result) {
			return $result['vip_count'];
		} else {
			return 0;
		}
	}
	
	/**
	 * @param int $page
	 * @param int $count
	 *
	 * @return array
	 */
	public function fetchVipUsers($page = 1, $count = 20) {
		$query = $this->entityManager->getRepository(UserVip::class)
			->createQueryBuilder('v')
			->orderBy('v.id', 'DESC')
			->setFirstResult(($page - 1) * $count)
			->setMaxResults($count)
			->getQuery();
		
		return $query->getResult();
	}
	
	/**
	 * @param $userId
	 * @param null $certificationText
	 */
	public function addVip($userId, $certificationText = null) {
		$vip = new UserVip();
		$vip->userId = $userId;
		$vip->certificationText = $certificationText;
		try {
			$this->entityManager->persist($vip);
			$this->entityManager->flush();
		} catch (UniqueConstraintViolationException $e) {
		
		}
	}
	
	/**
	 * @param $userId
	 */
	public function deleteVipByUserId($userId) {
		$vip = $this->entityManager->getRepository(UserVip::class)
			->findOneBy([
				'userId' => $userId
			]);
		if ($vip) {
			$this->entityManager->remove($vip);
			$this->entityManager->flush();
		}
	}
	
	
	public function editCertification($userId, $certificationText = null) {
		$em = $this->entityManager;
		/** @var UserVip  $vip_user */
		$vip_user = $em->getRepository(UserVip::class)->findOneBy(['userId' => $userId]);
		if(!$vip_user) {
			return false;
		}
		$vip_user->certificationText = $certificationText;
		$em->flush();
		return true;
	}
	
	public  function queryVipToGetUserIds($keyword, $cursor, $count, &$total = null) {
		$query = $this->entityManager->createQuery(
			'SELECT v FROM '.UserVip::class.' v INNER JOIN LycheeCoreBundle:User u WITH v.userId = u.id
            WHERE u.nickname LIKE :keyword'
		);
		$query->setParameter('keyword','%'.$keyword.'%');
		$total = count($query->getResult());
		$users = $query->setFirstResult($cursor)->setMaxResults($count)->execute();
		
		return ArrayUtility::columns($users, 'userId');
	}
	/**
	 * @param int[] $userIds
	 * @return UserVip[]
	 */
	public function fetchVipInfosByUserIds($userIds) {
		/** @var UserVip[] $infos */
		$infos = $this->entityManager->getRepository(UserVip::class)->findBy(['userId' => $userIds]);
		$result = [];
		foreach ($infos as $info) {
			$result[$info->userId] = $info;
		}
		return $result;
	}
	
	/**
	 * @param $userId
	 *
	 * @return bool
	 */
	public function isUserInVip($userId) {
		$vip = $this->entityManager->getRepository(UserVip::class)->findOneBy([
			'userId' => $userId
		]);
		
		return $vip && true;
	}
	
	/**
	 * @return array
	 */
	public function fetchVips() {
		$userVips = $this->entityManager->getRepository(UserVip::class)->findAll();
		$userVipIds = ArrayUtility::columns($userVips, 'userId');
		
		return $userVipIds;
	}
	
}