<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Component\Foundation\ArrayUtility;

class PostLatestCommentSynthesizer extends AbstractSynthesizer {

    /**
     * @var Synthesizer
     */
    private $commentSynthesizer;

    /**
     * @param array $latestIdsByPostIds
     * @param Synthesizer $commentSynthesizer
     */
    public function __construct($latestIdsByPostIds, $commentSynthesizer) {
        parent::__construct($latestIdsByPostIds);
        $this->commentSynthesizer = $commentSynthesizer;
    }

    /**
     * @param array $latestIds
     * @param mixed $info
     *
     * @return array
     */
    protected function synthesize($latestIds, $info = null) {
        if ($latestIds === null) {
            return null;
        } else {
            return array_map(function($latestId){
                return $this->commentSynthesizer->synthesizeOne($latestId);
            }, $latestIds);
        }
    }

} 