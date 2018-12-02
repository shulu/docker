<?php
namespace Lychee\Module\Voting;

class VoteResolver {

    private $optionMap;

    public function __construct($votedOptionsByVotings) {
        $this->optionMap = $votedOptionsByVotings;
    }

    /**
     * @param int $votingId
     * @return int|null
     */
    public function getVotedOption($votingId) {
        if (isset($this->optionMap[$votingId])) {
            return $this->optionMap[$votingId];
        } else {
            return null;
        }
    }

}