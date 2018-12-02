<?php
namespace Lychee\Module\Notification\Push;

use Lychee\Module\Notification\Entity\PushSetting;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

class PushSettingManager {

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param Registry $registry
     * @param string $entityManagerName
     */
    public function __construct($registry, $entityManagerName) {
        $this->entityManager = $registry->getManager($entityManagerName);
    }

    /**
     * @param int $userId
     * @param PushSetting $pushSetting
     */
    public function update($userId, $pushSetting) {
        $pushSetting->userId = $userId;

        $entity = $this->entityManager->merge($pushSetting);
        $this->entityManager->flush($entity);
    }

    /**
     * @param $userId
     * @return PushSetting
     */
    public function fetchOne($userId) {
        $entity = $this->entityManager->find(PushSetting::class, $userId);
        if ($entity == null) {
            $entity = new PushSetting();
        }
        return $entity;
    }

    /**
     * @param int[] $userIds
     *
     * @return PushSetting[]
     */
    public function fetch($userIds) {
        if (count($userIds) == 0) {
            return array();
        }

        /** @var PushSetting[] $settings */
        $settings = $this->entityManager->getRepository(PushSetting::class)->findBy(array('userId' => $userIds));
        $result = array();
        foreach ($settings as $setting) {
            $result[$setting->userId] = $setting;
        }
        return $result;
    }
} 