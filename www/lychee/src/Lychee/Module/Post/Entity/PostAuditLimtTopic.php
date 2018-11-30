<?php
namespace Lychee\Module\Post\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="post_audit_limit_topic", indexes={
 * })
 */
class PostAuditLimtTopic {

    /**
     * @var int
     *
     * @ORM\Column(name="topic_id", type="bigint", options={"unsigned":true})
     * @ORM\Id
     */
    public $topicId;
}