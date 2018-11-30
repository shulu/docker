<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Component\Foundation\ArrayUtility;

class LikerSynthesizer extends AbstractSynthesizer {

    private $userSynthesizer;

    /**
     * @param array $likerIdsByIds
     * @param Synthesizer $userSynthesizer
     */
    public function __construct($likerIdsByIds, $userSynthesizer) {
        parent::__construct($likerIdsByIds);
        $this->userSynthesizer = $userSynthesizer;
    }

    /**
     * @param array $likerIds
     * @param mixed $info
     *
     * @return array
     */
    protected function synthesize($likerIds, $info = null) {
        if ($this->userSynthesizer) {
            $result = array_map(function($likerId) use ($info) {
                return $this->userSynthesizer->synthesizeOne($likerId, $info);
            }, $likerIds);
            return ArrayUtility::filterValuesNonNull($result);
        } else {
            return $likerIds;
        }
    }
} 