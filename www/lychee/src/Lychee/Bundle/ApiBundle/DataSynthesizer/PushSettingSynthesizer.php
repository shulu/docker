<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Module\Notification\Entity\PushSetting;

class PushSettingSynthesizer extends AbstractSynthesizer {
    /**
     * @param PushSetting $pushSetting
     * @param mixed $info
     *
     * @return array
     */
    protected function synthesize($pushSetting, $info = null) {
        $result = array(
            'mention' => $this->stringFromSettingType($pushSetting->mentionMeType),
            'comment' => $this->stringFromSettingType($pushSetting->commentMeType),
            'image_comment' => $this->stringFromSettingType($pushSetting->imageCommentMeType),
            'follow' => $this->stringFromSettingType($pushSetting->followMeType),
            'like' => $this->stringFromSettingType($pushSetting->likeMeType),
            'message' => $this->stringFromSettingType($pushSetting->messageMeType),
            'topic_apply' => $this->stringFromSettingType($pushSetting->topicApplyType),
            'schedule' => $this->stringFromSettingType($pushSetting->scheduleType),
            'followee_post' => $this->stringFromSettingType($pushSetting->followeePostType),
            'followee_anchor' => $this->stringFromSettingType($pushSetting->followeeAnchorType),
        );

        if ($pushSetting->noDisturb) {
            $result['no_disturb'] = array(
                'timezone' => $pushSetting->noDisturbTimeZone,
                'from' => $pushSetting->noDisturbStartTime,
                'to' => $pushSetting->noDisturbEndTime,
            );
        } else {
            $result['no_disturb'] = null;
        }

        return $result;
    }

    /**
     * @param int $type
     * @return string
     */
    private function stringFromSettingType($type) {
        if ($type === PushSetting::TYPE_NONE) {
            return 'no';
        } else if ($type === PushSetting::TYPE_MY_FOLLOWEE) {
            return 'followee';
        } else {
            return 'all';
        }
    }
} 