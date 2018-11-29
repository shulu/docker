<?php
namespace app\entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="topic_chat_post")
 */
class TopicChatPost {

    /**
     * @var int
     *
     * @ORM\Column(name="topic_id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public $topicId;

    /**
     * @var int
     *
     * @ORM\Column(name="post_id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public $postId;

}