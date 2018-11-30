<?php
namespace Lychee\Module\Topic\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="topic_following_application", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="topic_position_applicant_udx", columns={"topic_id", "position", "applicant_id"})
 * })
 */
class TopicFollowingApplication {

    /**
     * @var int
     *
     * @ORM\Column(name="topic_id", type="bigint")
     * @ORM\Id
     */
    public $topicId;

    /**
     * @var int
     *
     * @ORM\Column(name="applicant_id", type="bigint")
     * @ORM\Id
     */
    public $applicantId;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="bigint")
     */
    public $position;

    /**
     * @var int
     *
     * @ORM\Column(name="apply_time", type="integer")
     */
    public $applyTime;

    /**
     * @var string
     *
     * @ORM\Column(name="apply_description", type="string", length=100)
     */
    public $applyDescription;

}