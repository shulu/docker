<?php
namespace Lychee\Module\Recommendation\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="rec_group_posts", indexes={
 *   @ORM\Index(name="gid_sid_idx", columns={"group_id", "seq_id"})
 * }, uniqueConstraints={
 *   @ORM\UniqueConstraint(name="gid_pid_udx", columns={"group_id", "post_id"})
 * })
 */
class RecommendationGroupPost {
    /**
     * @var int
     * @ORM\Column(name="seq_id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $seqId;

    /**
     * @var int
     * @ORM\Column(name="group_id", type="integer")
     */
    public $groupId;

    /**
     * @var int
     * @ORM\Column(name="post_id", type="bigint")
     */
    public $postId;
}