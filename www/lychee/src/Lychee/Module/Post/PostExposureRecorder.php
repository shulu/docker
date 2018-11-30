<?php
namespace Lychee\Module\Post;

use Lychee\Component\Database\DoctrineUtility;
use Lychee\Component\Foundation\CursorableIterator\ArrayCursorableIterator;
use Lychee\Module\Post\Entity\PostExposureRecord;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManagerInterface;

class PostExposureRecorder {

    /** @var EntityManagerInterface  */
    private $em;

    private $container;

    /**
     * PostExposureCounter constructor.
     * @param RegistryInterface $doctrine
     */
    public function __construct($doctrine, $container) {
        $this->em = $doctrine->getManager();
        $this->container = $container;
    }

    /**
     * @param [int, int][] $postIds 形如[[pid, tid], [pid, tid], ...]的数组
     */
    public function recordPostsExposure($postIdsAndTopicIds) {
        $event = [];
        $event['time'] = time();
        $event['postIdsAndTopicIds'] = $postIdsAndTopicIds;
        $this->container->get('lychee.dynamic_dispatcher_async')->dispatch('post.exposure', $event);
    }

    public function clearRecordBefore(\DateTime $time) {
        $maxId = DoctrineUtility::getMaxIdWithTime($this->em, PostExposureRecord::class, 'id', 'time', $time);
        $sql = 'DELETE FROM post_exposure_records WHERE id < ? LIMIT 100000';
        $deleted = 1;
        while ($deleted > 0) {
            $deleted = $this->em->getConnection()->executeUpdate($sql, array($maxId), array(\PDO::PARAM_INT));
        }
    }


    /**
     * 帖子曝光后事件，消费消息队列异步执行
     *
     * @param $eventBody
     */
    public function asyncAfterExposure($eventBody) {
        $time = $eventBody['time'];
        $postIdsAndTopicIds = $eventBody['postIdsAndTopicIds'];
        $this->realRecordPostsExposure($postIdsAndTopicIds, $time);
        return true;
    }


    public function realRecordPostsExposure($postIdsAndTopicIds, $time) {
        $postIdItor = new ArrayCursorableIterator($postIdsAndTopicIds);
        $postIdItor->setStep(100);

        $conn = $this->em->getConnection();
        $sqlPrefix = 'INSERT INTO post_exposure_records(`time`, post_id, topic_id) VALUES';
        try {
            foreach ($postIdItor as $step) {
                if (empty($step)) {
                    continue;
                }
                $values = array_map(function($ids) use ($time) {return "($time,$ids[0],$ids[1])";}, $step);
                $sql = $sqlPrefix . implode(',', $values);
                $conn->executeUpdate($sql);
            }
        } catch (\Exception $e) {
            //do nothing
        }
    }

}