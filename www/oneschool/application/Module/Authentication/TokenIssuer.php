<?php
namespace app\module\authentication;

use app\module\authentication\exception\NoResultException;
use app\module\authentication\model\AuthToken;
use \think\Facade\Cache;

class TokenIssuer {
	
	/**
	 * @param RegistryInterface $registry
	 * @param MemcacheInterface $memcache
	 */
	public function __construct() {}
	
	/**
	 * @param int $userId
	 * @param int $clientId
	 * @param int $time
	 *
	 * @return string
	 */
	private function generateTokenString($userId, $clientId, $time) {
		$idHigh = $userId >> 32;
		$idLow = $userId & 0xffffffff;
		
		$idBin = pack('NN', $idHigh, $idLow);
		$timeBin = pack('N', $time);
		$data = openssl_random_pseudo_bytes(4).$timeBin.$idBin;
		return hash_hmac('sha1', $data, hex2bin('cb1112956101ec930302bf03d1e600ca'));
	}
	
	/**
	 * @param AuthToken $token
	 * @param int $time
	 *
	 * @return bool
	 */
	private function tokenHasExpiredAt($token, $time) {
		return $token['create_time'] + $token['ttl'] < $time;
	}
	
	/**
	 * @param int $userId
	 *
	 * @return AuthToken[]
	 */
	public function getTokensByUser($userId) {
		$repository = $this->em->getRepository(AuthToken::class);
		/** @var AuthToken[] $tokens */
		$tokens = $repository->findBy(array('userId' => $userId));
		$now = time();
		list($expiredTokens, $unexpiredTokens) = ArrayUtility::separate($tokens, function($token)use($now){
			return $this->tokenHasExpiredAt($token, $now);
		});
		
		if (count($expiredTokens) > 0) {
			$expiredIds = array_map(function($token){return $token->id;}, $expiredTokens);
			$deleteQuery = $this->em->createQuery('DELETE '.AuthToken::class.' t WHERE t.id IN (:ids)');
			$deleteQuery->execute(array('ids' => $expiredIds));
		}
		
		return $unexpiredTokens;
	}
	
	/**
	 * @param int $userId
	 * @param int $clientId
	 *
	 * @return AuthToken|null
	 */
	public function getTokenByUserClient($userId, $clientId)
	{
		$where = [
			'user_id' => $userId,
			'client_id' => $clientId
		];
		$token = AuthToken::where($where)->find();
		#$query = $this->em->createQuery('SELECT t FROM '.AuthToken::class.' t WHERE t.userId = :userId AND t.clientId = :clientId ORDER BY t.id DESC' );
		#$query->setParameters(array('userId' => $userId, 'clientId' => $clientId));
		#$query->setMaxResults(1);
		if ($token)
		{
			if ($this->tokenHasExpiredAt($token, time()))
			{
				AuthToken::where($where)->delete();
				#$deleteQuery = $this->em->createQuery('DELETE '.AuthToken::class.' t WHERE t.id = :id');
				#$deleteQuery->execute(array('id' => $token->id));
				return null;
			}
			
			return $token;
		}else{
			return null;
		}
		
	}
	
	/**
	 * @param string $accessToken
	 *
	 * @return AuthToken|null
	 */
	public function getTokenByAccessToken($accessToken) {
		/** @var AuthToken $token */
		$token = Cache::store('redis')->handler()->get('token:'.$accessToken);
		$fromCache = false;
		if ($token === false)
		{
			#$query = $this->em->createQuery('SELECT t FROM '.AuthToken::class.' t WHERE t.accessToken = :accessToken');
			#$query->setParameters(array('accessToken' => $accessToken));
			#$query->setMaxResults(1);
			try {
				#$token = $query->getSingleResult();
				$where = [
					'access_token' =>$accessToken
				];
				$token = AuthToken::where($where)->find();
			} catch (NoResultException $e) {
				return null;
			}
		} else {
			$fromCache = true;
		}
		
		if ($this->tokenHasExpiredAt($token, time()))
		{
			AuthToken::destroy($token->id);
			if ($fromCache) {
				Cache::store('redis')->handler()->del('token:'.$accessToken);
			}
			return null;
		} else {
			if (!$fromCache) {
				Cache::store('redis')->handler()->set('token:'.$accessToken, $token, 4 * 3600);
			}
			return $token;
		}
	}
	
	/**
	 * @param string $accessToken
	 *
	 * @return AuthToken|null
	 */
	public function getTokenByClientId($accessToken, $clientId) {
		/** @var AuthToken $token */
		$token = $this->memcache->get($clientId.'_token:'.$accessToken);
		$fromCache = false;
		if ($token === false) {
			$query = $this->em->createQuery(
				'SELECT t FROM '.AuthToken::class.' t
                WHERE t.accessToken = :accessToken
                AND t.clientId = :clientId');
			$query->setParameters(array('accessToken' => $accessToken));
			$query->setParameters(array('clientId' => $clientId));
			$query->setMaxResults(1);
			try {
				$token = $query->getSingleResult();
			} catch (NoResultException $e) {
				return null;
			}
		} else {
			$fromCache = true;
		}
		
		if ($this->tokenHasExpiredAt($token, time())) {
			$deleteQuery = $this->em->createQuery('DELETE '.AuthToken::class.' t WHERE t.id = :id');
			$deleteQuery->execute(array('id' => $token->id));
			if ($fromCache) {
				$this->memcache->delete('token:'.$accessToken);
			}
			return null;
		} else {
			if (!$fromCache) {
				$this->memcache->set($clientId.'_token:'.$accessToken, $token, 0, 4 * 3600);
			}
			return $token;
		}
	}
	
	/**
	 * @param int $userId
	 * @param int $clientId
	 * @param string $scope
	 * @param string $grantType
	 * @param int $ttl
	 *
	 * @return AuthToken
	 */
	public function issueToken($userId, $clientId, $scope, $grantType, $ttl) {
		$oldUserClientToken = $this->getTokenByUserClient($userId, $clientId);
		$now = time();
		$accessToken = $this->generateTokenString($userId, $clientId, $now);
		$data = [
			'access_token' => $accessToken,
			'user_id' => $userId,
			'create_time' => $now,
			'client_id' => $clientId,
			'grant_type' => $grantType,
			'scope' => $scope,
			'ttl' => $ttl
		];
		$token = AuthToken::create($data);
		if ($oldUserClientToken) { $this->revokeToken($oldUserClientToken); }
		return $token;
	}
	
	/**
	 * @param string $accessToken
	 */
	public function revokeTokenByAccessToken($accessToken) {
		$query = $this->em->createQuery('DELETE '.AuthToken::class.' t WHERE t.accessToken = :accessToken');
		$query->execute(array('accessToken' => $accessToken));
		$this->memcache->delete('token:'.$accessToken);
	}
	
	/**
	 * @param AuthToken $token
	 */
	public function revokeToken($token)
	{
		AuthToken::destroy($token->id);
		Cache::store('redis')->handler()->del('token:'.$token->access_token);
		#$query = $this->em->createQuery('DELETE '.AuthToken::class.' t WHERE t.id = :id');
		#$query->execute(array('id' => $token->id));
		#$this->memcache->delete('token:'.$token->accessToken);
	}
	
	/**
	 * @param int $userId
	 */
	public function revokeTokensByUser($userId) {
		$userTokens = $this->getTokensByUser($userId);
		if (count($userTokens) == 0) {
			return;
		}
		$tokenIds = array_map(function($token){return $token->id;}, $userTokens);
		
		$ids = implode(',', $tokenIds);
		$query = $this->em->createQuery('DELETE '.AuthToken::class.' t WHERE t.id IN ('.$ids.')');
		$query->execute();
		
		foreach ($userTokens as $token) {
			$this->memcache->delete('token:'.$token->accessToken);
		}
	}
}