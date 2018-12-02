<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Module\Game\Entity\Game;

class GameSynthesizer extends AbstractSynthesizer {
    /**
     * @param Game $entity
     * @param mixed $info
     *
     * @return array
     */
    protected function synthesize($entity, $info = null) {
        return array(
            'id' => $entity->getId(),
            'appName' => $entity->getAppName(),
	        'short_description' => $entity->getShortDescription(),
            'description' => $entity->getDescription(),
            'icon' => $entity->getIcon(),
            'appType' => $entity->getAppType(),
            'ios_link' => $entity->getIosLink(),
            'android_link' => $entity->getAndroidLink(),
	        'category' => $entity->getCategoryId(),
	        'publisher' => $entity->getPublisher(),
	        'player_numbers' => $entity->getPlayerNumbers(),
	        'launch_date' => $entity->getLaunchDate()->getTimestamp(),
        );
    }
}