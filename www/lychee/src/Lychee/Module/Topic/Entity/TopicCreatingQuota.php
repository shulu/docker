<?php
namespace Lychee\Module\Topic\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="topic_creating_quota")
 */
class TopicCreatingQuota {
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="user_id", type="bigint")
     */
    public $userId;

    /**
     * @var int
     *
     * @ORM\Column(name="quota", type="integer")
     */
    public $quota;
} 