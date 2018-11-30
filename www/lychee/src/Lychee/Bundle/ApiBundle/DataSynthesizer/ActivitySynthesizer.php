<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Module\Activity\Entity\Activity;

class ActivitySynthesizer extends AbstractSynthesizer {

    /**
     * @var Synthesizer
     */
    protected $userSynthesizer;

    /**
     * @var Synthesizer
     */
    protected $topicSynthesizer;

    /**
     * @var Synthesizer
     */
    protected $commentSynthesizer;

    /**
     * @var Synthesizer
     */
    protected $postSynthesizer;

    /**
     * @param array $activitiesByIds
     * @param Synthesizer|null $userSynthesizer
     * @param Synthesizer|null $topicSynthesizer
     * @param Synthesizer|null $commentSynthesizer
     * @param Synthesizer|null $postSynthesizer
     */
    public function __construct(
        $activitiesByIds,
        $userSynthesizer,
        $topicSynthesizer,
        $commentSynthesizer,
        $postSynthesizer
    ) {
        parent::__construct($activitiesByIds);
        $this->userSynthesizer = $userSynthesizer;
        $this->topicSynthesizer = $topicSynthesizer;
        $this->commentSynthesizer = $commentSynthesizer;
        $this->postSynthesizer = $postSynthesizer;
    }

    /**
     * @param Activity $activity
     * @param mixed $info
     *
     * @return array
     * @throws \RuntimeException
     */
    protected function synthesize($activity, $info = null) {
        $result = array(
            'time' => $activity->createTime->getTimestamp(),
            'user' => $this->userSynthesizer ?
                    $this->userSynthesizer->synthesizeOne($activity->userId):
                    array('id' => $activity->userId)
                ,
        );

        switch ($activity->action) {
            case Activity::ACTION_POST:
                $result['action'] = 'post';
                $result['post'] = $this->postSynthesizer ?
                        $this->postSynthesizer->synthesizeOne($activity->targetId):
                        array('id' => $activity->targetId);
                break;
            case Activity::ACTION_FOLLOW_USER:
                $result['action'] = 'follow';
                $result['followee'] = $this->userSynthesizer ?
                    $this->userSynthesizer->synthesizeOne($activity->targetId):
                    array('id' => $activity->targetId);
                break;
            case Activity::ACTION_FOLLOW_TOPIC:
                $result['action'] = 'follow';
                $result['topic'] = $this->topicSynthesizer ?
                    $this->topicSynthesizer->synthesizeOne($activity->targetId):
                    array('id' => $activity->targetId);
                break;
            case Activity::ACTION_LIKE_POST:
                $result['action'] = 'like';
                $result['post'] = $this->postSynthesizer ?
                    $this->postSynthesizer->synthesizeOne($activity->targetId):
                    array('id' => $activity->targetId);
                break;
            case Activity::ACTION_LIKE_COMMENT:
                $result['action'] = 'like';
                $result['comment'] = $this->commentSynthesizer ?
                    $this->commentSynthesizer->synthesizeOne($activity->targetId):
                    array('id' => $activity->targetId);
                break;
            case Activity::ACTION_COMMENT_IMAGE:
                $result['action'] = 'image_comment';
                $result['comment'] = $this->commentSynthesizer ?
                    $this->commentSynthesizer->synthesizeOne($activity->targetId):
                    array('id' => $activity->targetId);
                break;
            case Activity::ACTION_TOPIC_CREATE:
                $result['action'] = 'create_topic';
                $result['topic'] = $this->topicSynthesizer ?
                    $this->topicSynthesizer->synthesizeOne($activity->targetId):
                    array('id' => $activity->targetId);
                break;
            default:
                throw new \RuntimeException('activity action type error');
        }

        return $result;
    }

} 