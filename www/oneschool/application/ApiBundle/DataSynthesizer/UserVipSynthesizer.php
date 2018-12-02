<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Module\Account\Entity\UserVip;

class UserVipSynthesizer extends AbstractSynthesizer {

    /**
     * @param UserVip $entity
     * @param null $info
     */
    protected function synthesize($entity, $info = null) {
        return ['certificate' => $entity->certificationText];
    }

}