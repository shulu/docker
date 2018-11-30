<?php
namespace Lychee\Module\Voting\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="voting_voters", indexes={
 *   @ORM\Index(name="voting_option_time_voter_idx", columns={"voting_id", "option", "time", "voter_id"})
 * })
 */
class VotingVoter {

    /**
     * @var int
     *
     * @ORM\Column(name="voting_id", type="bigint")
     * @ORM\Id
     */
    public $votingId;

    /**
     * @var int
     *
     * @ORM\Column(name="voter_id", type="bigint")
     * @ORM\Id
     */
    public $voterId;

    /**
     * @var int
     *
     * @ORM\Column(name="option", type="smallint")
     */
    public $option;

    /**
     * @var int
     *
     * @ORM\Column(name="time", type="integer")
     */
    public $time;

}