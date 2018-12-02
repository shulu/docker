<?php
namespace Lychee\Bundle\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="spammer_records")
 */
class SpammerRecord {

    /**
     * @var int
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var int
     * @ORM\Column(name="spammer_id", type="bigint", unique=true)
     */
    public $spammerId;

    /**
     * @var int
     * @ORM\Column(name="time", type="integer")
     */
    public $time;

}