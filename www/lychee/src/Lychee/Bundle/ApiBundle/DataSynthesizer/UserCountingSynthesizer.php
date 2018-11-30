<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Bundle\CoreBundle\Entity\UserCounting;

class UserCountingSynthesizer extends AbstractSynthesizer {
    public function __construct($countingsByIds) {
        $this->entitiesByIds = $countingsByIds;
    }

    /**
     * @param UserCounting $entity
     * @param mixed $info
     *
     * @return array
     */
    protected function synthesize($entity, $info = null) {
        return array(
            'post_count' => $entity->postCount,
            'image_comment_count' => $entity->imageCommentCount,
        );
    }
} 