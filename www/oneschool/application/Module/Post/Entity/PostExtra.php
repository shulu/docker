<?php
namespace Lychee\Module\Post\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="post_extra", indexes={
 *    @ORM\Index(name="user_type", columns={"author_id", "type"}),
 *    @ORM\Index(name="topic_id_type", columns={"topic_id", "type"})
 * }, options={"comment":"帖子扩展表，冗余id，主人，次元id，类型。用于排序"})
 */
class PostExtra {
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint", options={"unsigned":true,"comment":"post.id"})
     * @ORM\Id
     */
    public $id;

    /**
     * @var int
     *
     * @ORM\Column(name="author_id", type="bigint", options={"unsigned":true,"comment":"post.author_id,帖子主人"})
     */
    public $authorId;

    /**
     * @var int
     *
     * @ORM\Column(name="topic_id", type="bigint", nullable=true, options={"unsigned":true,"comment":"post.topic_id,帖子的次元ID"})
     */
    public $topicId;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="smallint", options={"default":0,"comment":"post.type,帖子类型"})
     */
    public $type;

}
