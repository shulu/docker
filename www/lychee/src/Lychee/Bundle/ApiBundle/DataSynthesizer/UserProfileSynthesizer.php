<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Bundle\CoreBundle\Entity\UserProfile;

class UserProfileSynthesizer extends AbstractSynthesizer {
    /**
     * @param UserProfile $entity
     * @param mixed $info
     * @return array
     */
    protected function synthesize($entity, $info = null) {
        return array(
            'signature' => $entity->signature,
            'cover_url' => $entity->coverUrl,
            'honmei' => $entity->honmei,
            'attributes' => $entity->attributes,
            'skills' => $entity->skills,
            'constellation' => $entity->constellation,
            'blood_type' => $entity->bloodType,
            'age' => $entity->age,
            'birthday' => $entity->birthday ? $entity->birthday->format('Y-m-d') : null,
            'location' => $entity->location,
            'school' => $entity->school,
            'community' => $entity->community,
            'fancy' => $entity->fancy
        );
    }

} 