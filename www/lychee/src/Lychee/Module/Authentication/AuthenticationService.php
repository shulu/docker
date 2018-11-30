<?php
namespace Lychee\Module\Authentication;

use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\NoResultException;
use Lychee\Component\Foundation\HttpUtility;
use Lychee\Module\Authentication\Entity\WechatAuth;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Lychee\Bundle\CoreBundle\Entity\User;
use Lychee\Module\Authentication\Entity\WeiboAuth;
use Lychee\Module\Authentication\Entity\QQAuth;
use Lychee\Module\Authentication\Entity\PasswordAuth;
use Lychee\Component\Foundation\ArrayUtility;
use Psr\Log\LoggerInterface;
use Lychee\Module\Authentication\Entity\AuthToken;

class AuthenticationService {

    const TOKEN_TTL = 2592000;//30 days
    const GRANT_TYPE_EMAIL = 'email';
    const GRANT_TYPE_PHONE = 'phone';
    const GRANT_TYPE_QQ = 'qq';
    const GRANT_TYPE_WEIBO = 'weibo';
    const GRANT_TYPE_WECHAT = 'wechat';

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
        } else {
            $this->createPasswordForUser($userId, $password);
        }
    }

    /**
     * @param int $userId
     * @param string $grantType
     * @return AuthToken
     */
    public function createTokenForUser($userId, $grantType) {
        return $this->tokenIssuer->issueToken($userId, AuthToken::CLIENT_CIYO, null, $grantType, self::TOKEN_TTL);
    }

    /**
     * @param string $accessToken
     *
     * @return int|null
     */
    public function getUserIdByToken($accessToken) {
        $token = $this->tokenIssuer->getTokenByAccessToken($accessToken);
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
        $connection->executeUpdate('INSERT INTO auth_sina_weibo(weibo_uid, user_id) VALUES(?, ?)
          ON DUPLICATE KEY UPDATE user_id = ?',
            array($weiboUid, $userId, $userId), array(\PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT));
    }

    /**
     * @param int $weiboUid
     * @return int|null
     */
    public function getUserIdByWeiboUid($weiboUid) {
        $query = $this->entityManager->createQuery('
            SELECT a.userId FROM '.WeiboAuth::class.' a WHERE a.weiboUid = :weiboUid
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

    /**
     * @param string $openId binary string
     * @param int $userId
     */
    public function registerQQOpenIdWithUserId($openId, $userId) {
        $connection = $this->entityManager->getConnection();
        $connection->executeUpdate('INSERT INTO auth_qq(open_id, user_id) VALUES(?, ?)
          ON DUPLICATE KEY UPDATE user_id = ?',
            array($openId, $userId, $userId), array(\PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT));
    }

    /**
     * @param string $openId binary string
     *
     * @return int|null
     */
    public function getUserIdByQQOpenId($openId) {
        $query = $this->entityManager->createQuery('
            SELECT a.userId FROM '.QQAuth::class.' a WHERE a.openId = :openId
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
     * @param string $token
     * @param string $openId
     * @param string $deviceName
     *
     * @return bool
     */
    public function qqOpenIdIsValid($token, $openId, $deviceName) {
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
            $this->logger->critical('ciyo qq open id valid fail.', array(
                'response' => $response, 'access_token' => $token,
                'openid' => $openId, 'oauth_consumer_key' => $this->qqAppKey, 'client' => $deviceName));
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
        $connection->executeUpdate('INSERT INTO auth_wechat(open_id, user_id) VALUES(?, ?)
          ON DUPLICATE KEY UPDATE user_id = ?',
            array($openId, $userId, $userId), array(\PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT));
    }

    /**
     * @param string $openId
     *
     * @return int|null
     */
    public function getUserIdByWechatOpenId($openId) {
        $query = $this->entityManager->createQuery('
            SELECT a.userId FROM '.WechatAuth::class.' a WHERE a.openId = :openId
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
        $qb->where('t.userId = :userId')->setParameter('userId', $userId);
        $query = $qb->getQuery();
        $query->setMaxResults(1);
        $result = $query->getOneOrNullResult();

        return $result;
    }



    /**
     * 登录后事件，消费消息队列异步执行
     *
     * @param $eventBody
     */
    public function asyncAfterSignIn($eventBody) {
        return true;
    }


    /**
     * 注册后事件，消费消息队列异步执行
     *
     * @param $eventBody
     */
    public function asyncAfterSignUp($eventBody) {
        return true;
    }

}