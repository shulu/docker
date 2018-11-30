<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Module\Schedule\Entity\Schedule;
use Lychee\Module\Schedule\ScheduleJoinResolver;

class ScheduleSynthesizer extends AbstractSynthesizer {

    private $joinersSynthesizer;
    private $joinResolver;

    /**
     * ScheduleSynthesizer constructor.
     *
     * @param array $entitiesByIds
     * @param Synthesizer $joinersSynthesizer
     * @param ScheduleJoinResolver $joinResolver
     */
    public function __construct($entitiesByIds, $joinersSynthesizer, $joinResolver) {
        parent::__construct($entitiesByIds);
        $this->joinersSynthesizer = $joinersSynthesizer;
        $this->joinResolver = $joinResolver;
    }

    /**
     * @param Schedule $entity
     * @param mixed $info
     * @return array
     */
    protected function synthesize($entity, $info = null) {
        $result = array(
            'id' => $entity->id,
            'name' => $entity->name,
            'creator' => $this->joinersSynthesizer ?
                $this->joinersSynthesizer->synthesizeOne($entity->creatorId):
                array('id' => $entity->creatorId),
            'post_id' => $entity->postId,
            'description' => $entity->description,
            'address' => $entity->address,
            'poi' => $entity->poi,
            'longitude' => $entity->longitude,
            'latitude' => $entity->latitude,
            'start_time' => $entity->startTime->format('Y-m-d H:i:s'),
            'end_time' => $entity->endTime->format('Y-m-d H:i:s'),
            'joiner_count' => $entity->joinerCount,
        );

        if ($this->joinersSynthesizer) {
            $result['joiners'] = $this->joinersSynthesizer->synthesizeOne($entity->id);
        }

        if ($this->joinResolver) {
            $result['joined'] = $this->joinResolver->hasJoin($entity->id);
        }

        if ($entity->cancelled) {
            $result['cancelled'] = true;
        }

        return $result;
    }

}