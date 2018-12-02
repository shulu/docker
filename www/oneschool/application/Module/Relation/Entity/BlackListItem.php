<?php
namespace Lychee\Module\Relation\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="user_blacklist")
 */
class BlackListItem {
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="user_id", type="bigint")
     */
    private $userId;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="target_id", type="bigint")
     */
    private $targetId;
}