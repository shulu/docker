<?php
namespace Lychee\Component\GraphStorage\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 */
class AbstractMetadata {
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="target_id", type="bigint")
     */
    public $targetId;

    /**
     * @var int
     *
     * @ORM\Column(name="follower_count", type="integer", nullable=true)
     */
    public $followerCount;

    /**
     * @var int
     *
     * @ORM\Column(name="followee_count", type="integer", nullable=true)
     */
    public $followeeCount;

} 