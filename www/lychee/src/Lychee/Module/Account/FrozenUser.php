<?php
namespace Lychee\Module\Account;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("user_frozen", indexes={
 *   @ORM\Index(name="user_index", columns={"user_id"})
 * })
 */
class FrozenUser {

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(name="id", type="bigint")
     */
    public $id;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="bigint")
     */
    public $userId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time", type="datetime")
     */
    public $time;

    /**
     * @var string
     *
     * @ORM\Column(name="reason", type="string", length=200, nullable=true)
     */
    public $reason;
} 