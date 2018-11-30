<?php
namespace Lychee\Module\Topic\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table("topic_category")
 */
class TopicCategory {
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    public $id;

    /**
     * @ORM\Column(name="name", type="string", length=20, unique=true)
     */
    public $name;

}