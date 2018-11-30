<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Voting\VotingService;

class VotingSynthesizerBuilder extends AbstractSynthesizerBuilder {

    public function build($idsOrEntities, $accountId = 0, $options = null) {
        /** @var VotingService $votingService */
        $votingService = $this->container()->get('lychee.module.voting');

        list($vids, $votings) = $this->extractIdsAndEntities($idsOrEntities, function($ids) use($votingService) {
            return $votingService->multiGet($ids);
        });

        if ($accountId > 0) {
            $voterIdMap = array();
            $allVoterIds = array();
            foreach ($vids as $vid) {
                $voterIdsByOption = $votingService->getLatestVotersPerOption($vid, 5);
                if (count($voterIdsByOption) > 0) {
                    $voterIds = call_user_func_array('array_merge', $voterIdsByOption);
                } else {
                    $voterIds = array();
                }
                $allVoterIds = array_merge($allVoterIds, $voterIds);
                $voterIdMap[$vid] = $voterIdsByOption;
            }

            /** @var SynthesizerBuilder $synthesizerBuilder */
            $synthesizerBuilder = $this->container()->get('lychee_api.synthesizer_builder');
            $userSynthesizer = $synthesizerBuilder->buildSimpleUserSynthesizer($allVoterIds);

            $voterSynthesizers = array();
            foreach ($voterIdMap as $vid => $voterIdsByOption) {
                $voterSynthesizers[$vid] = new ListSynthesizer($voterIdsByOption, $userSynthesizer);
            }
            $voteResolver = $votingService->buildVoteResolver($accountId, $vids);
        } else {
            $voterSynthesizers = array();
            $voteResolver = null;
        }

        if (isset($options['four_options_at_most']) && $options['four_options_at_most']) {
            return new VotingSynthesizer($votings, $voteResolver, $voterSynthesizers, true);
        } else {
            return new VotingSynthesizer($votings, $voteResolver, $voterSynthesizers);
        }
    }

}