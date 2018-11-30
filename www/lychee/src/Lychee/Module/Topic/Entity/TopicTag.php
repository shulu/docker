<?php
namespace Lychee\Module\Topic\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="topic_tag")
 */
class TopicTag {
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    public $id;

    /**
     * @var string
     * @ORM\Column(name="name", type="string", length=20, unique=true)
     */
    public $name;

    /**
     * @var string
     * @ORM\Column(name="color", type="string", length=10)
     */
    public $color;

    /**
     * @var int
     *
     * @ORM\Column(name="order_key", type="smallint")
     */
    public $order;

}