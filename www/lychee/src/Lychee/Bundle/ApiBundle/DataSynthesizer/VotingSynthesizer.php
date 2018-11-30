<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Module\Voting\Entity\Voting;
use Lychee\Module\Voting\VoteResolver;


class VotingSynthesizer extends AbstractSynthesizer {

    private $voteResolver;
    private $latestVoterSynthesizers;
    private $showFourOptionsAtMost;

    /**
     * VotingSynthesizer constructor.
     *
     * @param array $entitiesByIds
     * @param VoteResolver $voteResolver
     * @param Synthesizer[] $latestVoterSynthesizers
     * @param bool $showFourOptionsAtMost 艹,好蠢的设置
     */
    public function __construct($entitiesByIds, $voteResolver, $latestVoterSynthesizers, $showFourOptionsAtMost = false) {
        parent::__construct($entitiesByIds);
        $this->voteResolver = $voteResolver;
        $this->latestVoterSynthesizers = $latestVoterSynthesizers;
        $this->showFourOptionsAtMost = $showFourOptionsAtMost;
    }

    /**
     * @param Voting $entity
     * @param mixed $info
     * @return array
     */
    protected function synthesize($entity, $info = null) {
        $r = array(
            'id' => $entity->id,
            'title' => $entity->title,
            'description' => $entity->description,
            'vote_count' => $entity->vote_count,
        );
        $options = array();

        if ($this->voteResolver) {
            $voterSynthesizer = $this->latestVoterSynthesizers[$entity->id];
            $votedOption = $this->voteResolver->getVotedOption($entity->id);
            foreach ($entity->getOptions() as $i => $o) {
                $optionId = $i + 1;
                $option = array('id' => $optionId, 'title' => $o->title, 'vote_count' => $o->voteCount);
                if ($votedOption == $optionId) {
                    $option['voted'] = true;
                }
                if ($voterSynthesizer) {
                    $option['voters'] = $voterSynthesizer->synthesizeOne($optionId);
                }
                $options[] = $option;
            }
        } else {
            foreach ($entity->getOptions() as $i => $o) {
                $options[] = array('id' => $i + 1, 'title' => $o->title);
            }
        }
        if ($this->showFourOptionsAtMost) {
            $options = array_slice($options, 0, 4);
        }

        $r['options'] = $options;

        return $r;
    }

}