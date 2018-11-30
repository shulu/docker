<?php
namespace Lychee\Bundle\ApiBundle\SmsRecoder;

use Doctrine\ORM\EntityManagerInterface;
use Lychee\Bundle\ApiBundle\Entity\IpBlockerRecord;
use Lychee\Bundle\ApiBundle\Entity\SmsRecord;
use Symfony\Bridge\Doctrine\RegistryInterface;

class SmsRecorder {

    /**
     * @var EntityManagerInterface
     */
    private $em;

    private $serviceContainer;

    /**
     * IpBlocker constructor.
     * @param RegistryInterface $registry
     */
    public function __construct($registry, $serviceContainer) {
        $this->em = $registry->getManager();
        $this->serviceContainer = $serviceContainer;
    }


    private function getMemcached() {
        return $this->serviceContainer->get('memcache.default');
    }

    /**
     * @param string $ip
     * @param string $area
     * @param string $phone
     * @param string $platform
     * @param string $osVersion
     * @param string $appVersion
     * @param string $deviceId
     */
    public function record($ip, $area, $phone, $platform, $osVersion, $appVersion, $deviceId) {
        $r = new SmsRecord();
        $r->time = new \DateTime();
        $r->ip = $ip;
        $r->areaCode = $area;
        $r->phone = $phone;
        $r->platform = $platform;
        $r->osVersion = $osVersion;
        $r->appVersion = $appVersion;
        $r->deviceId = $deviceId;

        $this->em->persist($r);
        $this->em->flush();
    }


    /**
     * 判断60s内是否有重复的手机号发送短信验证请求
     * @param $phone
     * @param $ip
     *
     * @return bool
     */
    public function tryRecord($phone, $ip) {
        $mc = $this->getMemcached();
        $key = 'sms_record_phone_'.$phone;
        $r = true;
        try {
            $r = $mc->add($key, 1, 0, 60);
        } catch (\Exception $e) {}
        return $r;
    }

    /**
     * 判断60s内是否有重复的手机号发送短信验证请求
	 * @param $phone
	 * @param $ip
	 *
	 * @return bool
	 */
    public function isDuplicateRecord($phone, $ip) {
        $now = new \DateTime();
        $timeInterval = new \DateInterval('PT1H');
        $now->sub($timeInterval);
        $conn = $this->em->getConnection();
        $sql = 'SELECT id FROM sms_records WHERE time<' . $conn->quote($now->format('Y-m-d H:i:s'))
            . ' ORDER BY id DESC LIMIT 1';
//	    echo PHP_EOL . $sql . PHP_EOL;
        $stmt = $conn->query($sql);
        $result = $stmt->fetch();
        if (!$result) {
            $lastId = 0;
        } else {
            $lastId = $result['id'];
        }
        $sql = 'SELECT COUNT(id) AS record_count FROM sms_records WHERE id>' . $conn->quote($lastId) .
            ' AND (phone=' . $conn->quote($phone) . ' OR ip=' . $conn->quote($ip) . ')';
//	    echo PHP_EOL . $sql . PHP_EOL;
        $stmt = $conn->query($sql);
        $result = $stmt->fetch();
        if ($result && $result['record_count'] > 5) {
            return true;
        }

        return false;
    }
}