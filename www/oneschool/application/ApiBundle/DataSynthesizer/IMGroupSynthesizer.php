<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;
use Lychee\Module\IM\Group;

class IMGroupSynthesizer extends AbstractSynthesizer {

    private $memberSynthesizer;

    /**
     * @param array $groupsByIds
     * @param Synthesizer $memberSynthesizer
     */
    public function __construct($groupsByIds, $memberSynthesizer) {
        parent::__construct($groupsByIds);
        $this->memberSynthesizer = $memberSynthesizer;
    }

    /**
     * @param Group $entity
     * @param mixed $info
     *
     * @return array
     */
    protected function synthesize($entity, $info = null) {
        $result = array(
            'id' => $entity->id,
            'name' => $entity->name,
            'icon' => $entity->icon,
            'description' => $entity->description,
            'topic' => array('id' => $entity->topicId),
            'no_disturb' => $entity->noDisturb,
        );
        if ($entity->createTime) {
            $result['create_time'] = $entity->createTime->getTimestamp();
        }
        if ($this->memberSynthesizer && count($entity->memberIds) > 0) {
            $members = array();
            foreach ($entity->memberIds as $mid) {
                $member = $this->memberSynthesizer->synthesizeOne($mid);
                if ($member) {
                    $members[] = $member;
                }
            }
            $result['members'] = $members;
        } else {
            $result['member_count'] = $entity->memberCount;
        }
        return $result;
    }

}