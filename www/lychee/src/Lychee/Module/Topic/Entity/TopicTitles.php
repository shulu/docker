<?php
namespace Lychee\Module\Topic\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="topic_titles")
 */
class TopicTitles {

    /**
     * @ORM\Column(name="title", type="string", length=60)
     * @ORM\Id
     * @var string
     */
    public $topicTitle;

}