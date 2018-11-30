<?php
namespace Lychee\Module\Topic\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table("topic_group")
 */
class TopicGroup {
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    public $id;

    /**
     * @ORM\Column(name="name", type="string", length=50, unique=true)
     */
    public $name;

    /**
     * @var int
     *
     * @ORM\Column(name="weight", type="integer", options={"unsigned":true, "default":"0", "comment":"排序权重，值越大，排序越靠前"})
     */
    public $weight = 0;
}