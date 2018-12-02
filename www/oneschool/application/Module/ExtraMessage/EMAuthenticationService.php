<?php

namespace Lychee\Module\ExtraMessage;

use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\NoResultException;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use Facebook\HttpClients\FacebookCurl;
use Facebook\HttpClients\FacebookCurlHttpClient;
use Lychee\Component\Foundation\HttpUtility;
use Lychee\Module\ExtraMessage\Component\RKFacebookCurlHttpClient;
use Lychee\Module\ExtraMessage\Entity\EMBilibiliAuth;
use Lychee\Module\ExtraMessage\Entity\EMDmzjAuth;
use Lychee\Module\ExtraMessage\Entity\EMFacebookAuth;
use Lychee\Module\ExtraMessage\Entity\EMWechatAuth;
use Lychee\Module\ExtraMessage\Entity\EMWeiboAuth;
use Lychee\Module\ExtraMessage\Entity\EMQQAuth;
use Lychee\Module\ExtraMessage\Entity\EMUser;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Lychee\Component\Foundation\ArrayUtility;
use Psr\Log\LoggerInterface;
use Lychee\Module\Authentication\Entity\AuthToken;
use Lychee\Module\Account\Exception\PhoneDuplicateException;
use Lychee\Module\Account\Exception\EmptyNicknameException;
use Lychee\Module\Authentication\TokenIssuer;
use Doctrine\ORM\EntityManagerInterface;

class EMAuthenticationService
{
//    const TOKEN_TTL = 2592000;//30 days
    const TOKEN_TTL = 5184000;//60 days
    const GRANT_TYPE_EMAIL = EMGrantType::EMAIL;
    const GRANT_TYPE_PHONE = EMGrantType::PHONE;
    const GRANT_TYPE_QQ = EMGrantType::QQ;
    const GRANT_TYPE_WEIBO = EMGrantType::WEIBO;
    const GRANT_TYPE_WECHAT = EMGrantType::WECHAT;
	const GRANT_TYPE_BILIBILI = EMGrantType::BILIBILI;
	const GRANT_TYPE_DMZJ = EMGrantType::DMZJ;
	const GRANT_TYPE_FACEBOOK = EMGrantType::FACEBOOK;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var PasswordEncoderInterface
     */
    private $passwordEncoder;

    private $weiboAppKey;
    private $qqAppKey;

    private $logger;
    private $tokenIssuer;

    /**
     * @param ManagerRegistry $registry
     * @param PasswordEncoderInterface $passwordEncoder
     * @param string $weiboAppKey
     * @param string $qqAppKey
     * @param LoggerInterface $logger
     * @param TokenIssuer $tokenIssuer
     */
    public function __construct(
        $registry, $passwordEncoder,
        $weiboAppKey, $qqAppKey, $logger, $tokenIssuer
    ) {
        $this->entityManager = $registry->getManager($registry->getDefaultManagerName());
        $this->passwordEncoder = $passwordEncoder;
        $this->weiboAppKey = $weiboAppKey;
        $this->qqAppKey = $qqAppKey;
        $this->logger = $logger;
        $this->tokenIssuer = $tokenIssuer;
    }

    /**
     * @param int $userId
     * @param string $password
     */
    public function createPasswordForUser($userId, $password) {
        $salt = base64_encode( openssl_random_pseudo_bytes(32) );
        $passwordEncoded = $this->passwordEncoder->encodePassword($password, $salt);
        $authPassword = new PasswordAuth();
        $authPassword->setUserId($userId);
        $authPassword->setSalt($salt);
        $authPassword->setEncodedPassword($passwordEncoded);
        $this->entityManager->persist($authPassword);
        $this->entityManager->flush();
    }

    /**
     * @param int $userId
     * @param string $password
     * @return bool
     */
    public function isUserPasswordValid($userId, $password) {
        /** @var PasswordAuth $authPassword */
        $authPassword = $this->entityManager->find(PasswordAuth::class, $userId);
        if ($authPassword) {
            $passwordValid = $this->passwordEncoder->isPasswordValid(
                $authPassword->getEncodedPassword(), $password, $authPassword->getSalt()
            );
            if ($passwordValid) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param int $userId
     * @param string $password
     */
    public function updatePasswordForUser($userId, $password) {
        /** @var PasswordAuth $authPassword */
        $authPassword = $this->entityManager->find(PasswordAuth::class, $userId);
        if ($authPassword) {
            $passwordEncoded = $this->passwordEncoder->encodePassword($password, $authPassword->getSalt());
            $authPassword->setEncodedPassword($passwordEncoded);
            $this->entityManager->flush($authPassword);
        }
    }

    /**
     * @param int $userId
     * @param string $grantType
     * @return AuthToken
     */
    public function createTokenForUser($userId, $grantType) {
        return $this->tokenIssuer->issueToken($userId, AuthToken::CLIENT_EXTRA_MESSAGE, null, $grantType, self::TOKEN_TTL);
    }

    /**
     * @param string $accessToken
     *
     * @return int|null
     */
    public function getUserIdByToken($accessToken) {
        $token = $this->tokenIssuer->getTokenByAccessToken($accessToken);
        //$token = $this->tokenIssuer->getTokenByClientId($accessToken, AuthToken::CLIENT_EXTRA_MESSAGE);
        if ($token) {
            return $token->userId;
        } else {
            return null;
        }
    }

    public function refreshToken($token) {

    }

    public function revokeToken($token) {
        $this->tokenIssuer->revokeTokenByAccessToken($token);
    }

    /**
     * @param int $weiboUid
     * @param int $userId
     */
    public function registerWeiboUidWithUserId($weiboUid, $userId) {
        $connection = $this->entityManager->getConnection();
        $connection->executeUpdate('INSERT INTO ciyo_extramessage.em_weibo_auth (weibo_uid, user_id) VALUES(?, ?)',
            array($weiboUid, $userId), array(\PDO::PARAM_STR, \PDO::PARAM_INT));
    }

    /**
     * @param int $weiboUid
     * @return int|null
     */
    public function getUserIdByWeiboUid($weiboUid) {
        $query = $this->entityManager->createQuery('
            SELECT a.userId FROM '.EMWeiboAuth::class.' a WHERE a.weiboUid = :weiboUid
        ')->setMaxResults(1)
        ->setParameters(array('weiboUid' => $weiboUid));
        try {
            $userId = intval($query->getSingleScalarResult());
            return $userId;
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param int $userid
     * @return int|null
     */
    public function getUserByWeiBoUserId($userId) {
        $query = $this->entityManager->createQuery('
            SELECT a FROM '.EMWeiboAuth::class.' a WHERE a.userId = :userId
        ')->setMaxResults(1)
            ->setParameters(array('userId' => $userId));
        try {
            $user = $query->getSingleResult();
            return $user;
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param int $userid
     * @param int $weiboUid
     * @return int|null
     */
    public function modifyUserByUserId($userId,$weiboUid) {
        $query = $this->entityManager->createQuery('
            UPDATE '.EMWeiboAuth::class.' a  SET a.userId = :userid WHERE a.weiboUid = :weiboUid
        ')->setMaxResults(1)
            ->setParameters(array('userId' => $userId,'weiboUid'=>$weiboUid));
        try {
            $user = intval($query->getSingleScalarResult());
            return $user;
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param string $token
     * @param string $weiboUid
     *
     * @return bool
     */
    public function weiboTokenIsValid($token, $weiboUid) {
        $params = array(
            'access_token' => $token
        );
        $url = 'https://api.weibo.com/oauth2/get_token_info';
        $json = HttpUtility::getJson($url, $params, 10, $response);
        if ($json !== null) {
            if ($json['uid'] == $weiboUid && $json['appkey'] == $this->weiboAppKey) {
                return true;
            }
        }
        $this->logger->critical('weibo token valid fail.', array('response' => $response));

        return false;
    }

    public function facebookTokenIsValid($token, $fbUserId, $appid, $secret, $proxy = [])
    {
        $facebook = new Facebook(['app_id' => $appid, 'app_secret' => $secret, 'default_graph_version' => 'v2.10',]);

	    if (!empty($proxy)) {
		    $client = new RKFacebookCurlHttpClient();
		    $client->proxy = $proxy;
		    $facebook->getClient()->setHttpClientHandler($client);
	    }

        try {
            $response = $facebook->get('/me', $token);
        } catch(FacebookResponseException $e) {
            $this->logger->critical('facebook Graph returned an error: ', array('response' => $e->getMessage()));
	        return array(false, null, null);
        } catch(FacebookSDKException $e) {
            $this->logger->critical('Facebook SDK returned an error: ', array('response' => $e->getMessage()));
	        return array(false, null, null);
        }

        $me = $response->getGraphUser();
        if ($fbUserId != $me->getId()) {
            $this->logger->critical('Facebook user id not match: ', array($fbUserId => $me->getId()));
            return array(false, null, null);
        }

       $this->logger->info('Facebook user info: ', [$me->getId(), $me->getName(), $me->getPicture()]);
        return array(true, $me->getName(), $me->getPicture());
    }

    /**
     * @param string $openId binary string
     * @param int $userId
     */
    public function registerQQOpenIdWithUserId($openId, $userId) {
        $connection = $this->entityManager->getConnection();
        $connection->executeUpdate('INSERT INTO ciyo_extramessage.em_qq_auth (open_id, user_id) VALUES(?, ?)',
            array($openId, $userId), array(\PDO::PARAM_STR, \PDO::PARAM_INT));
    }

	/**
	 * @param string $openId binary string
	 * @param int $userId
	 */
	public function registerBilibiliOpenIdWithUserId($openId, $userId) {
		$connection = $this->entityManager->getConnection();
		$connection->executeUpdate('INSERT INTO ciyo_extramessage.em_bilibili_auth (open_id, user_id) VALUES(?, ?)',
			array($openId, $userId), array(\PDO::PARAM_STR, \PDO::PARAM_INT));
	}

	/**
	 * @param string $openId binary string
	 * @param int $userId
	 */
	public function registerDmzjOpenIdWithUserId($openId, $userId) {
		$connection = $this->entityManager->getConnection();
		$connection->executeUpdate('INSERT INTO ciyo_extramessage.em_dmzj_auth (open_id, user_id) VALUES(?, ?)',
			array($openId, $userId), array(\PDO::PARAM_STR, \PDO::PARAM_INT));
	}

    /**
     * @param string $openId binary string
     *
     * @return int|null
     */
    public function getUserIdByQQOpenId($openId) {
        $query = $this->entityManager->createQuery('
            SELECT a.userId FROM '.EMQQAuth::class.' a WHERE a.openId = :openId
        ');
        $query->setMaxResults(1);
        $query->setParameters(array('openId' => $openId));
        try {
            $userId = intval($query->getSingleScalarResult());
            return $userId;
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param string $openId binary string
     *
     * @return int|null
     */
    public function getUserByQQUserId($userId) {
        $query = $this->entityManager->createQuery('
            SELECT a FROM '.EMQQAuth::class.' a WHERE a.userId = :userId
        ');
        $query->setMaxResults(1);
        $query->setParameters(array('userId' => $userId));
        try {
            $user = $query->getSingleResult();
//            var_dump($query->getSQL());exit;
//            var_dump($user);exit;
            return $user;
        } catch (NoResultException $e) {
            return null;
        }
    }

	/**
	 * @param string $uid binary string
	 *
	 * @return int|null
	 */
	public function getUserIdByBilibiliOpenId($openId) {
		$query = $this->entityManager->createQuery('
            SELECT a.userId FROM '.EMBilibiliAuth::class.' a WHERE a.openId = :open_id
        ');
		$query->setMaxResults(1);
		$query->setParameters(array('open_id' => $openId));
		try {
			$userId = intval($query->getSingleScalarResult());
			return $userId;
		} catch (NoResultException $e) {
			return null;
		}
	}

	/**
	 * @param string $uid binary string
	 *
	 * @return int|null
	 */
	public function getUserIdByDmzjOpenId($openId) {
		$query = $this->entityManager->createQuery('
            SELECT a.userId FROM '.EMDmzjAuth::class.' a WHERE a.openId = :open_id
        ');
		$query->setMaxResults(1);
		$query->setParameters(array('open_id' => $openId));
		try {
			$userId = intval($query->getSingleScalarResult());
			return $userId;
		} catch (NoResultException $e) {
			return null;
		}
	}

    /**
     * @param string $uid binary string
     *
     * @return int|null
     */
    public function getUserByDmzjUserId($userId) {
        $query = $this->entityManager->createQuery('
            SELECT a FROM '.EMDmzjAuth::class.' a WHERE a.userId = :userId
        ');
        $query->setMaxResults(1);
        $query->setParameters(array('userId' => $userId));
        try {
            $user = $query->getSingleScalarResult();
            return $user;
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param string $uid binary string
     *
     * @return int|null
     */
    public function getUserByBiliUserId($userId) {
        $query = $this->entityManager->createQuery('
            SELECT a FROM '.EMBilibiliAuth::class.' a WHERE a.userId = :userId
        ');
        $query->setMaxResults(1);
        $query->setParameters(array('userId' => $userId));
        try {
            $user = $query->getSingleResult();
            return $user;
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param string $token
     * @param string $openId
     *
     * @return bool
     */
    public function qqOpenIdIsValid($token, $openId) {
        $params = array(
            'access_token' => $token,
            'oauth_consumer_key' => $this->qqAppKey,
            'openid' => $openId
        );
        $url = 'https://graph.qq.com/user/get_user_info';
        $json = HttpUtility::getJson($url, $params, 10, $response);
        if ($json !== null && $json['ret'] == 0) {
            return true;
        } else {
            $this->logger->critical('extra message qq open id valid fail.', array(
                'response' => $response, 'access_token' => $token,
                'openid' => $openId, 'oauth_consumer_key' => $this->qqAppKey));
            return false;
        }
    }

    /**
     * @param array $uids
     *
     * @return array
     */
    public function fetchUserIdsByWeiboUids($uids) {
        $query = $this->entityManager->createQuery('
            SELECT t.userId, t.weiboUid FROM '.WeiboAuth::class.' t
            WHERE t.weiboUid IN (:uids)
        ');
        $query->setParameters(array('uids' => $uids));
        $result = $query->getArrayResult();
        return ArrayUtility::columns($result, 'userId', 'weiboUid');
    }

    /**
     * @param array $openIds
     *
     * @return array
     */
    public function fetchUserIdsByQQOpenIds($openIds) {
        $query = $this->entityManager->createQuery('
            SELECT t.userId FROM '.QQAuth::class.' t
            WHERE t.openId IN (:openIds)
        ');
        $query->setParameters(array('openIds' => $openIds));
        $result = $query->getArrayResult();
        return ArrayUtility::columns($result, 'userId');
    }

    /**
     * @param string $openId
     * @param int $userId
     */
    public function registerWechatOpenIdWithUserId($openId, $userId) {
        $connection = $this->entityManager->getConnection();
	    $connection->executeUpdate('INSERT INTO ciyo_extramessage.em_wechat_auth (open_id, user_id) VALUES(?, ?)',
		    array($openId, $userId), array(\PDO::PARAM_STR, \PDO::PARAM_INT));

    }

    /**
     * @param string $openId
     * @param int $userId
     */
    public function registerFacebookUserIdWithUserId($openId, $userId) {
        $connection = $this->entityManager->getConnection();
        $connection->executeUpdate('INSERT INTO ciyo_extramessage.em_facebook_auth (open_id, user_id) VALUES(?, ?)',
            array($openId, $userId), array(\PDO::PARAM_STR, \PDO::PARAM_INT));
    }

    /**
     * @param string $openId
     *
     * @return int|null
     */
    public function getUserIdByWechatOpenId($openId) {
        $query = $this->entityManager->createQuery('
            SELECT a.userId FROM '.EMWechatAuth::class.' a WHERE a.openId = :openId
        ')->setMaxResults(1)
            ->setParameters(array('openId' => $openId));
        try {
            $userId = intval($query->getSingleScalarResult());
            return $userId;
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param string $openId
     *
     * @return int|null
     */
    public function getUserIdByFacebookUserId($openId) {
        $query = $this->entityManager->createQuery('
            SELECT a.userId FROM '.EMFacebookAuth::class.' a WHERE a.openId = :openId
        ')->setMaxResults(1)
                                     ->setParameters(array('openId' => $openId));
        try {
            $userId = intval($query->getSingleScalarResult());
            return $userId;
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param string $userId
     *
     * @return int|null
     */
    public function getUserByWechatUserId($userId) {
        $query = $this->entityManager->createQuery('
            SELECT a FROM '.EMWechatAuth::class.' a WHERE a.userId = :userId
        ')->setMaxResults(1)
            ->setParameters(array('userId' => $userId));
        try {
            $user = $query->getSingleResult();
            return $user;
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param string $token
     * @param string $openId
     * @return bool
     */
    public function wechatTokenIsValid($token, $openId) {
        $params = array(
            'access_token' => $token,
            'openid' => $openId
        );
        $url = 'https://api.weixin.qq.com/sns/auth';
        $json = HttpUtility::getJson($url, $params, 10, $response);
        if ($json !== null && $json['errcode'] == 0) {
            return true;
        } else {
            $this->logger->critical('wechat token valid fail.', array(
                'response' => $response, 'access_token' => $token, 'openid' => $openId));
            return false;
        }
    }

    public function getTokenByUserId($userId) {
        $qb = $this->entityManager->getRepository(AuthToken::class)->createQueryBuilder('t');
        $qb->where('t.userId = :userId')->andWhere('t.clientId = :clientId')
            ->setParameter('userId', $userId)
            ->setParameter('clientId', AuthToken::CLIENT_EXTRA_MESSAGE);
        $query = $qb->getQuery();
        $query->setMaxResults(1);
        $result = $query->getOneOrNullResult();

        return $result;
    }

	/**
	 * @param string|null $phone
	 * @param string|null $areaCode
	 * @param string|null $nickname
     * @param string|null $grantType
	 *
	 * @return EMUser
	 * @throws PhoneDuplicateException
	 * @throws \Exception
	 */
	public function createWithPhone($phone = null, $areaCode = null, $nickname = null, $grantType = '') {
		$user = new EMUser();
		$user->createTime = new \DateTime('now');
		$user->phone = $phone;
		$user->areaCode = $areaCode;
		$user->grantType = $grantType;
		if ($nickname == null) {
			throw new EmptyNicknameException();
		} else {
			$user->nickname = $nickname;
		}

		try {
			$this->entityManager->beginTransaction();

			$this->entityManager->persist($user);
			$this->entityManager->flush($user);

			$this->entityManager->commit();

		} catch (UniqueConstraintViolationException $e) {
			$this->entityManager->rollback();
			$constraintName = $this->getConstraintName($e);
			if ($constraintName == 'area_code_phone_udx') {
				throw new PhoneDuplicateException();
			} else {
				throw $e;
			}
		} catch (\Exception $e) {
			$this->entityManager->rollback();
			throw $e;
		}

		return $user;
	}

	public function updateUserInfo($user){

		if ($user->nickname == null) {
			throw new EmptyNicknameException();
		}

		try {
			$this->entityManager->beginTransaction();

			$this->entityManager->persist($user);
			$this->entityManager->flush($user);

			$this->entityManager->commit();

		} catch (UniqueConstraintViolationException $e) {
			$this->entityManager->rollback();
			$constraintName = $this->getConstraintName($e);
			if ($constraintName == 'area_code_phone_udx') {
				throw new PhoneDuplicateException();
			} else {
				throw $e;
			}
		} catch (\Exception $e) {
			$this->entityManager->rollback();
			throw $e;
		}

		return $user;
	}

	/**
	 * @param $id
	 *
	 * @return null|EMUser
	 */
	public function fetchAccount($id){
		return $this->entityManager->getRepository('Lychee\Module\ExtraMessage\Entity\EMUser')->find($id);
	}

    private function getConstraintName(UniqueConstraintViolationException $e) {
        $msg = $e->getPrevious()->getMessage();
        if (preg_match('/for key \'([^\']+)\'/', $msg, $matches) == false) {
            return '';
        } else {
            return $matches[1];
        }
    }

    public function saveEntity($item){
        $this->entityManager->persist($item);
        $this->entityManager->flush();
    }
}