<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Module\Notification\Entity\OfficialNotification;

class OfficialNotificationSynthesizer extends AbstractSynthesizer {

    private $userSynthesizer;

    /**
     * @param array $notificationsByIds
     * @param Synthesizer|null $userSynthesizer
     */
    public function __construct(
        $notificationsByIds,
        $userSynthesizer
    ) {
        parent::__construct($notificationsByIds);
        $this->userSynthesizer = $userSynthesizer;
    }

    /**
     * @param OfficialNotification $entity
     * @param mixed $info
     *
     * @return array
     */
    protected function synthesize($entity, $info = null) {
        $result = array(
            'id' => $entity->id,
            'time' => $entity->publishTime->getTimestamp(),
            'message' => $entity->message,
            'image' => $entity->image,
            'url' => $entity->url,
        );
        if ($this->userSynthesizer) {
            $fromInfo = $this->userSynthesizer->synthesizeOne($entity->fromId, $info);
            if ($fromInfo) {
                $result['from'] = $fromInfo;
            }
        } else {
            $result['from'] = array('id' => $entity->fromId);
        }

        return $result;
    }

}