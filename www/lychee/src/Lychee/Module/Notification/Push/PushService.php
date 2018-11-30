<?php
namespace Lychee\Module\Notification\Push;

use Lychee\Component\GraphStorage\FollowingResolver;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Lychee\Module\Relation\RelationService;
use Lychee\Module\Notification\Push\PushSettingManager;
use Lychee\Module\Notification\Entity\PushSetting;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PushService {

    private $producer;
    private $relationService;
    private $pushSettingManager;
    private $defaultPushSetting;
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @param Producer $producer
     * @param RelationService $relationService
     * @param PushSettingManager $pushSettingManager
     * @param RegistryInterface $registry
     */
    public function __construct($producer, $relationService, $pushSettingManager, $registry) {
        $this->producer = $producer;
        $this->producer->setContentType('application/json');
        $this->relationService = $relationService;
        $this->pushSettingManager = $pushSettingManager;
        $this->defaultPushSetting = new PushSetting();
        $this->em = $registry->getManager();
    }

    /**
     * @param int $type PushEventType
     * @param int $from
     * @param int $to
     * @return bool
     */
    private function canPush($type, $from, $to) {
        if ($this->relationService->userBlackListHas($to, $from)) {
            return false;
        }

        $setting = $this->pushSettingManager->fetchOne($to);
        if ($setting->isTimeNoDisturbing(new \DateTime())) {
            return false;
        }
        $pushSettingType = $this->settingTypeFromEventType($setting, $type);
        if ($pushSettingType === PushSetting::TYPE_NONE) {
            return false;
        } else if ($pushSettingType === PushSetting::TYPE_MY_FOLLOWEE) {
            if ($this->relationService->isUserFollowingAnother($to, $from) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int $type PushEventType
     * @param int $from
     * @param int[] $tos
     * @return int[]
     */
    public function filterPushableTo($type, $from, $tos) {
        $pushableTos = array();

        $batchSize = 1000;
        $t = ceil( count($tos) / $batchSize );
        for ($i = 0; $i < $t; ++$i) {
            $subTos = array_slice($tos, $i * $batchSize, $batchSize);
            $subPushableTos = $this->filterPushableToStep($type, $from, $subTos);
            $pushableTos = array_merge($pushableTos, $subPushableTos);
        }

        return $pushableTos;
    }

    private function filterPushableToStep($type, $from, $tos) {
        $noBlockingTos = $this->relationService->userBlackListFilterNoBlocking($tos, $from);
        $settings = $this->pushSettingManager->fetch($noBlockingTos);

        $pushableTos = array();
        $needFollowingTos = array();
        $now = new \DateTime();
        foreach ($noBlockingTos as $toId) {
            $setting = isset($settings[$toId]) ? $settings[$toId] : $this->defaultPushSetting;

            if ($setting->isTimeNoDisturbing($now)) {
                continue;
            }
            $pushSettingType = $this->settingTypeFromEventType($setting, $type);
            if ($pushSettingType == PushSetting::TYPE_ALL) {
                $pushableTos[] = $toId;
            } else if ($pushSettingType == PushSetting::TYPE_MY_FOLLOWEE) {
                $needFollowingTos[] = $toId;
            }
        }
        $followingResolver = $this->relationService->buildRelationResolver($from,
            $needFollowingTos, FollowingResolver::HINT_NO_FOLLOWEE);
        foreach ($needFollowingTos as $toId) {
            if ($followingResolver->isFollower($toId)) {
                $pushableTos[] = $toId;
            }
        }
        $this->em->clear();
        return $pushableTos;
    }

    /**
     * @param PushSetting $pushSetting
     * @param int $type PushEventType
     * @return int
     */
    private function settingTypeFromEventType($pushSetting, $type) {
        switch ($type) {
            case PushEventType::MENTION:
                return $pushSetting->mentionMeType;
            case PushEventType::REPLY:
            case PushEventType::COMMENT:
                return $pushSetting->commentMeType;
            case PushEventType::IMAGE_COMMENT:
                return $pushSetting->imageCommentMeType;
            case PushEventType::FOLLOW:
                return $pushSetting->followMeType;
            case PushEventType::LIKE:
                return $pushSetting->likeMeType;
            case PushEventType::MESSAGE:
                return $pushSetting->messageMeType;
            case PushEventType::TOPIC_APPLY:
                return $pushSetting->topicApplyType;
            case PushEventType::TOPIC_APPLY_CONFIRM:
                return $pushSetting->topicApplyType;
            case PushEventType::SCHEDULE_ABOUT_TO_START:
            case PushEventType::SCHEDULE_CANCELLED:
                return $pushSetting->scheduleType;
            case PushEventType::POST:
                return $pushSetting->followeePostType;
	        case PushEventType::ANCHOR:
	        	return $pushSetting->followeeAnchorType;
            default:
                return PushSetting::TYPE_ALL;
        }
    }

    /**
     * @param int|int[] $to
     * @param string $message
     * @param string $type
     * @param int|null $pushBefore
     */
    public function push($to, $type, $message, $pushBefore = null) {
        $data = array(
            'to' => is_array($to) ? $to : array($to),
            'msg' => $message,
            'type' => $type,
        );
        if ($pushBefore) {
            $data['pushBefore'] = $pushBefore;
        }
        $this->producer->publish(json_encode($data));
    }

    /**
     * @param int|null $from
     * @param int|int[] $to
     * @param int $type PushEventType
     * @param string $message
     */
    public function pushEvent($from, $to, $type, $message) {
        if (!is_array($to)) {
            $to = array($to);
        }
        if (empty($to)) {
            return;
        }
        assert(count($to) <= 1000, '推送的对象最多可以设置1000条');

        if (is_string($type)) {
            $type = PushEventType::fromString($type);
        }
        if ($from) {
            $pushableTo = $this->filterPushableTo($type, $from, $to);
            if (empty($pushableTo)) {
                return;
            }
        } else {
            $pushableTo = $to;
        }

        $this->push($pushableTo, PushEventType::toString($type), $message, time() + 1800);
    }

    public function reportError($errorMessage) {
        $this->push(array(31724), 'system', '[Error]: '.$errorMessage);
    }

}