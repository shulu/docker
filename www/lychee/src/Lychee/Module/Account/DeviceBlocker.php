<?php
namespace Lychee\Module\Account;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManagerInterface;

class DeviceBlocker {
    /**
     * @var EntityManagerInterface
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
     * @param string $platform
     * @param string $deviceId
     */
    public function updateUserDeviceId($userId, $platform, $deviceId) {
        $sql = 'INSERT INTO user_device (user_id, platform, device_id) VALUE (?, ?, ?)
            ON DUPLICATE KEY UPDATE platform = ?, device_id = ?';
        try {
            $this->em->getConnection()->executeUpdate($sql,
                array($userId, $platform, $deviceId, $platform, $deviceId),
                array(\PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR));
        } catch (\Exception $e) {
            //do nothing
        }
    }

    /**
     * @param int $userId
     * @return null|array [platform: string, deviceId: string]
     */
    public function getUserDeviceId($userId) {
        $sql = 'SELECT platform, device_id FROM user_device WHERE user_id = ?';
        $stat = $this->em->getConnection()->executeQuery($sql, array($userId), array(\PDO::PARAM_INT));
        $result = $stat->fetchAll(\PDO::FETCH_ASSOC);
        if (count($result) == 0) {
            return null;
        } else {
            $r = $result[0];
            return array($r['platform'], $r['device_id']);
        }
    }

    /**
     * @param int $userId
     * @throws \Exception
     */
    public function blockUserDevice($userId) {
        $deviceInfo = $this->getUserDeviceId($userId);
        if ($deviceInfo == null) {
            return;
        }
        list($platform, $deviceId) = $deviceInfo;
        
        // OPPO手机的设备ID是固定的,因此不能封锁设备
        if ($deviceId === '812345678912345') {
            throw new \Exception('OPPO手机使用了固定的设备ID, 因此不能封锁设备, 否则会影响所有使用OPPO手机的用户.');
        }
        $this->blockDevice($platform, $deviceId, $userId);
    }

    /**
     * @param string $platform
     * @param string $deviceId
     * @param int $userId
     */
    public function blockDevice($platform, $deviceId, $userId = null) {
//       设备判断方案存在问题，暂时屏蔽该功能
        return;
        $sql = 'INSERT INTO blocking_device (platform, device_id, user_id, create_time) VALUE
            (?, ?, ?, ?) ON DUPLICATE KEY UPDATE user_id = ?, create_time = ?';
        $dateFormat = $this->em->getConnection()->getDatabasePlatform()->getDateTimeFormatString();
        $now = (new \DateTime())->format($dateFormat);
        $this->em->getConnection()->executeUpdate($sql,
            array($platform, $deviceId, $userId, $now, $userId, $now),
            array(\PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_INT,
                \PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_STR)
        );
    }

    /**
     * @param string $platform
     * @param string $deviceId
     */
    public function unblockDevice($platform, $deviceId) {
        $sql = 'DELETE FROM blocking_device WHERE platform = ? AND device_id = ?';
        $this->em->getConnection()->executeUpdate($sql,
            array($platform, $deviceId), array(\PDO::PARAM_STR, \PDO::PARAM_STR));
    }

    /**
     * @param string $platform
     * @param string $deviceId
     * @return bool
     */
    public function isDeviceBlocked($platform, $deviceId) {
        $sql = 'SELECT 1 FROM blocking_device WHERE platform = ? AND device_id = ?';
        $stat = $this->em->getConnection()->executeQuery($sql,
            array($platform, $deviceId), array(\PDO::PARAM_STR, \PDO::PARAM_STR));
        $result =  $stat->fetchAll(\PDO::FETCH_ASSOC);
        return count($result) > 0;
    }
}