<?php
namespace Lychee\Module\Voting\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="voting")
 */
class Voting {

    /**
     * @var VotingOption[]|null
     */
    private $options = null;

    /**
     * Voting constructor.
     *
     * @param int $postId
     * @param string $title
     * @param string $description
     * @param VotingOption[] $options
     */
    public function __construct($postId, $title, $description, $options) {
        $this->postId = $postId;
        $this->title = $title;
        $this->description = $description;
        if (count($options) < 2 || count($options) > 10) {
            throw new \LogicException('voting options count ('.count($options).') error.');
        }
        foreach ($options as $i => $option) {
            $this->{'opt' . ($i + 1)} = $option->title;
            $this->{'opt' . ($i + 1) . '_count'} = $option->voteCount;
        }

        $this->options = $options;
    }

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var int
     *
     * @ORM\Column(name="post_id", type="bigint")
     */
    public $postId;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=60)
     */
    public $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=400, nullable=true)
     */
    public $description;

    /**
     * @var int
     *
     * @ORM\Column(name="vote_count", type="integer", options={"default": "0"})
     */
    public $vote_count = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="opt1", type="string", length=20)
     */
    public $opt1;

    /**
     * @var int
     *
     * @ORM\Column(name="opt1_count", type="integer", options={"default": "0"})
     */
    public $opt1_count = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="opt2", type="string", length=20)
     */
    public $opt2;

    /**
     * @var int
     *
     * @ORM\Column(name="opt2_count", type="integer", options={"default": "0"})
     */
    public $opt2_count = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="opt3", type="string", length=20, nullable=true)
     */
    public $opt3;

    /**
     * @var int
     *
     * @ORM\Column(name="opt3_count", type="integer", nullable=true, options={"default": "0"})
     */
    public $opt3_count = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="opt4", type="string", length=20, nullable=true)
     */
    public $opt4;

    /**
     * @var int
     *
     * @ORM\Column(name="opt4_count", type="integer", nullable=true, options={"default": "0"})
     */
    public $opt4_count = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="opt5", type="string", length=20, nullable=true)
     */
    public $opt5;

    /**
     * @var int
     *
     * @ORM\Column(name="opt5_count", type="integer", nullable=true, options={"default": "0"})
     */
    public $opt5_count = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="opt6", type="string", length=20, nullable=true)
     */
    public $opt6;

    /**
     * @var int
     *
     * @ORM\Column(name="opt6_count", type="integer", nullable=true, options={"default": "0"})
     */
    public $opt6_count = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="opt7", type="string", length=20, nullable=true)
     */
    public $opt7;

    /**
     * @var int
     *
     * @ORM\Column(name="opt7_count", type="integer", nullable=true, options={"default": "0"})
     */
    public $opt7_count = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="opt8", type="string", length=20, nullable=true)
     */
    public $opt8;

    /**
     * @var int
     *
     * @ORM\Column(name="opt8_count", type="integer", nullable=true, options={"default": "0"})
     */
    public $opt8_count = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="opt9", type="string", length=20, nullable=true)
     */
    public $opt9;

    /**
     * @var int
     *
     * @ORM\Column(name="opt9_count", type="integer", nullable=true, options={"default": "0"})
     */
    public $opt9_count = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="opt10", type="string", length=20, nullable=true)
     */
    public $opt10;

    /**
     * @var int
     *
     * @ORM\Column(name="opt10_count", type="integer", nullable=true, options={"default": "0"})
     */
    public $opt10_count = 0;

    /**
     * @return VotingOption[]
     */
    public function getOptions() {
        if ($this->options) {
            return $this->options;
        }

        $options = array();
        for ($i = 1; $i <= 10; ++$i) {
            if (is_null($this->{'opt'.$i})) {
                break;
            }
            $option = new VotingOption();
            $option->title = $this->{'opt'.$i};
            $option->voteCount = $this->{'opt'.$i.'_count'};
            $options[] = $option;
        }
        $this->options = $options;
        return $this->options;
    }

}