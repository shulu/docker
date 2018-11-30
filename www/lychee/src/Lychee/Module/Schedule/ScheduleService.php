<?php
namespace Lychee\Module\Schedule;

use Doctrine\ORM\EntityManagerInterface;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Foundation\CursorableIterator\CustomizedCursorableIterator;
use Lychee\Module\Schedule\Entity\ScheduleByTopic;
use Lychee\Module\Schedule\Entity\ScheduleByUser;
use Lychee\Module\Schedule\Entity\ScheduleJoiner;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Lychee\Module\Schedule\Entity\Schedule;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Lychee\Module\Notification\NotificationService;

class ScheduleService {
    /**
     * @var EntityManagerInterface
     */
    private $em;

    private $eventDispatcher;

    /**
     * ActivityService constructor.
     *
     * @param RegistryInterface $registry
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct($registry, $eventDispatcher) {
        $this->em = $registry->getManager();
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param int $creatorId
     * @param int $topicId
     * @param int $postId
     * @param string $name
     * @param string $desc
     * @param string $address
     * @param string $poi
     * @param float $longitude
     * @param float $latitude
     * @param \DateTime $startTime
     * @param \DateTime $endTime
     *
     * @return Schedule
     * @throws \Exception
     */
    public function create($creatorId, $topicId, $postId, $name, $desc,
                           $address, $poi, $longitude, $latitude, $startTime, $endTime) {

        $schedule = new Schedule();
        $schedule->creatorId = $creatorId;
        $schedule->topicId = $topicId;
        $schedule->postId = $postId;
        $schedule->name = $name;
        $schedule->description = $desc;
        $schedule->address = $address;
        $schedule->poi = $poi;
        $schedule->longitude = $longitude;
        $schedule->latitude = $latitude;
        $schedule->startTime = $startTime;
        $schedule->endTime = $endTime;

        $scheduleByTopic = new ScheduleByTopic();
        $scheduleByTopic->topicId = $topicId;
        $scheduleByTopic->startTime = $startTime->getTimestamp();
        $scheduleByTopic->postId = $postId;

        try {
            $this->em->beginTransaction();
            $this->em->persist($schedule);
            $this->em->flush();

            $scheduleByTopic->scheduleId = $schedule->id;
            $this->em->persist($scheduleByTopic);
            $this->em->flush();

            $this->eventDispatcher->dispatch(ScheduleEvent::CREATE, new ScheduleEvent($schedule->id));
            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }

        return $schedule;
    }

    /**
     * @param int $id
     *
     * @return Schedule|null
     */
    public function fetchOne($id) {
        return $this->em->find(Schedule::class, $id);
    }

    /**
     * @param int[] $ids
     *
     * @return Schedule[]
     */
    public function fetch($ids) {
        if (count($ids) == 0) {
            return array();
        }
        /** @var Schedule[] $schedules */
        $schedules = $this->em->getRepository(Schedule::class)->findBy(array('id' => $ids));
        $schedulesByIds = array();
        foreach ($schedules as $schedule) {
            $schedulesByIds[$schedule->id] = $schedule;
        }
        $result = array();
        foreach ($ids as $id) {
            $result[$id] = $schedulesByIds[$id];
        }

        return $result;
    }

    /**
     * @param int $joinerId
     * @param int $scheduleId
     *
     * @throws \Exception
     */
    public function join($joinerId, $scheduleId) {
        $conn = $this->em->getConnection();
        try {
            $conn->beginTransaction();
            $position = (int)(floor(microtime(true) * 1000));
            $insertSql = 'INSERT INTO schedule_joiners(schedule_id, joiner_id, position) '.
                'VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE position = position';
            $affectedRow = $conn->executeUpdate($insertSql, array($scheduleId, $joinerId, $position),
                array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
            if ($affectedRow == 1) {
                $updateSql = 'UPDATE schedule SET joiner_count = joiner_count + 1 WHERE id = ?';
                $conn->executeUpdate($updateSql, array($scheduleId), array(\PDO::PARAM_INT));

                $schedule = $this->fetchOne($scheduleId);
                $insertUserSql = 'INSERT INTO schedule_by_user(user_id, start_time, schedule_id, post_id)'.
                    ' VALUES(?, ?, ?, ?) ON DUPLICATE KEY UPDATE schedule_id = schedule_id';
                $conn->executeUpdate($insertUserSql, array($joinerId, $schedule->startTime->getTimestamp(),
                    $scheduleId, $schedule->postId),
                    array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    /**
     * @param int $joinerId
     * @param int $scheduleId
     *
     * @throws \Exception
     */
    public function unjoin($joinerId, $scheduleId) {
        $conn = $this->em->getConnection();
        try {
            $conn->beginTransaction();
            $deleteSql = 'DELETE FROM schedule_joiners WHERE schedule_id = ? AND joiner_id = ?';
            $affectedRow = $conn->executeUpdate($deleteSql, array($scheduleId, $joinerId),
                array(\PDO::PARAM_INT, \PDO::PARAM_INT));
            if ($affectedRow == 1) {
                $updateSql = 'UPDATE schedule SET joiner_count = joiner_count - 1 WHERE id = ?';
                $conn->executeUpdate($updateSql, array($scheduleId), array(\PDO::PARAM_INT));

                $deleteUserSql = 'DELETE FROM schedule_by_user WHERE user_id = ? AND schedule_id = ?';
                $conn->executeUpdate($deleteUserSql, array($joinerId, $scheduleId),
                    array(\PDO::PARAM_INT, \PDO::PARAM_INT));
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    /**
     * @param int $scheduleId
     * @param string $cursor
     * @param int $count
     * @param string $nextCursor
     *
     * @return int[]
     */
    public function getJoinerIds($scheduleId, $cursor, $count, &$nextCursor = null) {
        if ($count <= 0) {
            $nextCursor = $cursor;
            return array();
        }
        @list($position, $joinerId) = explode(',', $cursor);
        $position = intval($position);
        if ($position == 0) {
            $position = PHP_INT_MAX;
        }
        $joinerId = intval($joinerId);

        $query = $this->em->createQuery('SELECT sj FROM '.ScheduleJoiner::class
            .' sj WHERE sj.scheduleId = :schedule AND ((sj.position = :position AND sj.joinerId < :joiner) OR sj.position < :position)'
            .' ORDER BY sj.position DESC, sj.joinerId DESC');
        $query->setParameters(array('schedule' => $scheduleId, 'position' => $position, 'joiner' => $joinerId));
        $query->setMaxResults($count);
        $joiners = $query->getResult();

        if (count($joiners) < $count) {
            $nextCursor = 0;
        } else {
            /** @var ScheduleJoiner $last */
            $last = $joiners[count($joiners) - 1];
            $nextCursor = $last->position . ',' . $last->joinerId;
        }

        return ArrayUtility::columns($joiners, 'joinerId');
    }

    public function getJoinerIdIterator($scheduleId) {
        return new CustomizedCursorableIterator(function($cursor, $step, &$nextCursor) use($scheduleId){
            return $this->getJoinerIds($scheduleId, $cursor, $step, $nextCursor);
        });
    }

    /**
     * @param int $scheduleId
     * @param int $cancellerId
     *
     * @throws \Exception
     * @throws \Doctrine\DBAL\DBALException
     */
    public function cancel($scheduleId, $cancellerId) {
        try {
            $this->em->beginTransaction();
            $sql = 'UPDATE schedule SET cancelled = 1, canceller_id = ? WHERE id = ? AND cancelled = 0';
            $affectedRow = $this->em->getConnection()->executeUpdate($sql, array($cancellerId, $scheduleId),
                array(\PDO::PARAM_INT, \PDO::PARAM_INT));
            if ($affectedRow > 0) {
                $schedule = $this->fetchOne($scheduleId);
                $deleteTopicSql = 'DELETE FROM schedule_by_topic WHERE topic_id = ? AND schedule_id = ?';
                $this->em->getConnection()->executeUpdate($deleteTopicSql, array($schedule->topicId, $schedule->id),
                    array(\PDO::PARAM_INT, \PDO::PARAM_INT));
                $this->eventDispatcher->dispatch(ScheduleEvent::CANCEL, new ScheduleEvent($scheduleId));
            }
            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    /**
     * @param int $scheduleId
     * @param NotificationService $notificationService
     */
    public function onCancel($scheduleId, $notificationService) {
        $schedule = $this->fetchOne($scheduleId);
        if ($schedule->cancelled == false) {
            return;
        }

        $now = new \DateTime();
        $hasEnd = $schedule->endTime <= $now;

        $itor = $this->getJoinerIdIterator($scheduleId);
        foreach ($itor as $joinerIds) {
            if (count($joinerIds) == 0) {
                continue;
            }

            if ($hasEnd == false) {
                $notificationService->notifyScheduleCancelledEvent($joinerIds,
                    $schedule->topicId, $schedule, $schedule->cancellerId);
            }

            $joiners = implode(',', $joinerIds);
            $deleteUserSql = 'DELETE FROM schedule_by_user WHERE user_id IN('.$joiners
                .') AND schedule_id = '.intval($scheduleId);
            $this->em->getConnection()->executeUpdate($deleteUserSql);

        }
    }

    /**
     * @param int $joiner
     * @param int[] $scheduleIds
     *
     * @return ScheduleJoinResolver
     * @throws \Doctrine\DBAL\DBALException
     */
    public function buildJoinResolver($joiner, $scheduleIds) {
        $sql = 'SELECT schedule_id FROM schedule_joiners WHERE schedule_id IN ('.
            implode(',', $scheduleIds).') AND joiner_id = ?';
        $stmt = $this->em->getConnection()->executeQuery($sql, array($joiner), array(\PDO::PARAM_INT));
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $joinedSchedule = array();
        foreach ($result as $i) {
            $joinedSchedule[] = $i['schedule_id'];
        }

        return new ScheduleJoinResolver($joinedSchedule);
    }

    /**
     * @param \DateTime $from
     * @param \DateTime $to
     * @return Schedule[]
     */
    public function fetchByStartTimeBetween($from, $to) {
        $dql = 'SELECT s FROM '.Schedule::class.' s WHERE s.startTime >= :from'
            .' AND s.startTime < :to AND s.cancelled = 0';
        $query = $this->em->createQuery($dql);
        $query->setParameters(array('from' => $from, 'to' => $to));
        return $query->getResult();
    }

    /**
     * @param int $scheduleId
     * @param int $time
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function updateNotifyTime($scheduleId, $time) {
        $sql = 'UPDATE schedule SET last_notify_time = ? WHERE id = ?';
        $this->em->getConnection()->executeUpdate($sql, array($time, $scheduleId),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT));
    }

    /**
     * @param int $topicId
     * @param string $cursor
     * @param int $count
     * @param string $nextCursor
     *
     * @return int[]
     */
    public function getSchedulePostIdsByTopicId($topicId, $cursor, $count, &$nextCursor) {
        if ($count <= 0) {
            $nextCursor = $cursor;
            return array();
        }
        @list($time, $scheduleId) = explode(',', $cursor);
        $time = intval($time);
        if ($time == 0) {
            $time = PHP_INT_MAX;
        }
        $topicId = intval($topicId);

        $query = $this->em->createQuery('SELECT st FROM '.ScheduleByTopic::class
            .' st WHERE st.topicId = :topic AND ((st.startTime = :startTime AND st.scheduleId < :schedule) OR st.startTime < :startTime)'
            .' ORDER BY st.startTime DESC, st.scheduleId DESC');
        $query->setParameters(array('schedule' => $scheduleId, 'startTime' => $time, 'topic' => $topicId));
        $query->setMaxResults($count);
        $items = $query->getResult();

        if (count($items) < $count) {
            $nextCursor = 0;
        } else {
            /** @var ScheduleByTopic $last */
            $last = $items[count($items) - 1];
            $nextCursor = $last->startTime . ',' . $last->scheduleId;
        }

        return ArrayUtility::columns($items, 'postId');
    }

    /**
     * @param int $userId
     * @param string $cursor
     * @param int $count
     * @param string $nextCursor
     *
     * @return int[]
     */
    public function getSchedulePostIdsByUserId($userId, $cursor, $count, &$nextCursor) {
        if ($count <= 0) {
            $nextCursor = $cursor;
            return array();
        }
        @list($time, $scheduleId) = explode(',', $cursor);
        $time = intval($time);
        if ($time == 0) {
            $time = PHP_INT_MAX;
        }
        $userId = intval($userId);

        $query = $this->em->createQuery('SELECT st FROM '.ScheduleByUser::class
            .' st WHERE st.userId = :user AND ((st.startTime = :startTime AND st.scheduleId < :schedule) OR st.startTime < :startTime)'
            .' ORDER BY st.startTime DESC, st.scheduleId DESC');
        $query->setParameters(array('schedule' => $scheduleId, 'startTime' => $time, 'user' => $userId));
        $query->setMaxResults($count);
        $items = $query->getResult();

        if (count($items) < $count) {
            $nextCursor = 0;
        } else {
            /** @var ScheduleByTopic $last */
            $last = $items[count($items) - 1];
            $nextCursor = $last->startTime . ',' . $last->scheduleId;
        }

        return ArrayUtility::columns($items, 'postId');
    }
}