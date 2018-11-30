<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Bundle\CoreBundle\Entity\PostCounting;

class PostCountingSynthesizer extends AbstractSynthesizer {

    public function __construct($countingsByIds) {
        $this->entitiesByIds = $countingsByIds;
    }

    /**
     * @param PostCounting $entity
     * @param mixed $info
     *
     * @return array
     */
    protected function synthesize($entity, $info = null) {
        return array(
            'liked_count' => $entity->likedCount,
            'commented_count' => $entity->commentedCount,
            'reposted_count' => $entity->repostedCount,
        );
    }
} 