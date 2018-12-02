<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Component\Foundation\ArrayUtility;

class TopicFollowerSynthesizer extends AbstractSynthesizer {

    /**
     * @var Synthesizer
     */
    private $userSynthesizer;

    /**
     * @param array $followersByTopicIds
     * @param Synthesizer $userSynthesizer
     */
    public function __construct($followersByTopicIds, $userSynthesizer) {
        $this->entitiesByIds = $followersByTopicIds;
        $this->userSynthesizer = $userSynthesizer;
    }

    /**
     * @param array $followerIds
     * @param mixed $info
     * @return array
     */
    protected function synthesize($followerIds, $info = null) {
        return ArrayUtility::filterValuesNonNull( array_map(
            function($followerId) use ($info) {
                if ($this->userSynthesizer) {
                    return $this->userSynthesizer->synthesizeOne($followerId, $info);
                } else {
                    return $followerId;
                }
            },
            $followerIds
        ) );
    }

} 