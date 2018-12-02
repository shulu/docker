<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Component\Foundation\ArrayUtility;

class ListSynthesizer extends AbstractSynthesizer {
    /**
     * @var Synthesizer
     */
    private $entitySynthesizer;

    /**
     * @param array $idsByParentIds
     * @param Synthesizer $entitySynthesizer
     */
    public function __construct($idsByParentIds, $entitySynthesizer) {
        parent::__construct($idsByParentIds);
        $this->entitySynthesizer = $entitySynthesizer;
    }

    /**
     * @param array $latestIds
     * @param mixed $info
     * @return array
     */
    protected function synthesize($ids, $info = null) {
        if ($ids === null) {
            return array();
        } else {
            return ArrayUtility::filterValuesNonNull(array_map(function($id) use ($info) {
                return $this->entitySynthesizer->synthesizeOne($id, $info);
            }, $ids));
        }
    }

}