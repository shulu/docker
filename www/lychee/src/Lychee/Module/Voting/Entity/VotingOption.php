<?php
namespace Lychee\Module\Voting\Entity;

class VotingOption {

    public function __construct($title = null) {
        $this->title = $title;
    }

    /**
     * @var string
     */
    public $title;

    /**
     * @var int
     */
    public $voteCount = 0;

}