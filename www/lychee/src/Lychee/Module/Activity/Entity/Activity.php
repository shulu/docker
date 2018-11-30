<?php
namespace Lychee\Module\Activity\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="activity", indexes={
 *   @ORM\Index(name="user_id_index", columns={"user_id", "id"})
 * })
 */
class Activity {

    const ACTION_POST = 11;
    const ACTION_FOLLOW_USER = 21;
    const ACTION_FOLLOW_TOPIC = 22;
    const ACTION_LIKE_POST = 31;
    const ACTION_LIKE_COMMENT = 32;
    const ACTION_COMMENT_IMAGE = 41;
    const ACTION_TOPIC_CREATE = 51;

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
     * @var int
     *
     * @ORM\Column(name="target_id", type="bigint")
     */
    public $targetId;

    /**
     * @var int
     *
     * @ORM\Column(name="action", type="smallint")
     */
    public $action;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_time", type="datetime")
     */
    public $createTime;

} 