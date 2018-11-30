<?php
/**
 * Created by PhpStorm.
 * User: Jison
 * Date: 16/3/31
 * Time: 下午2:29
 */

namespace Lychee\Module\Account;

use Lychee\Module\Account\Entity\UserSignInRecord;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManager;

class AccountSignInRecorder {

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param RegistryInterface $registry
     */
    public function __construct($registry) {
        $this->em = $registry->getManager();
    }

    /**
     * @param int $userId
     * @param string|null $os
     * @param string|null $osVersion
     * @param string|null $deviceType
     * @param string|null $deviceId
     * @param string|null $clientVersion
     */
    public function record($userId, $os = null, $osVersion = null, $deviceType = null,
                           $deviceId = null, $clientVersion = null) {
        $sql = 'INSERT INTO user_sign_in(user_id, time, os, os_version, device_type, device_id, client_version)'
            .' VALUES(?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE time = VALUES(time), os = VALUES(os),'
            .' os_version = VALUES(os_version), device_type = VALUES(device_type), device_id = VALUES(device_id),'
            .' client_version = VALUES(client_version)';
        $dtFormat = $this->em->getConnection()->getDatabasePlatform()->getDateTimeFormatString();
        $timeStr = (new \DateTime())->format($dtFormat);
        $this->em->getConnection()->executeUpdate($sql, array($userId, $timeStr, $os, $osVersion, $deviceType,
            $deviceId, $clientVersion));
    }

    /**
     * @param int $userId
     * @return UserSignInRecord|null
     */
    public function getUserRecord($userId) {
        return $this->em->find(UserSignInRecord::class, $userId);
    }

}