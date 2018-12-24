<?php
namespace app\module\account\mission;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Lychee\Module\Account\Mission\Entity\UserMissionState;
use Lychee\Module\Account\AccountService;

class MissionManager {

    /**
     * @var EntityManager
     */
    private $em;
    private $accountService;

    private $today;

    /**
     * @return \DateTime
     */
    public function getToday() {
        if ($this->today == null) {
            $this->today = (new \DateTime())->setTime(0, 0, 0);
        }

        return $this->today;
    }

    /**
     * @param Registry $registry
     * @param string $managerName
     * @param AccountService $accountService
     */
    public function __construct($registry, $managerName, $accountService) {
        $this->em = $registry->getManager($managerName);
        $this->accountService = $accountService;
    }

    /**
     * @param int $type MissionType
     *
     * @return Mission
     */
    public function getMissionByType($type) {
        switch ($type) {
            case MissionType::INVITE:
                return new CountingMissionImp('invitedFriends', 100, 1);
            case MissionType::FOLLOW_TOPIC:
                return new CountingMissionImp('followedTopic', 50, 5);
            case MissionType::FILL_PROFILE:
                return new CountingMissionImp('filledProfile', 30, 1);
            case MissionType::SET_FAVORITE_TOPIC:
                return new CountingMissionImp('setFavoriteTopic', 20, 1);
            case MissionType::SET_ATTRIBUTES:
                return new CountingMissionImp('setAttributes', 10, 1);
            case MissionType::DAILY_LIKE_POST:
                return new DailyMissionImp('dailyLikePost', 1, 20);
            case MissionType::DAILY_COMMENT:
                return new DailyMissionImp('dailyComment', 2, 10);
            case MissionType::DAILY_IMAGE_COMMENT:
                return new DailyMissionImp('dailyImageComment', 4, 5);
            case MissionType::DAILY_SHARE:
                return new DailyMissionImp('dailyShare', 5, 8);
            case MissionType::DAILY_POST:
                return new DailyMissionImp('dailyPost', 10, 5);
            case MissionType::DAILY_SIGNIN:
                return new DailyMissionImp('dailySignin', 10, 1);
            default:
                throw new \LogicException('unknown mission type '. $type);
        }
    }

    /**
     * @return DailyMission[]
     */
    public function getDailyMissions() {
        return array(
            $this->getMissionByType(MissionType::DAILY_LIKE_POST),
            $this->getMissionByType(MissionType::DAILY_COMMENT),
            $this->getMissionByType(MissionType::DAILY_IMAGE_COMMENT),
            $this->getMissionByType(MissionType::DAILY_SHARE),
            $this->getMissionByType(MissionType::DAILY_POST),
            $this->getMissionByType(MissionType::DAILY_SIGNIN),
        );
    }

    /**
     * @return CountingMission[]
     */
    public function getCountingMissions() {
        return array(
            $this->getMissionByType(MissionType::FOLLOW_TOPIC),
            $this->getMissionByType(MissionType::FILL_PROFILE),
            $this->getMissionByType(MissionType::SET_ATTRIBUTES),
        );
    }

    /**
     * @param int $userId
     * @return bool
     */
    public function isUserActivateMissions($userId) {
        /** @var UserMissionState $state */
        $state = $this->em->find(UserMissionState::class, $userId);
        if ($state === null) {
            return true;
        } else {
            return $state->activated;
        }
    }

    public function userActivateMissions($userId) {
        try {
            $this->em->beginTransaction();
            /** @var UserMissionState $state */
            $state = $this->em->find(UserMissionState::class, $userId, LockMode::PESSIMISTIC_WRITE);
            if ($state == null) {
                $state = new UserMissionState();
                $state->userId = $userId;
                $state->activated = true;
                $this->em->persist($state);
                $this->em->flush($state);
            } else if ($state->activated === true) {
                return;
            } else {
                $state->activated = true;
                $this->em->flush($state);
            }

            $this->accountService->userGainExperience($userId, 0);
            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    /**
     * @param int $userId
     * @param int $type MissionType
     *
     * @return MissionResult
     */
    public function userAccomplishMission($userId, $type) {
        /** @var Mission $mission */
        $mission = $this->getMissionByType($type);
        try {
            $this->em->beginTransaction();
            $state = $this->em->find(UserMissionState::class, $userId, LockMode::PESSIMISTIC_WRITE);
            $newState = false;
            if ($state === null) {
                $state = new UserMissionState();
                $state->userId = $userId;
                $state->activated = true;
                $newState = true;
            }

            if ($mission->isCompleted($state)) {
                $this->em->rollback();
                return null;
            }

            $missionCompleted = $this->accomplishMission($state, $mission);

            if ($newState) {
                $this->em->persist($state);
            }
            $this->em->flush($state);

            if ($missionCompleted) {
                $level = $this->accountService->userGainExperience($userId, $mission->getExperience(), $levelup);
            }
            $this->em->commit();

            if ($missionCompleted) {
                return new MissionResult($level, $mission->getExperience(), $levelup);
            } else {
                return null;
            }
        } catch (\Exception $e) {
            $this->em->rollback();
            return null;
        }
    }

    /**
     * @param UserMissionState $state
     * @param Mission $mission
     * @return bool
     */
    private function accomplishMission($state, $mission) {
        if ($state->activated === false) {
            return false;
        }
        return $mission->accomplish($state);
    }

    /**
     * @param int $userId
     * @param int $type MissionType
     *
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function userHasCompletedMission($userId, $type) {
        /** @var UserMissionState $state */
        $state = $this->em->find(UserMissionState::class, $userId);
        if ($state == null) {
            return false;
        } else {
            $mission = $this->getMissionByType($type);
            return $mission->isCompleted($state);
        }
    }

    /**
     * @param int $userId
     *
     * @return array array($count, $experience)
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function summarizeUserUncompletedMissions($userId) {
        /** @var UserMissionState $state */
        $state = $this->em->find(UserMissionState::class, $userId);
        $count = 0;
        $experience = 0;
        if ($state == null) {
            $state = new UserMissionState();
        }

        foreach ($this->getCountingMissions() as $mission) {
            /** @var AbstractMissionImp  $mission */
            $fieldName = $mission->getFieldName();
            if ($state->$fieldName == false) {
                $count += 1;
                $experience += $mission->getExperience();
            }
        }
        $today = (new \DateTime())->setTime(0, 0, 0);
        foreach ($this->getDailyMissions() as $mission) {
            /** @var DailyAbstractMissionImp  $mission */
            $fieldName = $mission->getFieldName();

            if ($state->dailyDate === null || $state->dailyDate < $today) {
                $missionRemainCount = $mission->getDailyCount();
            } else if ($state->dailyDate > $today || $state->$fieldName >= $mission->getDailyCount()) {
                $missionRemainCount = 0;
            } else {
                $missionRemainCount = $mission->getDailyCount() - $state->$fieldName;
            }

            $count += $missionRemainCount;
            $experience += $missionRemainCount * $mission->getExperience();
        }

        return array($count, $experience);
    }

}